<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('participant cannot access schedule without invitation even when assigned', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $otherParticipant = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT]);
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();

    $ownProgram = AssessmentProgram::create([
        'code' => 'SCHEDULE-OWN',
        'name' => 'Jadwal Assessment Peserta',
        'status' => 'active',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'created_by' => $admin->id,
    ]);
    $otherProgram = AssessmentProgram::create([
        'code' => 'SCHEDULE-OTHER',
        'name' => 'Jadwal Peserta Lain',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);
    $scenario = SimulationScenario::create([
        'simulation_type_id' => $type->id,
        'code' => 'SCHEDULE-PA',
        'title' => 'Problem Analysis Terjadwal',
        'status' => 'active',
        'duration_minutes' => 60,
        'created_by' => $admin->id,
    ]);
    AssessmentProgramSimulation::create([
        'assessment_program_id' => $ownProgram->id,
        'simulation_scenario_id' => $scenario->id,
        'opens_at' => now()->addDay(),
        'closes_at' => now()->addDays(2),
        'status' => 'scheduled',
    ]);
    AssessmentParticipant::create([
        'assessment_program_id' => $ownProgram->id,
        'user_id' => $participant->id,
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);
    AssessmentParticipant::create([
        'assessment_program_id' => $otherProgram->id,
        'user_id' => $otherParticipant->id,
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);

    $this->actingAs($participant)
        ->get(route('peserta-assessment.schedule.index'))
        ->assertRedirect(route('login'))
        ->assertDontSee('Jadwal Peserta Lain');
});

test('non participant cannot access participant assessment schedule', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('peserta-assessment.schedule.index'))
        ->assertForbidden();
});
