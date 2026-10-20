<?php

use App\Models\AssessmentAccessCode;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use App\Models\User;
use App\Services\AssessmentAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function privateAccessFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'peserta_assessment', 'employee_number' => '000123']);
    $program = AssessmentProgram::create(['code' => 'PRIVATE', 'name' => 'Program akses pribadi', 'status' => 'active',
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(2), 'created_by' => $admin->id]);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $user->id, 'status' => 'assigned']);
    $type = SimulationType::create(['code' => SimulationType::PROBLEM_ANALYSIS, 'name' => 'Problem Analysis', 'sequence' => 1, 'delivery_mode' => 'multi_page_response']);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'PRIVATE-PA', 'title' => 'PA', 'status' => 'published', 'created_by' => $admin->id]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    $code = app(AssessmentAccessService::class)->issue($participant);

    return compact('admin', 'user', 'program', 'participant', 'simulation', 'code');
}

test('private code is encrypted idempotent and never mailed or exposed in model JSON', function () {
    Mail::fake();
    Queue::fake();
    extract(privateAccessFixture());
    expect(app(AssessmentAccessService::class)->issue($participant)->secret)->toBe($code->secret)
        ->and(DB::table('assessment_access_codes')->value('secret'))->not->toBe($code->secret)
        ->and($code->toJson())->not->toContain($code->secret, $code->secret_hash, $code->version);
    Mail::assertNothingSent();
    Queue::assertNothingPushed();
});

test('participant logs in with NIP and private code and cannot reuse it after logout', function () {
    extract(privateAccessFixture());
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => strtolower($code->secret)])
        ->assertRedirect(route('peserta-assessment.simulations.index'));
    $this->assertAuthenticatedAs($user);
    $this->get(route('peserta-assessment.simulations.index'))->assertOk()->assertSee($program->name);
    $this->post(route('logout'))->assertRedirect();
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $code->secret])->assertSessionHasErrors('access');
    $this->assertGuest();
});

test('wrong NIP rejected and secret is never flashed', function () {
    extract(privateAccessFixture());
    $this->post(route('participant.login'), ['nip' => 'wrong', 'access_code' => $code->secret])
        ->assertSessionHasErrors('access')->assertSessionMissing('_old_input.access_code');
    expect($code->fresh()->used_at)->toBeNull();
});

test('schedule membership and completed program gates reject login', function (string $condition) {
    extract(privateAccessFixture());
    match ($condition) {
        'early' => $program->update(['starts_at' => now()->addHour()]),
        'ended' => $program->update(['ends_at' => now()]),
        'completed' => $program->update(['status' => 'completed']),
        'removed' => $participant->update(['status' => 'withdrawn']),
        'submitted' => $simulation->sessions()->create(['assessment_participant_id' => $participant->id, 'status' => 'submitted']),
    };
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $code->secret])->assertSessionHasErrors('access');
    $this->assertGuest();
    expect($code->fresh()->used_at)->toBeNull();
})->with(['early', 'ended', 'completed', 'removed', 'submitted']);

test('changing code revocation and schedule expiry terminate an existing session', function (string $condition) {
    extract(privateAccessFixture());
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $code->secret])->assertSessionHasNoErrors();
    match ($condition) {
        'replace' => app(AssessmentAccessService::class)->issue($participant, true),
        'revoke' => $code->update(['revoked_at' => now()]),
        'ended' => $program->update(['ends_at' => now()]),
    };
    $this->getJson(route('peserta-assessment.simulations.index'))->assertUnauthorized();
    $this->assertGuest();
})->with(['replace', 'revoke', 'ended']);

test('code grants only its program even when employee belongs to another program', function () {
    extract(privateAccessFixture());
    $other = AssessmentProgram::create(['code' => 'OTHER', 'name' => 'Program lain', 'status' => 'active', 'created_by' => $admin->id]);
    AssessmentParticipant::create(['assessment_program_id' => $other->id, 'user_id' => $user->id, 'status' => 'assigned']);
    $otherSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $other->id, 'simulation_scenario_id' => $simulation->simulation_scenario_id, 'status' => 'scheduled']);
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $code->secret]);
    $this->get(route('peserta-assessment.simulations.index'))->assertOk()->assertDontSee('Program lain');
    $this->get(route('peserta-assessment.simulations.show', $otherSimulation))->assertNotFound();
});

