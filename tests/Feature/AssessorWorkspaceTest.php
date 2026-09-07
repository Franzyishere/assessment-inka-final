<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('assessor sees participants and monitoring only for assigned simulations', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'WORK-01', 'name' => 'Program Asesor', 'status' => 'active', 'created_by' => $admin->id]);
    $assignedScenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'WORK-SIM-01', 'title' => 'Simulasi Milik Asesor', 'status' => 'active', 'created_by' => $admin->id]);
    $hiddenScenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'WORK-SIM-02', 'title' => 'Simulasi Asesor Lain', 'status' => 'active', 'created_by' => $admin->id]);
    $assignedSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $assignedScenario->id, 'status' => 'scheduled']);
    AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $hiddenScenario->id, 'status' => 'scheduled']);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'status' => 'assigned']);
    AssessorAssignment::create(['assessment_program_simulation_id' => $assignedSimulation->id, 'assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);
    SimulationSession::create(['assessment_program_simulation_id' => $assignedSimulation->id, 'assessment_participant_id' => $participant->id, 'status' => 'submitted', 'started_at' => now()->subHour(), 'submitted_at' => now()]);

    $this->actingAs($assessor)->get(route('asesor.participants.index'))
        ->assertOk()
        ->assertSee($participantUser->name)
        ->assertSee('1 / 1');

    $this->actingAs($assessor)->get(route('asesor.monitoring.index'))
        ->assertOk()->assertSee('Program Asesor')
        ->assertViewHas('programs', fn ($programs) => $programs->count() === 1);

    $this->actingAs($assessor)->get(route('asesor.monitoring.program', $program))
        ->assertOk()
        ->assertSee('Simulasi 1 - Problem Analysis')
        ->assertViewHas('simulations', fn ($simulations) => $simulations->count() === 1 && $simulations->first()->is($assignedSimulation))
        ->assertSee('100% peserta telah mengumpulkan');

    $this->actingAs($assessor)->get(route('asesor.simulations.index'))
        ->assertOk()->assertSee('Tidak ada penugasan aktif');
});

test('assessor without assignments sees empty workspace', function () {
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();

    $this->actingAs($assessor)->get(route('asesor.participants.index'))
        ->assertOk()
        ->assertSee('Belum ada peserta');
    $this->actingAs($assessor)->get(route('asesor.monitoring.index'))
        ->assertOk()
        ->assertSee('Program assessment tidak ditemukan');

    $program = AssessmentProgram::create(['name' => 'Tidak Ditugaskan', 'code' => 'DENIED-MON', 'status' => 'active', 'created_by' => $assessor->id]);
    $this->get(route('asesor.monitoring.program', $program))->assertForbidden();
});

test('active assignments are grouped by assessment program', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'GROUP-01', 'name' => 'Program Assessment September', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'GROUP-SIM-01', 'title' => 'Materi Aktif', 'status' => 'active', 'created_by' => $admin->id]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'status' => 'assigned']);
    AssessorAssignment::create(['assessment_program_simulation_id' => $simulation->id, 'assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);

    $newProgram = AssessmentProgram::create(['code' => 'GROUP-02', 'name' => 'Program Assessment Terbaru', 'status' => 'active', 'created_by' => $admin->id]);
    $newScenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'GROUP-SIM-02', 'title' => 'Materi Program Terbaru', 'status' => 'active', 'created_by' => $admin->id]);
    $newSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $newProgram->id, 'simulation_scenario_id' => $newScenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $newProgram->id, 'user_id' => $participantUser->id, 'status' => 'assigned']);
    AssessorAssignment::create(['assessment_program_simulation_id' => $newSimulation->id, 'assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);

    $this->actingAs($assessor)->get(route('asesor.simulations.index'))
        ->assertOk()
        ->assertSee('Daftar Program Assessment')
        ->assertSeeInOrder(['Program Assessment Terbaru', 'Program Assessment September'])
        ->assertDontSee('Materi Aktif');

    $this->actingAs($assessor)->get(route('asesor.simulations.program', $program))
        ->assertOk()
        ->assertSee('Program Assessment September')
        ->assertSee('Simulasi 1')
        ->assertSee('Problem Analysis');
});
