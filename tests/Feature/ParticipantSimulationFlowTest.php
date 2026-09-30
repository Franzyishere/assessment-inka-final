<?php

use App\Http\Middleware\EnsureAssessmentInvitation;
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

// Real invitation/OTP gating is tested separately without middleware bypass.
beforeEach(fn () => $this->withoutMiddleware(EnsureAssessmentInvitation::class));

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function assignedProblemAnalysis(string $status = AssessmentProgramSimulation::STATUS_IN_PROGRESS): array
{
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'FLOW-01', 'name' => 'Flow Test', 'status' => 'active', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'FLOW-SIM-01', 'title' => 'Problem Analysis Flow', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    foreach (range(1, 2) as $page) {
        SimulationMaterialPage::create(['simulation_scenario_id' => $scenario->id, 'title' => "Materi {$page}", 'content' => "Konten {$page}", 'page_order' => $page, 'is_required' => true]);
    }
    $programSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => $status]);
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

test('material highlights persist per session and material and reject unauthorized or invalid changes', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));
    $materials = $programSimulation->scenario->materialPages;
    $url = route('peserta-assessment.simulations.material.highlights', [$programSimulation, $materials->first()]);
    $marks = [['page' => 1, 'x' => .1, 'y' => .2, 'width' => .3, 'height' => .02]];
    $this->putJson($url, ['highlights' => $marks])->assertOk();
    $this->getJson($url)->assertOk()->assertJson(['highlights' => $marks]);
    $this->getJson(route('peserta-assessment.simulations.material.highlights', [$programSimulation, $materials->last()]))
        ->assertOk()->assertJson(['highlights' => []]);
    $this->putJson($url, ['highlights' => [['page' => 0, 'x' => -1]]])->assertUnprocessable();
    $other = User::create(['name' => 'Other', 'email' => 'highlight-other@example.test', 'role' => User::ROLE_PESERTA_ASSESSMENT, 'password' => 'password']);
    $this->actingAs($other)->getJson($url)->assertNotFound();
    $this->actingAs($other)->putJson($url, ['highlights' => []])->assertNotFound();
    $this->actingAs($participant)->putJson($url, ['highlights' => []])->assertOk();
    $this->getJson($url)->assertJson(['highlights' => []]);
    SimulationSession::query()->update(['status' => 'submitted']);
    $this->putJson($url, ['highlights' => $marks])->assertForbidden();
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

test('diagrams survive save reload and final submission without a separate save', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));
    $diagram = [['type' => 'arrow', 'points' => [[10, 20], [300, 100]]], ['type' => 'text', 'points' => [[20, 40]], 'text' => 'Penyebab']];
    $this->putJson(route('peserta-assessment.simulations.material.save', [$programSimulation, 1]), [
        'response' => 'Analisis pertama', 'diagram' => json_encode($diagram),
    ])->assertOk();
    $this->get(route('peserta-assessment.simulations.material', [$programSimulation, 1]))
        ->assertOk()->assertSee('data-insert-shape', false)->assertViewHas('diagrams', fn ($diagrams) => $diagrams[1] === $diagram);
    $this->put(route('peserta-assessment.simulations.material.save', [$programSimulation, 2]), [
        'response' => 'Analisis terakhir', 'diagram' => json_encode($diagram), 'submit_after_save' => 1,
    ])->assertSessionHasNoErrors();
    $submission = SimulationSession::firstOrFail()->submissions()->firstOrFail();
    expect($submission->diagrams[1])->toBe($diagram)->and($submission->diagrams[2])->toBe($diagram);
    expect(SimulationSession::firstOrFail()->status)->toBe('submitted');
    $this->putJson(route('peserta-assessment.simulations.material.save', [$programSimulation, 2]), [
        'response' => 'Ubah', 'diagram' => '[]',
    ])->assertForbidden();
});

test('invalid diagram is rejected without saving and diagrams cannot bypass participant ownership', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));
    foreach (['invalid', '{"type":"line"}', '[{"type":"script","points":[[0,0]]}]', '[{"type":"line","points":[[0,0],[2000,0]]}]'] as $json) {
        $this->putJson(route('peserta-assessment.simulations.material.save', [$programSimulation, 1]), [
            'response' => 'Analisis', 'diagram' => $json,
        ])->assertUnprocessable()->assertJsonValidationErrors('diagram');
    }
    expect(SimulationSession::firstOrFail()->submissions()->count())->toBe(0);
    $other = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT]);
    $this->actingAs($other)->putJson(route('peserta-assessment.simulations.material.save', [$programSimulation, 1]), [
        'response' => 'Analisis', 'diagram' => '[]',
    ])->assertNotFound();
});

