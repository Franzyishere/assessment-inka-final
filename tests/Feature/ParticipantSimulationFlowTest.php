<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationMaterialPage;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationSessionEvent;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function assignedProblemAnalysis(): array
{
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'FLOW-01', 'name' => 'Flow Test', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'FLOW-SIM-01', 'title' => 'Problem Analysis Flow', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    foreach (range(1, 2) as $page) {
        SimulationMaterialPage::create(['simulation_scenario_id' => $scenario->id, 'title' => "Materi {$page}", 'content' => "Konten {$page}", 'page_order' => $page, 'is_required' => true]);
    }
    $programSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participant->id, 'status' => 'assigned', 'assigned_at' => now()]);

    return compact('participant', 'programSimulation');
}

test('assigned participant can start save and submit problem analysis', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();

    $this->actingAs($participant)->get(route('peserta-assessment.simulations.index'))->assertOk()->assertSee('Simulasi 1 - Problem Analysis');
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation))
        ->assertRedirect(route('peserta-assessment.simulations.material', [$programSimulation, 1]));
    $this->actingAs($participant)->get(route('peserta-assessment.simulations.material', [$programSimulation, 1]))->assertOk()->assertSee('Materi 1');
    $this->actingAs($participant)->put(route('peserta-assessment.simulations.material.save', [$programSimulation, 1]), ['response' => 'Jawaban halaman pertama'])->assertRedirect();
    $this->actingAs($participant)->put(route('peserta-assessment.simulations.material.save', [$programSimulation, 2]), ['response' => 'Jawaban halaman kedua'])->assertRedirect();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.submit', $programSimulation))->assertRedirect(route('peserta-assessment.simulations.index'));

    expect(SimulationSession::firstOrFail()->status)->toBe('submitted')
        ->and(SimulationSession::firstOrFail()->submissions->first()->response_text)->toContain('Jawaban halaman pertama');
});

test('participant cannot submit while a required material page is unanswered', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();

    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));
    $this->actingAs($participant)->put(
        route('peserta-assessment.simulations.material.save', [$programSimulation, 1]),
        ['response' => 'Jawaban halaman pertama']
    );

    $this->actingAs($participant)
        ->post(route('peserta-assessment.simulations.submit', $programSimulation))
        ->assertRedirect(route('peserta-assessment.simulations.material', [$programSimulation, 2]))
        ->assertSessionHasErrors('response');

    expect(SimulationSession::firstOrFail()->status)->toBe('in_progress');
});

test('participant cannot start simulation from an inactive program', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $programSimulation->program()->update(['status' => 'archived']);

    $this->actingAs($participant)
        ->post(route('peserta-assessment.simulations.start', $programSimulation))
        ->assertForbidden();

    expect(SimulationSession::count())->toBe(0);
});

test('participant cannot access simulation from another program', function () {
    ['programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $otherParticipant = User::create(['name' => 'Peserta Lain', 'email' => 'other@example.test', 'role' => User::ROLE_PESERTA_ASSESSMENT, 'password' => 'password']);

    $this->actingAs($otherParticipant)->get(route('peserta-assessment.simulations.show', $programSimulation))->assertNotFound();
    $this->actingAs($otherParticipant)->post(route('peserta-assessment.simulations.start', $programSimulation))->assertNotFound();
});

test('active participant session records supported anti cheat events', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));

    $this->actingAs($participant)->postJson(route('peserta-assessment.simulations.events.store', $programSimulation), [
        'event_type' => 'fullscreen_exit', 'client_time' => now()->toISOString(),
    ])->assertCreated()->assertJson(['recorded' => true]);
    expect(SimulationSessionEvent::firstOrFail()->event_type)->toBe('fullscreen_exit');

    $this->actingAs($participant)->postJson(route('peserta-assessment.simulations.events.store', $programSimulation), [
        'event_type' => 'tab_hidden', 'client_time' => now()->toISOString(), 'visibility_state' => 'hidden',
    ])->assertCreated()->assertJson(['recorded' => true, 'violation_count' => 2]);
    expect(SimulationSessionEvent::where('event_type', 'tab_hidden')->exists())->toBeTrue();

    $this->actingAs($participant)->postJson(route('peserta-assessment.simulations.events.store', $programSimulation), [
        'event_type' => 'unsupported_event',
    ])->assertUnprocessable();
});

test('single material uses one save and submit action then disappears from active list', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $programSimulation->scenario->materialPages()->where('page_order', 2)->delete();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));

    $this->actingAs($participant)->put(route('peserta-assessment.simulations.material.save', [$programSimulation, 1]), [
        'response' => 'Jawaban final materi tunggal',
        'submit_after_save' => '1',
    ])->assertRedirect(route('peserta-assessment.simulations.index'));

    expect(SimulationSession::firstOrFail()->status)->toBe('submitted');
    $this->actingAs($participant)->get(route('peserta-assessment.simulations.index'))
        ->assertOk()->assertSee('Tidak ada simulasi aktif');
});

test('participant can save an intermediate material without a page reload', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));

    $this->actingAs($participant)->putJson(
        route('peserta-assessment.simulations.material.save', [$programSimulation, 1]),
        ['response' => '<p>Jawaban materi pertama</p>']
    )->assertOk()->assertJson([
        'message' => 'Jawaban tersimpan.',
        'next_page' => 2,
    ]);

    expect(SimulationSession::firstOrFail()->submissions()->firstOrFail()->response_text)
        ->toContain('Jawaban materi pertama');
});

test('participant simulation list hides programs and simulations after their execution time ends', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $programSimulation->program()->update(['ends_at' => now()->subMinute()]);

    $this->actingAs($participant)->get(route('peserta-assessment.simulations.index'))
        ->assertOk()
        ->assertDontSee('Flow Test')
        ->assertSee('Belum ada program assessment');

    $programSimulation->program()->update(['ends_at' => now()->addHour()]);
    $programSimulation->update(['closes_at' => now()->subMinute()]);

    $this->actingAs($participant)->get(route('peserta-assessment.simulations.index'))
        ->assertOk()
        ->assertDontSee('Problem Analysis Flow')
        ->assertSee('Tidak ada simulasi aktif');
});
