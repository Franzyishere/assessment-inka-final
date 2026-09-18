<?php

use App\Jobs\SendAssessmentInvitation;
use App\Mail\AssessmentAccessMail;
use App\Models\AssessmentInvitation;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationType;
use App\Models\User;
use App\Services\AssessmentInvitationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 18)->setTime(9, 0));
    config(['assessment_access.mailer' => 'smtp']);
    Mail::fake();
    Queue::fake();
});

function invitationFixture(): array
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT, 'email' => 'testing@gmail.com']);
    $program = AssessmentProgram::create(['code' => 'INV-1', 'name' => 'Program Undangan', 'status' => 'active', 'starts_at' => now()->setTime(8, 0), 'ends_at' => now()->setTime(17, 0), 'created_by' => $admin->id]);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $user->id, 'status' => 'assigned', 'assessment_category' => 'grade_1_to_2']);
    $token = str_repeat('a', 64);
    $invitation = AssessmentInvitation::create(['assessment_participant_id' => $participant->id, 'email' => $user->email, 'token_hash' => hash('sha256', $token), 'valid_from' => now()->startOfDay(), 'expires_at' => now()->addDay()->startOfDay(), 'sent_by' => $admin->id]);

    return compact('admin', 'user', 'program', 'participant', 'token', 'invitation');
}

function invitationSession(AssessmentInvitation $invitation): array
{
    return ['assessment_invitation_id' => $invitation->id, 'assessment_invitation_version' => $invitation->token_hash];
}

test('personal email can request OTP and login to only simulations', function () {
    extract(invitationFixture());
    $this->get(route('assessment.invitation', $token))->assertOk()->assertSee('Email penerima undangan');
    $this->post(route('assessment.invitation.otp', $token), ['email' => $user->email])->assertSessionHasNoErrors();
    $mail = Mail::sent(AssessmentAccessMail::class)->first();
    expect($mail->otp)->toHaveLength(6);
    expect(Hash::check($mail->otp, $invitation->challenges()->first()->code_hash))->toBeTrue();
    $this->post(route('assessment.invitation.verify', $token), ['otp' => $mail->otp])->assertRedirect(route('peserta-assessment.simulations.index'));
    $this->assertAuthenticatedAs($user);
    $this->get(route('peserta-assessment.simulations.index'))->assertOk()->assertDontSee('Hasil &amp; Rekomendasi', false)->assertDontSee('Jadwal Assessment');
    $this->get(route('peserta-assessment.results.index'))->assertRedirect(route('peserta-assessment.simulations.index'));
    $this->get(route('peserta-assessment.schedule.index'))->assertRedirect(route('peserta-assessment.simulations.index'));
    expect($invitation->challenges()->first()->consumed_at)->not->toBeNull();
});

