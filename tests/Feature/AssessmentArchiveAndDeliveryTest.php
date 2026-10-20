<?php

use App\Jobs\SendAssessmentInvitation;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 24)->setTime(9, 0));
    config(['assessment_access.mailer' => 'smtp']);
    Mail::fake();
    Queue::fake();
    Storage::fake('local');
});

function archiveDeliveryFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'peserta_assessment']);
    $program = AssessmentProgram::create(['code' => 'ARCHIVE-TEST', 'name' => 'Program Arsip Uji', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->setTime(17, 0), 'created_by' => $admin->id, 'auto_send_invitations' => true]);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $user->id, 'status' => 'assigned', 'assessment_category' => 'grade_1_to_2']);
    $type = SimulationType::create(['code' => SimulationType::PROBLEM_ANALYSIS, 'name' => 'Problem Analysis', 'sequence' => 1, 'delivery_mode' => 'multi_page_response']);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'ARCHIVE-PA', 'title' => 'PA', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    $session = $simulation->sessions()->create(['assessment_participant_id' => $participant->id, 'status' => 'submitted', 'submitted_at' => now()]);
    $submission = $session->submissions()->create(['revision' => 1, 'response_text' => json_encode(['1' => '<p>Jawaban arsip tetap ada</p>']), 'submitted_at' => now()]);

    return compact('admin', 'user', 'program', 'participant', 'simulation', 'session', 'submission');
}

test('archiving keeps answers reviews files and invitations while hiding operational program', function () {
    extract(archiveDeliveryFixture());
    $assessor = User::factory()->create(['role' => 'asesor']);
    $session->reviews()->create(['assessor_id' => $assessor->id, 'status' => 'submitted', 'recommendation' => 'recommended', 'assessment_notes' => 'Catatan tetap ada']);
    Storage::disk('local')->put('archive/presentation.pdf', '%PDF-1.4 example');
    $file = $session->submissions()->create(['revision' => 2, 'storage_path' => 'archive/presentation.pdf', 'original_filename' => 'presentation.pdf', 'mime_type' => 'application/pdf']);
    $program->update(['status' => 'completed']);
    $this->actingAs($admin)->delete(route('admin.assessment-programs.destroy', $program), ['archive_confirmation' => '1'])->assertSessionHas('success');
    expect($program->fresh()->archived_at)->not->toBeNull();
    expect($session->fresh())->not->toBeNull()
        ->and($submission->fresh()->response_text)->toContain('Jawaban arsip tetap ada')
        ->and($session->reviews()->count())->toBe(1);
    $this->get(route('admin.assessment-programs.index'))->assertDontSee('Program Arsip Uji');
    $this->get(route('admin.result-archives.index'))->assertOk()->assertSee('Program Arsip Uji');
    $this->get(route('admin.result-archives.show', $program->id))->assertOk()->assertSee('Jawaban arsip tetap ada')->assertSee('Catatan tetap ada');
    $this->get(route('admin.result-archives.file', [$program->id, $file]))->assertDownload('presentation.pdf');
    $this->get(route('admin.assessment-programs.edit', $program->id))->assertOk();
    Storage::disk('local')->assertExists('archive/presentation.pdf');
});

test('archive restricts roles scopes files and supports search', function () {
    extract(archiveDeliveryFixture());
    $this->actingAs($admin)->get(route('admin.result-archives.index', ['search' => 'not-found']))->assertDontSee('Program Arsip Uji');
    $this->get(route('admin.result-archives.show', [$program->id, 'search' => 'not-found']))->assertDontSee($user->email);
    $other = AssessmentProgram::create(['code' => 'OTHER', 'name' => 'Other', 'status' => 'completed', 'created_by' => $admin->id]);
    $this->get(route('admin.result-archives.file', [$other->id, $submission]))->assertNotFound();
    $super = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($super)->get(route('admin.result-archives.show', $program->id))->assertOk();
    $assessor = User::factory()->create(['role' => 'asesor']);
    $this->actingAs($assessor)->get(route('admin.result-archives.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.result-archives.index'))->assertRedirect(route('login'));
});

test('archived program remains in assessor assessment and monitoring with read only results', function () {
    extract(archiveDeliveryFixture());
    $assessor = User::factory()->create(['role' => 'asesor']);
    $simulation->assessorAssignments()->create(['assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);
    $session->archiveGrant()->create(['assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);
    $program->update(['status' => 'completed']);
    $this->actingAs($admin)->delete(route('admin.assessment-programs.destroy', $program), ['archive_confirmation' => '1'])->assertSessionHas('success');
    $this->actingAs($assessor)->get(route('asesor.reviews.index'))->assertOk()->assertSee('Program Arsip Uji');
    $this->get(route('asesor.reviews.program', $program))->assertOk()->assertSee($user->name);
    $this->get(route('asesor.monitoring.index'))->assertOk()->assertSee('Program Arsip Uji');
    $this->get(route('asesor.monitoring.program', $program))->assertOk();
    $this->get(route('asesor.reviews.edit', $session))->assertOk();
    $this->put(route('asesor.reviews.update', $session), [
        'status' => 'draft', 'assessment_notes' => 'Tidak boleh disimpan',
    ])->assertForbidden();
    expect($session->reviews()->count())->toBe(0);
    $otherAssessor = User::factory()->create(['role' => 'asesor']);
    $this->actingAs($otherAssessor)->get(route('asesor.reviews.edit', $session))->assertForbidden();
});

test('archive shares program table actions and separates main list without changing program state', function () {
    extract(archiveDeliveryFixture());
    $this->actingAs($admin)->get(route('admin.result-archives.index'))->assertDontSee('Program Arsip Uji');
    $program->update(['status' => 'draft', 'starts_at' => now()->addDays(2)]);
    $this->delete(route('admin.assessment-programs.destroy', $program), ['archive_confirmation' => '1'])->assertSessionHas('success');
    expect($program->fresh()->status)->toBe('draft');
    $this->get(route('admin.result-archives.index'))->assertOk()
        ->assertViewIs('pages.admin.assessment-programs.index')
        ->assertSee('Program Arsip Uji')->assertSee('Periode')->assertSee('Simulasi')
        ->assertSee(route('admin.assessment-programs.setup.edit', $program), false)
        ->assertSee(route('admin.assessment-programs.edit', $program), false)
        ->assertDontSee('Buat Program');
    $this->get(route('admin.assessment-programs.index'))->assertDontSee('Program Arsip Uji');
    $this->put(route('admin.assessment-programs.update', $program), [
        'name' => 'Program Arsip Diperbarui', 'status' => 'draft', 'starts_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'ends_at' => now()->addDays(3)->addHour()->format('Y-m-d H:i:s'),
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.result-archives.index'));
});
