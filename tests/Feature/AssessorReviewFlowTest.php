<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationSubmission;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function submittedSessionForReview(): array
{
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'REVIEW-01', 'name' => 'Review Test', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'REVIEW-SIM-01', 'title' => 'Review Simulation', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $programSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'status' => 'assigned']);
    $session = SimulationSession::create(['assessment_program_simulation_id' => $programSimulation->id, 'assessment_participant_id' => $participant->id, 'status' => 'submitted', 'started_at' => now()->subHour(), 'submitted_at' => now()]);
    SimulationSubmission::create(['simulation_session_id' => $session->id, 'response_text' => json_encode([1 => 'Jawaban peserta']), 'revision' => 1, 'submitted_at' => now()]);
    AssessorAssignment::create(['assessment_program_simulation_id' => $programSimulation->id, 'assessor_id' => $assessor->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);

    return compact('assessor', 'session');
}

test('assigned assessor saves draft and finalizes review', function () {
    ['assessor' => $assessor, 'session' => $session] = submittedSessionForReview();

    $this->actingAs($assessor)->get(route('asesor.reviews.index'))->assertOk()->assertSee('Simulasi 1 - Problem Analysis');
    $this->actingAs($assessor)->put(route('asesor.reviews.update', $session), [
        'status' => 'draft', 'assessment_notes' => 'Catatan sementara',
    ])->assertRedirect(route('asesor.reviews.index'));
    expect($session->reviews()->firstOrFail()->status)->toBe('draft');

    $this->actingAs($assessor)->put(route('asesor.reviews.update', $session), [
        'status' => 'submitted', 'recommendation' => 'recommended_with_development', 'assessment_notes' => 'Perlu pengembangan komunikasi.',
    ])->assertRedirect(route('asesor.reviews.index'));
    expect($session->reviews()->firstOrFail()->status)->toBe('submitted')
        ->and($session->reviews()->firstOrFail()->reviewed_at)->not->toBeNull();

    $this->actingAs($assessor)->put(route('asesor.reviews.update', $session), [
        'status' => 'submitted', 'recommendation' => 'not_recommended',
    ])->assertForbidden();
});

test('unassigned assessor cannot view or review participant submission', function () {
    ['session' => $session] = submittedSessionForReview();
    $other = User::create(['name' => 'Asesor Tidak Ditugaskan', 'email' => 'unauthorized-assessor@example.test', 'role' => User::ROLE_ASESOR, 'password' => 'password']);

    $this->actingAs($other)->get(route('asesor.reviews.edit', $session))->assertForbidden();
    $this->actingAs($other)->put(route('asesor.reviews.update', $session), ['status' => 'draft'])->assertForbidden();
});
