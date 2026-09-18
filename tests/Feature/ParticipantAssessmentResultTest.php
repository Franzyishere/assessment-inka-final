<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationReview;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () { $this->seed(DatabaseSeeder::class); });

test('participant cannot read historic recommendations without an invitation and records are retained', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $otherUser = User::create(['name' => 'Peserta Rahasia', 'email' => 'rahasia@example.test', 'role' => User::ROLE_PESERTA_ASSESSMENT, 'password' => 'password']);
    $program = AssessmentProgram::create(['code' => 'RESULT-01', 'name' => 'Program Hasil', 'status' => 'completed', 'created_by' => $admin->id]);
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'RESULT-SIM', 'title' => $type->name, 'status' => 'active', 'created_by' => $admin->id]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);

    foreach ([[$participantUser, 'Catatan pengembangan peserta.'], [$otherUser, 'CATATAN RAHASIA PESERTA LAIN']] as [$user, $note]) {
        $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $user->id, 'assessment_category' => 'grade_1_to_2', 'status' => 'assigned']);
        $session = SimulationSession::create(['assessment_program_simulation_id' => $simulation->id, 'assessment_participant_id' => $participant->id, 'status' => 'submitted', 'submitted_at' => now()]);
        SimulationReview::create(['simulation_session_id' => $session->id, 'assessor_id' => $assessor->id, 'recommendation' => 'recommended_with_development', 'assessment_notes' => $note, 'status' => 'submitted', 'reviewed_at' => now()]);
    }

    $this->actingAs($participantUser)->get(route('peserta-assessment.results.index'))
        ->assertRedirect(route('login'))->assertDontSee('Catatan pengembangan peserta.')->assertDontSee('CATATAN RAHASIA PESERTA LAIN');
    expect(SimulationReview::count())->toBe(2);
});
