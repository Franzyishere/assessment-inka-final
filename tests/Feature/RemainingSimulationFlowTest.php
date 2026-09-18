<?php

// Exercise assessment business rules independently; real invitation/OTP gating is
// covered without middleware bypass in AssessmentInvitationAccessTest.
beforeEach(fn () => $this->withoutMiddleware(\App\Http\Middleware\EnsureAssessmentInvitation::class));

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationMaterialPage;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function simulationFlowSetup(string $typeCode, string $suffix): array
{
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $type = SimulationType::where('code', $typeCode)->firstOrFail();
    $program = AssessmentProgram::create(['code' => "FLOW-{$suffix}", 'name' => "Program {$suffix}", 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'simulation_package' => $typeCode === SimulationType::CRITICAL_INCIDENT ? 'ci_1' : null, 'code' => "SIM-{$suffix}", 'title' => "Simulasi {$suffix}", 'description' => 'Materi kasus simulasi.', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $programSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'assessment_category' => 'grade_1_to_2', 'status' => 'assigned']);
    AssessorAssignment::create(['assessment_program_simulation_id' => $programSimulation->id, 'assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);

    return compact('participantUser', 'assessor', 'participant', 'programSimulation');
}

test('participant completes critical incident case response', function () {
    ['participantUser' => $participant, 'programSimulation' => $simulation] = simulationFlowSetup(SimulationType::CRITICAL_INCIDENT, 'CASE');
    $this->actingAs($participant)->get(route('peserta-assessment.simulations.show', $simulation))
        ->assertOk()
        ->assertSee('Aktivitas pengerjaan dipantau sistem')
        ->assertSee('perpindahan tab atau keluar dari mode fullscreen');
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $simulation))
        ->assertRedirect(route('peserta-assessment.simulations.case-response', $simulation));
    $this->actingAs($participant)->get(route('peserta-assessment.simulations.case-response', $simulation))->assertOk()->assertSee('Materi kasus simulasi.');
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.case-response.submit', $simulation), ['response' => 'Keputusan dan analisis peserta.'])
        ->assertRedirect(route('peserta-assessment.simulations.index'));
    expect(SimulationSession::firstOrFail()->status)->toBe('submitted')
        ->and(SimulationSession::firstOrFail()->submissions->first()->response_text)->toBe('Keputusan dan analisis peserta.');
});

test('participant final critical incident answer is saved when save and submit is clicked directly', function () {
    ['participantUser' => $participant, 'programSimulation' => $simulation] = simulationFlowSetup(SimulationType::CRITICAL_INCIDENT, 'CASE-MULTI');

    foreach (range(1, 3) as $page) {
        SimulationMaterialPage::create([
            'simulation_scenario_id' => $simulation->simulation_scenario_id,
            'title' => "Materi {$page}",
            'content' => "Kasus {$page}",
            'page_order' => $page,
            'is_required' => true,
        ]);
    }

    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $simulation))
        ->assertRedirect(route('peserta-assessment.simulations.material', [$simulation, 1]));

    foreach ([1 => 'Jawaban pertama', 2 => 'Jawaban kedua'] as $page => $answer) {
        $this->actingAs($participant)->put(
            route('peserta-assessment.simulations.material.save', [$simulation, $page]),
            ['response' => $answer]
        )->assertRedirect(route('peserta-assessment.simulations.material', [$simulation, $page + 1]));
    }

    $this->actingAs($participant)->put(
        route('peserta-assessment.simulations.material.save', [$simulation, 3]),
        ['response' => '<p>Jawaban terakhir langsung dikumpulkan</p>', 'submit_after_save' => '1']
    )->assertRedirect(route('peserta-assessment.simulations.index'));

    $session = SimulationSession::firstOrFail();
    $responses = json_decode($session->submissions()->firstOrFail()->response_text, true);

    expect($session->status)->toBe('submitted')
        ->and($responses[3])->toBe('<p>Jawaban terakhir langsung dikumpulkan</p>');
});

test('participant completes timed lgd then assessor proceeds to review', function () {
    ['participantUser' => $participantUser, 'assessor' => $assessor, 'participant' => $participant, 'programSimulation' => $simulation] = simulationFlowSetup(SimulationType::LGD, 'LGD');
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $problemType = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $problemScenario = SimulationScenario::create(['simulation_type_id' => $problemType->id, 'code' => 'LGD-PA', 'title' => $problemType->name, 'status' => 'active', 'created_by' => $admin->id]);
    $problemSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $simulation->assessment_program_id, 'simulation_scenario_id' => $problemScenario->id, 'status' => 'scheduled']);
    SimulationSession::create(['assessment_program_simulation_id' => $problemSimulation->id, 'assessment_participant_id' => $participant->id, 'status' => 'submitted', 'started_at' => now()->subHour(), 'submitted_at' => now()]);

    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $simulation))
        ->assertRedirect(route('peserta-assessment.simulations.lgd-review', $simulation));
    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.lgd-review.submit', $simulation), ['confirmation' => '1'])
        ->assertRedirect(route('peserta-assessment.simulations.index'));
    $session = SimulationSession::where('assessment_program_simulation_id', $simulation->id)->firstOrFail();
    expect($session->status)->toBe('submitted');
    $this->actingAs($assessor)->get(route('asesor.simulations.show', $simulation))->assertOk()->assertSee('Beri penilaian');
    $this->actingAs($assessor)->get(route('asesor.reviews.edit', $session))->assertOk()->assertSee('Hasil Observasi LGD');
});

test('participant cannot start lgd before problem analysis is submitted', function () {
    ['participantUser' => $participant, 'programSimulation' => $simulation] = simulationFlowSetup(SimulationType::LGD, 'LGD-BLOCK');
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $simulation))->assertStatus(422);
});