test('admin manages private codes with scoped participant and no cache', function () {
    extract(privateAccessFixture());
    $this->actingAs($admin)->get(route('admin.participant-access.index'))->assertOk();
    $preview = $this->get(route('admin.participant-access.show', $program))->assertOk()->assertSee($code->secret)->assertDontSee('Lihat Kode');
    expect($preview->headers->get('Cache-Control'))->toContain('no-store', 'private');
    $this->post(route('admin.participant-access.manage', [$program, $participant]), ['action' => 'reveal'])
        ->assertOk()->assertSee($code->secret);
    $other = AssessmentProgram::create(['code' => 'OTHER', 'name' => 'Other', 'created_by' => $admin->id]);
    $this->post(route('admin.participant-access.manage', [$other, $participant]), ['action' => 'revoke'])->assertNotFound();
    $this->post(route('admin.participant-access.manage', [$program, $participant]), ['action' => 'revoke'])->assertRedirect();
    expect($code->fresh()->revoked_at)->not->toBeNull();
    $this->get(route('admin.participant-access.show', $program))->assertOk()->assertDontSee($code->secret);
    expect(DB::table('audit_logs')->pluck('metadata')->implode(''))->not->toContain($code->secret);
    $assessor = User::factory()->create(['role' => 'asesor']);
    $this->actingAs($assessor)->get(route('admin.participant-access.show', $program))->assertForbidden();
    $this->actingAs($assessor)->post(route('admin.participant-access.manage', [$program, $participant]), ['action' => 'reveal'])->assertForbidden();
});

test('access preview hides consumed codes and is available to super admin', function () {
    extract(privateAccessFixture());
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($superAdmin)->get(route('admin.participant-access.show', $program))->assertOk()->assertSee($code->secret);
    $code->update(['used_at' => now()]);
    $this->get(route('admin.participant-access.show', $program))->assertOk()->assertDontSee($code->secret)->assertSee('Sudah dipakai');
});

test('old invitation routes and participant password authentication cannot bypass private codes', function () {
    extract(privateAccessFixture());
    $this->get('/assessment/invitations/'.str_repeat('a', 64))->assertNotFound();
    $this->post('/assessment/invitations/'.str_repeat('a', 64).'/verify', ['otp' => '12345678'])->assertNotFound();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors();
    $this->assertGuest();
});

test('login rate limits repeated invalid attempts', function () {
    extract(privateAccessFixture());
    for ($i = 0; $i < 5; $i++) {
        $this->postJson(route('participant.login'), ['nip' => $user->employee_number, 'access_code' => 'AAAAAAAAAAAA'])->assertUnprocessable();
    }
    $this->postJson(route('participant.login'), ['nip' => $user->employee_number, 'access_code' => 'AAAAAAAAAAAA'])->assertTooManyRequests();
});

test('archive grant controls each participant simulation independently and survives program archiving', function () {
    extract(privateAccessFixture());
    $assessor = User::factory()->create(['role' => 'asesor']);
    $otherAssessor = User::factory()->create(['role' => 'asesor']);
    $session = $simulation->sessions()->create(['assessment_participant_id' => $participant->id, 'status' => 'submitted']);
    $session->submissions()->create(['revision' => 1, 'response_text' => '{"1":"PRIVATE ANSWER"}']);
    $simulation->assessorAssignments()->create(['assessor_id' => $otherAssessor->id, 'assigned_by' => $admin->id]);
    $this->actingAs($admin)->put(route('admin.result-archives.assign', [$program, $session]), ['assessor_id' => $assessor->id])->assertSessionHasNoErrors();
    $this->actingAs($otherAssessor)->get(route('asesor.reviews.edit', $session))->assertForbidden();
    $this->get(route('asesor.reviews.download', $session))->assertForbidden();
    $program->forceFill(['archived_at' => now()])->save();
    $this->actingAs($assessor)->get(route('asesor.reviews.index'))->assertOk()->assertSee($program->name);
    $this->get(route('asesor.reviews.program', $program))->assertOk()->assertSee($user->name);
    $this->get(route('asesor.reviews.edit', $session))->assertOk()->assertSee('PRIVATE ANSWER');
    $this->get(route('asesor.reviews.download', [$session, 'format' => 'word']))->assertOk();
    $this->actingAs($admin)->put(route('admin.result-archives.assign', [$program, $session]), ['assessor_id' => $otherAssessor->id])->assertSessionHasNoErrors();
    $this->actingAs($assessor)->get(route('asesor.reviews.edit', $session))->assertForbidden();
});

test('archive assignment validates role program and submitted status', function () {
    extract(privateAccessFixture());
    $session = $simulation->sessions()->create(['assessment_participant_id' => $participant->id, 'status' => 'submitted']);
    $this->actingAs($admin)->put(route('admin.result-archives.assign', [$program, $session]), ['assessor_id' => $user->id])->assertSessionHasErrors('assessor_id');
    $other = AssessmentProgram::create(['code' => 'OTHER', 'name' => 'Other', 'created_by' => $admin->id]);
    $this->put(route('admin.result-archives.assign', [$other, $session]), ['assessor_id' => null])->assertNotFound();
    $session->update(['status' => 'in_progress']);
    $this->put(route('admin.result-archives.assign', [$program, $session]), ['assessor_id' => null])->assertUnprocessable();
});

