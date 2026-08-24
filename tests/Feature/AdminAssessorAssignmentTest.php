<?php

use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\AuditLog;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function programSimulationForAssignment(): array
{
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'ASSIGN-01', 'name' => 'Program Penugasan', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'ASSIGN-SIM-01', 'title' => 'Simulasi Penugasan', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);

    return compact('admin', 'program', 'simulation');
}

test('admin views centralized assignments and assigns multiple assessors', function () {
    ['admin' => $admin, 'program' => $program, 'simulation' => $simulation] = programSimulationForAssignment();
    $first = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $second = User::create(['name' => 'Asesor Kedua', 'email' => 'asesor.kedua@example.test', 'role' => User::ROLE_ASESOR, 'password' => 'password123']);
    $secondType = SimulationType::where('code', SimulationType::LGD)->firstOrFail();
    $secondScenario = SimulationScenario::create(['simulation_type_id' => $secondType->id, 'code' => 'ASSIGN-SIM-02', 'title' => 'LGD Penugasan', 'status' => 'published', 'created_by' => $admin->id]);
    $secondSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $secondScenario->id, 'status' => 'scheduled']);

    $this->actingAs($admin)->get(route('admin.assessor-assignments.index', ['program' => $program->id]))
        ->assertOk()->assertSee('Program Penugasan')->assertSee('Tim Asesor Program');
    $this->actingAs($admin)->get(route('admin.assessor-assignments.edit', $program))->assertOk()->assertSee('Pilih semua asesor')->assertSee('Tim Asesor Tidak Boleh Kosong')->assertSee('Asesor Kedua');
    $this->actingAs($admin)->put(route('admin.assessor-assignments.update', $program), [
        'assessor_ids' => [$first->id, $second->id],
    ])->assertRedirect(route('admin.assessor-assignments.index'));

    expect(AssessorAssignment::where('assessment_program_simulation_id', $simulation->id)->count())->toBe(2)
        ->and(AssessorAssignment::where('assessment_program_simulation_id', $secondSimulation->id)->count())->toBe(2)
        ->and(AuditLog::where('action', 'assessor_assignment.updated')->exists())->toBeTrue();
});

test('admin cannot assign a non assessor account', function () {
    ['admin' => $admin, 'simulation' => $simulation] = programSimulationForAssignment();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $this->actingAs($admin)->from(route('admin.assessor-assignments.edit', $simulation->program))
        ->put(route('admin.assessor-assignments.update', $simulation->program), ['assessor_ids' => [$participant->id]])
        ->assertSessionHasErrors('assessor_ids.0');
    expect($simulation->assessorAssignments()->count())->toBe(0);
});

test('admin cannot leave the program assessor team empty', function () {
    ['admin' => $admin, 'program' => $program] = programSimulationForAssignment();

    $this->actingAs($admin)
        ->from(route('admin.assessor-assignments.edit', $program))
        ->put(route('admin.assessor-assignments.update', $program), ['assessor_ids' => []])
        ->assertRedirect(route('admin.assessor-assignments.edit', $program))
        ->assertSessionHasErrors('assessor_ids');
});

test('non admin cannot manage assessor assignments', function () {
    ['simulation' => $simulation] = programSimulationForAssignment();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $this->actingAs($assessor)->get(route('admin.assessor-assignments.index'))->assertForbidden();
    $this->actingAs($assessor)->put(route('admin.assessor-assignments.update', $simulation->program), ['assessor_ids' => []])->assertForbidden();
});
