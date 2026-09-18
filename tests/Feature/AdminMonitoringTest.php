<?php

// Exercise assessment business rules independently; real invitation/OTP gating is
// covered without middleware bypass in AssessmentInvitationAccessTest.
beforeEach(fn () => $this->withoutMiddleware(\App\Http\Middleware\EnsureAssessmentInvitation::class));

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationReview;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('admin monitors participant simulation and review progress', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PRESENTATION)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'MON-01', 'name' => 'Monitoring Program', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'MON-SIM-01', 'title' => 'Monitoring Presentation', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $programSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'status' => 'assigned']);
    $session = SimulationSession::create(['assessment_program_simulation_id' => $programSimulation->id, 'assessment_participant_id' => $participant->id, 'status' => 'submitted', 'started_at' => now()->subHour(), 'submitted_at' => now()]);
    SimulationReview::create(['simulation_session_id' => $session->id, 'assessor_id' => $assessor->id, 'recommendation' => 'recommended', 'status' => 'submitted', 'reviewed_at' => now()]);

    $this->actingAs($admin)->get(route('admin.monitoring.index'))->assertOk()->assertSee('Monitoring Program');
    $this->actingAs($admin)->get(route('admin.monitoring.show', $program))->assertOk()
        ->assertSee('Simulasi 4 - Presentasi Peserta')->assertSee('Dikumpulkan')->assertSee('Penilaian final')->assertSee('100%');
});

test('non admin cannot access assessment monitoring', function () {
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $this->actingAs($participant)->get(route('admin.monitoring.index'))->assertForbidden();
});