test('direct file endpoints require a grant not only an operational assignment', function () {
    extract(privateAccessFixture());
    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::disk('local')->put('answer.pdf', '%PDF-1.4 test');
    $session = $simulation->sessions()->create(['assessment_participant_id' => $participant->id, 'status' => 'submitted']);
    $submission = $session->submissions()->create(['revision' => 1, 'storage_path' => 'answer.pdf', 'original_filename' => 'answer.pdf', 'mime_type' => 'application/pdf']);
    $assessor = User::factory()->create(['role' => 'asesor']);
    $simulation->assessorAssignments()->create(['assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);
    $this->actingAs($assessor)->get(route('asesor.submissions.preview', $submission))->assertForbidden();
    $this->get(route('asesor.submissions.download', $submission))->assertForbidden();
    $grant = $session->archiveGrant()->create(['assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);
    $this->get(route('asesor.submissions.preview', $submission))->assertOk();
    $this->get(route('asesor.submissions.download', $submission))->assertDownload('answer.pdf');
    $grant->delete();
    $this->get(route('asesor.submissions.preview', $submission))->assertForbidden();
});

test('legacy queued invitation is cancelled without sending an email', function () {
    extract(privateAccessFixture());
    Mail::fake();
    $invitation = $participant->invitation()->create([
        'email' => $user->email, 'token_hash' => hash('sha256', 'legacy'),
        'valid_from' => now(), 'expires_at' => now()->addHour(), 'sent_by' => $admin->id,
    ]);
    $delivery = $invitation->deliveries()->create(['kind' => 'invitation', 'status' => 'queued']);
    (new \App\Jobs\SendAssessmentInvitation($invitation->id, $delivery->id, 'legacy'))->handle();
    expect($delivery->fresh()->status)->toBe('cancelled');
    Mail::assertNothingSent();
});

test('regenerating a code preserves saved answers and session deadline', function () {
    extract(privateAccessFixture());
    $session = $simulation->sessions()->create([
        'assessment_participant_id' => $participant->id, 'status' => 'in_progress',
        'started_at' => now()->subMinutes(10), 'expires_at' => now()->addMinutes(50),
    ]);
    $submission = $session->submissions()->create(['revision' => 1, 'response_text' => '{"1":"Jawaban tersimpan"}']);
    $deadline = $session->expires_at->toISOString();
    $service = app(AssessmentAccessService::class);
    $service->consume('000123', $code->secret);
    $replacement = $service->issue($participant, true);
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $replacement->secret])->assertSessionHasNoErrors();
    expect($session->fresh()->expires_at->toISOString())->toBe($deadline)
        ->and($submission->fresh()->response_text)->toContain('Jawaban tersimpan');
});

test('active session can present submitted PDF but loses access at program deadline', function () {
    extract(privateAccessFixture());
    $simulation->scenario->type->update(['delivery_mode' => 'file_upload', 'code' => SimulationType::PRESENTATION]);
    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::disk('local')->put('presentation.pdf', '%PDF-1.4 test');
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $code->secret])->assertSessionHasNoErrors();
    $session = $simulation->sessions()->create(['assessment_participant_id' => $participant->id, 'status' => 'submitted']);
    $session->submissions()->create(['revision' => 1, 'storage_path' => 'presentation.pdf', 'original_filename' => 'presentation.pdf', 'mime_type' => 'application/pdf']);
    $this->get(route('peserta-assessment.simulations.presentation.preview', $simulation))->assertOk();
    $this->travelTo($program->ends_at);
    $this->get(route('peserta-assessment.simulations.presentation.preview', $simulation))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('active archived program retains private code login and existing participant session until deadline', function () {
    extract(privateAccessFixture());
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $code->secret])->assertSessionHasNoErrors();
    $program->forceFill(['archived_at' => now()])->save();
    $this->get(route('peserta-assessment.simulations.index'))->assertOk()->assertSee($program->name);
    $this->post(route('logout'));
    $replacement = app(AssessmentAccessService::class)->issue($participant, true);
    $this->post(route('participant.login'), ['nip' => '000123', 'access_code' => $replacement->secret])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    $program->update(['ends_at' => now()]);
    $this->getJson(route('peserta-assessment.simulations.index'))->assertUnauthorized();
});