test('embedded shapes are valid answers and replace legacy diagrams on final submission', function () {
    ['participant' => $participant, 'programSimulation' => $programSimulation] = assignedProblemAnalysis();
    $programSimulation->scenario->materialPages()->where('page_order', 2)->delete();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $programSimulation));
    $scene = [['type' => 'text', 'points' => [[100, 100]], 'width' => 220, 'height' => 100, 'text' => 'Penyebab utama']];
    $answer = '<span data-answer-scene="'.htmlspecialchars(json_encode($scene), ENT_QUOTES).'"></span>';
    $this->put(route('peserta-assessment.simulations.material.save', [$programSimulation, 1]), [
        'response' => $answer, 'diagram' => '[]', 'submit_after_save' => 1,
    ])->assertSessionHasNoErrors()->assertRedirect(route('peserta-assessment.simulations.index'));
    $submission = SimulationSession::firstOrFail()->submissions()->firstOrFail();
    expect($submission->response_text)->toContain('data-answer-scene', 'Penyebab utama');
    expect($submission->diagrams[1])->toBe([]);
    expect(SimulationSession::firstOrFail()->status)->toBe('submitted');
});

test('autosave persists partial drafts without submitting and restores them on reload', function () {
    ['participant' => $participant, 'programSimulation' => $simulation] = assignedProblemAnalysis();
    $this->actingAs($participant)->post(route('peserta-assessment.simulations.start', $simulation));
    $url = route('peserta-assessment.simulations.material.save', [$simulation, 1]);
    $this->putJson($url, ['response' => '', 'draft_only' => true])->assertOk()->assertJson(['draft_saved' => true]);
    $this->putJson($url, ['response' => '<p>Draft belum selesai</p>', 'draft_only' => true])->assertOk();
    expect(SimulationSession::firstOrFail()->status)->toBe('in_progress');
    expect(SimulationSession::firstOrFail()->submissions()->firstOrFail()->submitted_at)->toBeNull();
    $this->get(route('peserta-assessment.simulations.material', [$simulation, 1]))->assertOk()->assertViewHas('responses', fn ($responses) => $responses[1] === '<p>Draft belum selesai</p>');
    SimulationSession::firstOrFail()->update(['status' => 'submitted']);
    $this->putJson($url, ['response' => 'terlambat', 'draft_only' => true])->assertForbidden();
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

test('participant simulations are ordered strictly by simulation type sequence 1, 2, 3, 4', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();

    $program = AssessmentProgram::create([
        'code' => 'FLOW-ORDER',
        'name' => 'Order Test',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);

    $type1 = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $type2 = SimulationType::where('code', SimulationType::LGD)->firstOrFail();
    $type3 = SimulationType::where('code', SimulationType::CRITICAL_INCIDENT)->firstOrFail();
    $type4 = SimulationType::where('code', SimulationType::PRESENTATION)->firstOrFail();

    $scenario1 = SimulationScenario::create(['simulation_type_id' => $type1->id, 'code' => 'O-1', 'title' => 'Sim 1', 'status' => 'published', 'created_by' => $admin->id]);
    $scenario2 = SimulationScenario::create(['simulation_type_id' => $type2->id, 'code' => 'O-2', 'title' => 'Sim 2', 'status' => 'published', 'created_by' => $admin->id]);
    $scenario4 = SimulationScenario::create(['simulation_type_id' => $type4->id, 'code' => 'O-4', 'title' => 'Sim 4', 'status' => 'published', 'created_by' => $admin->id]);
    $scenario3 = SimulationScenario::create(['simulation_type_id' => $type3->id, 'code' => 'O-3', 'title' => 'Sim 3', 'simulation_package' => 'ci_1', 'status' => 'published', 'created_by' => $admin->id]);

    AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario1->id, 'status' => 'scheduled']);
    AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario2->id, 'status' => 'scheduled']);
    AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario4->id, 'status' => 'scheduled']);
    AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario3->id, 'status' => 'scheduled']);

    AssessmentParticipant::create([
        'assessment_program_id' => $program->id,
        'user_id' => $participant->id,
        'assessment_category' => 'grade_1_to_2',
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);

    $response = $this->actingAs($participant)->get(route('peserta-assessment.simulations.index'))->assertOk();

    $participations = $response->viewData('participations');
    $orderTestParticipation = $participations->firstWhere('assessment_program_id', $program->id);
    $sequences = $orderTestParticipation->program->simulations->pluck('scenario.type.sequence')->all();

    expect($sequences)->toBe([1, 2, 3, 4]);
});