test('participants cannot use password or an old authenticated session without invitation', function () {
    extract(invitationFixture());
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->actingAs($user)->get(route('peserta-assessment.simulations.index'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('invitation is restricted to execution day and expires at midnight not 24 hours after issue', function () {
    extract(invitationFixture());
    $invitation = app(AssessmentInvitationService::class)->issue($participant, $admin->id);
    expect($invitation->valid_from->format('H:i'))->toBe('00:00')
        ->and($invitation->expires_at->format('Y-m-d H:i'))->toBe('2026-09-19 00:00');
    $this->travelTo(now()->setTime(23, 59, 59));
    expect($invitation->fresh()->isAccessible())->toBeTrue();
    $this->travel(1)->seconds();
    $this->actingAs($user)->withSession(invitationSession($invitation))->get(route('peserta-assessment.simulations.index'))->assertRedirect(route('login'));
});

test('future day invitation cannot request OTP', function () {
    extract(invitationFixture());
    $this->travelBack();
    $this->travelTo($invitation->valid_from->copy()->subSecond());
    $this->post(route('assessment.invitation.otp', $token), ['email' => $user->email])->assertSessionHasErrors('email');
    Mail::assertNothingSent();
});

test('wrong email does not send or create a challenge and does not disclose registration', function () {
    extract(invitationFixture());
    $this->post(route('assessment.invitation.otp', $token), ['email' => 'other@example.org'])->assertSessionHas('success');
    Mail::assertNothingSent();
    expect($invitation->challenges()->count())->toBe(0);
});

test('OTP expires after ten minutes and cannot be reused', function () {
    extract(invitationFixture());
    $service = app(AssessmentInvitationService::class);
    $service->sendOtp($invitation, $user->email, 'browser');
    $code = Mail::sent(AssessmentAccessMail::class)->last()->otp;
    $this->travel(10)->minutes();
    expect($service->verify($invitation, $code, 'browser'))->toBeNull();
    $service->sendOtp($invitation, $user->email, 'browser');
    $code = Mail::sent(AssessmentAccessMail::class)->last()->otp;
    expect($service->verify($invitation, $code, 'browser'))->not->toBeNull()
        ->and($service->verify($invitation, $code, 'browser'))->toBeNull();
});

test('resending OTP invalidates old code and is limited to once per minute', function () {
    extract(invitationFixture());
    $service = app(AssessmentInvitationService::class);
    $service->sendOtp($invitation, $user->email, 'browser');
    $first = $invitation->challenges()->first();
    expect(fn () => $service->sendOtp($invitation, $user->email, 'browser'))->toThrow(ValidationException::class);
    $this->travel(61)->seconds();
    $service->sendOtp($invitation, $user->email, 'browser');
    expect($first->fresh()->invalidated_at)->not->toBeNull();
    $code = Mail::sent(AssessmentAccessMail::class)->last()->otp;
    expect($service->verify($invitation, $code, 'other-browser'))->toBeNull()
        ->and($service->verify($invitation, $code, 'browser'))->not->toBeNull();
});

test('five invalid OTP attempts lock the challenge even for the correct code', function () {
    extract(invitationFixture());
    $service = app(AssessmentInvitationService::class);
    $service->sendOtp($invitation, $user->email, 'browser');
    foreach (range(1, 5) as $attempt) {
        expect($service->verify($invitation, '000000', 'browser'))->toBeNull();
    }
    $code = Mail::sent(AssessmentAccessMail::class)->last()->otp;
    expect($service->verify($invitation, $code, 'browser'))->toBeNull()
        ->and($invitation->challenges()->first()->attempts)->toBe(5);
});

test('revoking or reissuing an invitation terminates existing access', function (string $operation) {
    extract(invitationFixture());
    $session = invitationSession($invitation);
    $service = app(AssessmentInvitationService::class);
    if ($operation === 'revoke') {
        $service->revoke($invitation);
    } else {
        $service->issue($participant, $admin->id);
    }
    $this->actingAs($user)->withSession($session)->getJson(route('header.notifications'))->assertUnauthorized();
})->with(['revoke', 'reissue']);

test('changes to participant identity assignment or program invalidate access', function (string $change) {
    extract(invitationFixture());
    match ($change) {
        'email' => $user->update(['email' => 'changed@example.org']),
        'participant' => $participant->update(['status' => 'cancelled']),
        'program' => $program->update(['status' => 'cancelled']),
        'date' => $program->update(['starts_at' => now()->addDay()]),
    };
    $this->actingAs($user)->withSession(invitationSession($invitation))->get(route('peserta-assessment.simulations.index'))->assertRedirect(route('login'));
})->with(['email', 'participant', 'program', 'date']);

test('participant cannot view a different assigned program using its URL', function () {
    extract(invitationFixture());
    $other = AssessmentProgram::create(['code' => 'INV-2', 'name' => 'Program Lain Rahasia', 'status' => 'active', 'created_by' => $admin->id]);
    AssessmentParticipant::create(['assessment_program_id' => $other->id, 'user_id' => $user->id, 'status' => 'assigned']);
    $type = SimulationType::create(['code' => 'INV-PA', 'name' => 'PA', 'sequence' => 1, 'delivery_mode' => 'written']);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'INV-PA', 'title' => 'PA', 'status' => 'published', 'created_by' => $admin->id]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $other->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    $this->actingAs($user)->withSession(invitationSession($invitation))
        ->get(route('peserta-assessment.simulations.index'))->assertOk()->assertDontSee('Program Lain Rahasia');
    $this->get(route('peserta-assessment.simulations.show', $simulation))->assertNotFound();
    $this->post(route('peserta-assessment.simulations.start', $simulation))->assertNotFound();
});

test('admin and super admin can monitor invitations but assessor cannot', function () {
    extract(invitationFixture());
    foreach (['admin', 'super_admin'] as $role) {
        $staff = User::factory()->create(['role' => $role]);
        $this->actingAs($staff)->get(route('admin.invitations.index'))->assertOk()->assertSee('Program Undangan');
        $this->get(route('admin.invitations.show', $program))->assertOk()->assertSee($user->email);
    }
    $staff = User::factory()->create(['role' => 'asesor']);
    $this->actingAs($staff)->get(route('admin.invitations.index'))->assertForbidden();
});

test('admin bulk sending is scoped to program and requires selected participants', function () {
    extract(invitationFixture());
    $this->actingAs($admin)->post(route('admin.invitations.send', $program), [])->assertSessionHasErrors('ids');
    $this->post(route('admin.invitations.send', $program), ['ids' => [9999]])->assertUnprocessable();
    $this->post(route('admin.invitations.send', $program), ['ids' => [$participant->id]])->assertSessionHas('success');
    expect($invitation->fresh()->token_hash)->not->toBe(hash('sha256', $token));
    $this->post(route('logout'));
    $this->get(route('assessment.invitation', $token))->assertNotFound();
});

test('queued email does not send revoked or replaced invitation and records accepted separately', function () {
    extract(invitationFixture());
    $delivery = $invitation->deliveries()->create(['kind' => 'invitation']);
    (new SendAssessmentInvitation($invitation->id, $delivery->id, $token))->handle(app(AssessmentInvitationService::class));
    expect($delivery->fresh()->status)->toBe('accepted');
    Mail::assertSent(AssessmentAccessMail::class, fn ($mail) => str_contains($mail->invitationUrl, $token));
    Mail::fake();
    app(AssessmentInvitationService::class)->revoke($invitation);
    $delivery = $invitation->deliveries()->create(['kind' => 'invitation']);
    (new SendAssessmentInvitation($invitation->id, $delivery->id, $token))->handle(app(AssessmentInvitationService::class));
    expect($delivery->fresh()->status)->toBe('cancelled');
    Mail::assertNothingSent();
});

test('log mailer cannot leak OTP and late night OTP does not extend invitation', function () {
    extract(invitationFixture());
    config(['assessment_access.mailer' => 'log']);
    expect(fn () => app(AssessmentInvitationService::class)->sendOtp($invitation, $user->email, 'browser'))->toThrow(ValidationException::class);
    config(['assessment_access.mailer' => 'smtp']);
    $this->travelTo(now()->setTime(23, 58));
    app(AssessmentInvitationService::class)->sendOtp($invitation, $user->email, 'browser');
    expect($invitation->challenges()->first()->expires_at->eq($invitation->expires_at))->toBeTrue();
});

test('OTP relogin resumes saved answer and original simulation deadline', function () {
    extract(invitationFixture());
    $type = SimulationType::create(['code' => SimulationType::PROBLEM_ANALYSIS, 'name' => 'Problem Analysis', 'sequence' => 1, 'delivery_mode' => 'multi_page_response']);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'RESUME', 'title' => 'PA', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $scenario->materialPages()->create(['title' => 'Materi', 'content' => 'Materi tes', 'page_order' => 1, 'is_required' => true]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    $this->post(route('assessment.invitation.otp', $token), ['email' => $user->email]);
    $this->post(route('assessment.invitation.verify', $token), ['otp' => Mail::sent(AssessmentAccessMail::class)->last()->otp])->assertSessionHasNoErrors();
    $this->post(route('peserta-assessment.simulations.start', $simulation))->assertRedirect();
    $this->put(route('peserta-assessment.simulations.material.save', [$simulation, 1]), ['response' => 'Jawaban tetap tersimpan'])->assertSessionHasNoErrors();
    $session = SimulationSession::firstOrFail();
    $deadline = $session->expires_at->toIso8601String();
    $this->post(route('logout'));
    $this->travel(5)->minutes();
    $this->post(route('assessment.invitation.otp', $token), ['email' => $user->email]);
    $this->post(route('assessment.invitation.verify', $token), ['otp' => Mail::sent(AssessmentAccessMail::class)->last()->otp])->assertSessionHasNoErrors();
    $this->post(route('peserta-assessment.simulations.start', $simulation))->assertRedirect();
    expect($session->fresh()->expires_at->toIso8601String())->toBe($deadline)
        ->and(SimulationSession::count())->toBe(1)
        ->and($session->submissions()->first()->response_text)->toContain('Jawaban tetap tersimpan');
});

test('OTP validation does not flash the secret to session', function () {
    extract(invitationFixture());
    $this->post(route('assessment.invitation.verify', $token), ['otp' => '12345'])->assertSessionHasErrors('otp');
    expect(session()->getOldInput('otp'))->toBeNull();
});

test('failed mail transport invalidates challenge and records safe delivery failure', function () {
    extract(invitationFixture());
    Mail::shouldReceive('mailer')->once()->andThrow(new RuntimeException('SMTP secret details'));
    $this->post(route('assessment.invitation.otp', $token), ['email' => $user->email])
        ->assertSessionHasErrors('email')->assertDontSee('SMTP secret details');
    expect($invitation->challenges()->first()->invalidated_at)->not->toBeNull()
        ->and($invitation->deliveries()->first()->status)->toBe('failed');
});

test('separate invitations on one office IP do not share the small OTP quota', function () {
    extract(invitationFixture());
    foreach (range(1, 8) as $number) {
        $otherUser = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT]);
        $otherParticipant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $otherUser->id, 'status' => 'assigned']);
        $otherToken = str_pad((string) $number, 64, 'b');
        AssessmentInvitation::create(['assessment_participant_id' => $otherParticipant->id, 'email' => $otherUser->email, 'token_hash' => hash('sha256', $otherToken), 'valid_from' => now()->startOfDay(), 'expires_at' => now()->addDay()->startOfDay()]);
        $this->post(route('assessment.invitation.otp', $otherToken), ['email' => $otherUser->email])->assertSessionHasNoErrors()->assertSessionHas('success');
    }
    Mail::assertSentCount(8);
});
