<?php

// Exercise assessment business rules independently; real invitation/OTP gating is
// covered without middleware bypass in AssessmentInvitationAccessTest.
beforeEach(fn () => $this->withoutMiddleware(\App\Http\Middleware\EnsureAssessmentInvitation::class));

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationScenario;
use App\Models\SimulationSubmission;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

test('participant uploads presentation and assigned assessor can download it', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PRESENTATION)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'PRES-01', 'name' => 'Presentation Test', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'PRES-SIM-01', 'title' => 'Presentasi Strategi', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $programSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participant->id, 'status' => 'assigned']);
    AssessorAssignment::create(['assessment_program_simulation_id' => $programSimulation->id, 'assessor_id' => $assessor->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);

    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation))
        ->assertRedirect(route('peserta-assessment.simulations.presentation', $programSimulation));
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.presentation.submit', $programSimulation), [
        'presentation' => UploadedFile::fake()->create('presentasi.pdf', 500, 'application/pdf'),
    ])->assertRedirect(route('peserta-assessment.simulations.index'));

    $submission = SimulationSubmission::firstOrFail();
    Storage::disk('local')->assertExists($submission->storage_path);
    expect($submission->session->status)->toBe('submitted')->and($submission->file_checksum)->toHaveLength(64);
    $this->actingAs($assessor)->get(route('asesor.simulations.show', $programSimulation))->assertOk()->assertSee('presentasi.pdf');
    $this->actingAs($assessor)->get(route('asesor.submissions.preview', $submission))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($assessor)->get(route('asesor.submissions.download', $submission))->assertDownload('presentasi.pdf');

    $otherAssessor = User::create(['name' => 'Asesor Lain', 'email' => 'assessor2@example.test', 'role' => User::ROLE_ASESOR, 'password' => 'password']);
    $this->actingAs($otherAssessor)->get(route('asesor.submissions.download', $submission))->assertForbidden();
    $this->actingAs($otherAssessor)->get(route('asesor.submissions.preview', $submission))->assertForbidden();
});

test('presentation submission rejects non pdf files', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PRESENTATION)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'PRES-PDF', 'name' => 'PDF Only', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'PRES-PDF-SIM', 'title' => $type->name, 'duration_minutes' => 60, 'status' => 'active', 'created_by' => $admin->id]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participant->id, 'status' => 'assigned']);
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $simulation));

    $this->actingAs($participant)->post(route('peserta-assessment.simulations.presentation.submit', $simulation), [
        'presentation' => UploadedFile::fake()->create('presentasi.pptx', 500, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
    ])->assertSessionHasErrors('presentation');
});
