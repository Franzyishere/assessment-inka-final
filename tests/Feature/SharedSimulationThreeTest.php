<?php

// Exercise assessment business rules independently; real invitation/OTP gating is
// covered without middleware bypass in AssessmentInvitationAccessTest.
beforeEach(fn () => $this->withoutMiddleware(\App\Http\Middleware\EnsureAssessmentInvitation::class));

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AuditLog;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\User;
use App\Support\SimulationCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

function sharedSimulationThreeSetup(string $category = 'grade_1_to_2'): array
{
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $user = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'SHARED-TEST', 'name' => 'Program Materi Bersama', 'status' => 'active', 'created_by' => $admin->id]);
    $catalog = SimulationCatalog::ensure($admin->id);
    foreach ($catalog as $scenario) {
        if ($scenario->usesSharedSimulationThreeMaterial()) {
            $path = 'simulation-materials/'.$scenario->id.'/test.pdf';
            Storage::disk('local')->put($path, '%PDF-1.4 original '.$scenario->simulation_package);
            $scenario->materialPages()->create(['title' => 'Materi', 'page_order' => 1, 'is_required' => true,
                'attachment_path' => $path, 'attachment_name' => 'test.pdf', 'attachment_mime_type' => 'application/pdf']);
        }
        $simulation = $program->simulations()->create(['simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
        $simulation->assessorAssignments()->create(['assessor_id' => $assessor->id, 'assigned_by' => $admin->id]);
    }
    $participant = $program->participants()->create(['user_id' => $user->id, 'assessment_category' => $category, 'status' => 'assigned']);
    $simulations = $program->simulations()->with('scenario.materialPages')->get()->keyBy(fn ($simulation) => $simulation->scenario->simulation_package ?? $simulation->scenario->type->code);

    return compact('admin', 'assessor', 'user', 'program', 'participant', 'simulations');
}

dataset('assessment levels and types', collect(array_keys(AssessmentParticipant::CATEGORIES))
    ->flatMap(fn ($category) => [[$category, 'ci_long'], [$category, 'ci_short'], [$category, 'in_tray']])->all());

test('admin can select one of three materials for every assessment level', function (string $category, string $package) {
    extract(sharedSimulationThreeSetup($category));
    expect($participant->requiresSimulationThreeChoice())->toBeTrue();
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id], 'participant_categories' => [$user->id => $category],
        'participant_simulation_three_choices' => [$user->id => $package], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($participant->fresh()->simulationThreePackageKey())->toBe($package)
        ->and(AuditLog::where('action', 'admin.simulation_three_choice.updated')->count())->toBe(1);
    $chosen = $program->simulations()->with('scenario.materialPages')->get()->first(fn ($simulation) => $simulation->scenario->simulation_package === $package);
    $other = $program->simulations()->with('scenario.materialPages')->get()->first(fn ($simulation) => $simulation->scenario->type->delivery_mode === 'case_response' && $simulation->scenario->simulation_package !== $package);
    $this->actingAs($user)->get(route('peserta-assessment.simulations.index'))->assertOk()
        ->assertViewHas('participations', fn ($items) => $items->first()->program->simulations->count() === 4
            && $items->first()->program->simulations->contains('id', $chosen->id)
            && ! $items->first()->program->simulations->contains('id', $other->id));
    $this->get(route('peserta-assessment.simulations.material.pdf', [$other, $other->scenario->materialPages->first()]))->assertNotFound();
    $this->post(route('peserta-assessment.simulations.start', $chosen))->assertRedirect(route('peserta-assessment.simulations.material', [$chosen, 1]));
    $this->get(route('peserta-assessment.simulations.material', [$chosen, 1]))->assertOk()->assertSee('Simpan & Kumpulkan');
    $this->put(route('peserta-assessment.simulations.material.save', [$chosen, 1]), ['response' => '<p>Jawaban final.</p>', 'submit_after_save' => 1])
        ->assertSessionHasNoErrors()->assertRedirect(route('peserta-assessment.simulations.index'));
    expect(SimulationSession::firstOrFail()->status)->toBe('submitted');
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id], 'participant_categories' => [$user->id => $category],
        'participant_simulation_three_choices' => [$user->id => $other->scenario->simulation_package], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasErrors("participant_simulation_three_choices.{$user->id}");
})->with('assessment levels and types');

test('matrix is only a suggestion and does not authorize a participant to start or read pdf', function () {
    extract(sharedSimulationThreeSetup('promotion_m'));
    expect($participant->suggestedSimulationThreePackage())->toBe('in_tray')
        ->and($participant->simulationThreePackageKey())->toBeNull();
    $this->actingAs($assessor)->get(route('asesor.participants.index'))->assertOk()->assertSee('Belum ditetapkan admin')->assertDontSee('name="simulation_package"', false);
    $this->actingAs($user)->get(route('peserta-assessment.simulations.index'))->assertOk()
        ->assertViewHas('participations', fn ($items) => $items->first()->program->simulations->count() === 4);
    $this->get(route('peserta-assessment.dashboard'))->assertRedirect(route('peserta-assessment.simulations.index'));
    $this->get(route('peserta-assessment.schedule.index'))->assertRedirect(route('peserta-assessment.simulations.index'));
    foreach (['ci_long', 'ci_short', 'in_tray'] as $package) {
        $simulation = $simulations[$package];
        $this->post(route('peserta-assessment.simulations.start', $simulation))->assertStatus(422);
        $this->get(route('peserta-assessment.simulations.material.pdf', [$simulation, $simulation->scenario->materialPages->first()]))->assertForbidden();
    }
    expect(SimulationSession::count())->toBe(0);
});

test('selection validates role assignment type active participant and material readiness', function () {
    extract(sharedSimulationThreeSetup());
    $url = route('admin.assessment-programs.setup.update', $program);
    $payload = ['participant_ids' => [$user->id], 'participant_categories' => [$user->id => 'grade_1_to_2'],
        'participant_simulation_three_choices' => [$user->id => 'ci_long'], 'assessor_ids' => [$assessor->id]];
    $this->actingAs($user)->putJson($url, $payload)->assertForbidden();
    $this->actingAs($assessor)->putJson($url, $payload)->assertForbidden();
    $this->actingAs($admin)->putJson($url, array_replace_recursive($payload, ['participant_simulation_three_choices' => [$user->id => 'invalid']]))
        ->assertUnprocessable()->assertJsonValidationErrors("participant_simulation_three_choices.{$user->id}");
    Storage::disk('local')->delete($simulations['ci_long']->scenario->materialPages->first()->attachment_path);
    $this->putJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors("participant_simulation_three_choices.{$user->id}");
    expect($participant->fresh()->simulation_three_choice)->toBeNull();
});

test('choice locks on start and a resumed session retains its deadline', function () {
    extract(sharedSimulationThreeSetup());
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id], 'participant_categories' => [$user->id => 'grade_1_to_2'],
        'participant_simulation_three_choices' => [$user->id => 'ci_long'], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasNoErrors();
    $chosen = $program->simulations()->with('scenario')->get()->first(fn ($simulation) => $simulation->scenario->simulation_package === 'ci_long');
    $this->actingAs($user)->post(route('peserta-assessment.simulations.start', $chosen));
    $session = SimulationSession::firstOrFail();
    $deadline = $session->expires_at->toIso8601String();
    $this->travel(5)->minutes();
    $this->post(route('peserta-assessment.simulations.start', $chosen))->assertRedirect();
    expect($session->fresh()->expires_at->toIso8601String())->toBe($deadline)->and(SimulationSession::count())->toBe(1);
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id], 'participant_categories' => [$user->id => 'grade_1_to_2'],
        'participant_simulation_three_choices' => [$user->id => 'in_tray'], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasErrors("participant_simulation_three_choices.{$user->id}");
});

test('shared bank requires exactly one pdf and preserves an assigned material version', function () {
    extract(sharedSimulationThreeSetup());
    $scenario = $simulations['ci_long']->scenario;
    $material = $scenario->materialPages->first();
    $payload = ['simulation_type_id' => $scenario->simulation_type_id, 'status' => 'active', 'duration_minutes' => 45];
    $this->actingAs($admin)->putJson(route('admin.simulations.update', $scenario), $payload + ['material_pages' => []])->assertUnprocessable();
    $this->putJson(route('admin.simulations.update', $scenario), $payload + ['material_pages' => [
        ['id' => $material->id, 'title' => 'Materi'], ['id' => $material->id, 'title' => 'Materi kedua'],
    ]])->assertUnprocessable();
    $this->put(route('admin.simulations.update', $scenario), $payload + ['material_pages' => [[
        'id' => $material->id, 'title' => 'Materi', 'is_required' => false,
        'attachment' => UploadedFile::fake()->create('new.pdf', 20, 'application/pdf'),
    ]]])->assertSessionHasNoErrors()->assertRedirect();
    $latest = SimulationCatalog::ensure($admin->id)->firstWhere('simulation_package', 'ci_long');
    expect($latest->id)->not->toBe($scenario->id)
        ->and($latest->materialPages()->count())->toBe(1)
        ->and($latest->materialPages->first()->is_required)->toBeTrue()
        ->and($simulations['ci_long']->fresh()->simulation_scenario_id)->toBe($scenario->id)
        ->and($material->fresh()->attachment_path)->toBe($material->attachment_path);
    Storage::disk('local')->assertExists($material->attachment_path);
    $this->get(route('admin.simulations.index'))->assertOk()->assertDontSee('Critical Incident 1');
});

test('a new material version can retain its pdf without deleting the previous file', function () {
    extract(sharedSimulationThreeSetup());
    $scenario = $simulations['in_tray']->scenario;
    $material = $scenario->materialPages->first();
    $this->actingAs($admin)->put(route('admin.simulations.update', $scenario), [
        'simulation_type_id' => $scenario->simulation_type_id, 'status' => 'active', 'duration_minutes' => 80,
        'material_pages' => [['id' => $material->id, 'title' => 'Materi']],
    ])->assertSessionHasNoErrors()->assertRedirect();
    $latest = SimulationCatalog::ensure($admin->id)->firstWhere('simulation_package', 'in_tray');
    $copied = $latest->materialPages->first();
    expect($copied->attachment_path)->not->toBe($material->attachment_path)
        ->and(Storage::disk('local')->get($copied->attachment_path))->toBe(Storage::disk('local')->get($material->attachment_path));
});

test('program setup preserves historical simulation three sessions and materials', function () {
    extract(sharedSimulationThreeSetup());
    $legacy = $simulations['ci_long']->scenario;
    $legacy->update(['simulation_package' => 'ci_1', 'code' => 'LEGACY-CI-1']);
    // Reproduce a historical program with only an old CI package assigned.
    $simulations['in_tray']->delete();
    $simulations['ci_short']->delete();
    $session = SimulationSession::create(['assessment_program_simulation_id' => $simulations['ci_long']->id,
        'assessment_participant_id' => $participant->id, 'status' => 'submitted', 'submitted_at' => now()]);
    $session->submissions()->create(['response_text' => '{"1":"Historical answer"}', 'revision' => 1]);
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id], 'participant_categories' => [$user->id => 'grade_1_to_2'], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($session->fresh()->submissions->first()->response_text)->toBe('{"1":"Historical answer"}')
        ->and($program->fresh()->usesSharedSimulationThree())->toBeFalse()
        ->and($participant->fresh()->simulationThreePackageKey())->toBe('ci_1');
    Storage::disk('local')->assertExists($legacy->materialPages->first()->attachment_path);
});

test('started programs retain the previous shared CI and In-Tray materials', function () {
    extract(sharedSimulationThreeSetup());
    $simulations['ci_long']->scenario->update(['simulation_package' => 'ci', 'code' => 'LEGACY-SHARED-CI']);
    $simulations['ci_short']->delete();
    $participant->update(['simulation_three_choice' => 'ci', 'simulation_three_chosen_at' => now()]);
    SimulationSession::create([
        'assessment_program_simulation_id' => $simulations['ci_long']->id,
        'assessment_participant_id' => $participant->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id],
        'participant_categories' => [$user->id => 'grade_1_to_2'],
        'participant_simulation_three_choices' => [$user->id => 'ci'],
        'assessor_ids' => [$assessor->id],
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($participant->fresh()->simulationThreePackageKey())->toBe('ci')
        ->and($simulations['ci_long']->fresh()->scenario->simulation_package)->toBe('ci');
});

test('saving setup upgrades an unstarted legacy program and clears obsolete choice', function () {
    extract(sharedSimulationThreeSetup(AssessmentParticipant::MADYA_CATEGORY));
    $simulations['ci_long']->scenario->update(['simulation_package' => 'ci_3', 'code' => 'LEGACY-CI-3']);
    $simulations['in_tray']->scenario->update(['simulation_package' => 'in_tray_3', 'code' => 'LEGACY-INTRAY-3']);
    $simulations['ci_short']->delete();
    $participant->update(['simulation_three_choice' => 'ci_3', 'simulation_three_chosen_at' => now()]);
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id], 'participant_categories' => [$user->id => AssessmentParticipant::MADYA_CATEGORY], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($program->fresh()->usesSharedSimulationThree())->toBeTrue()
        ->and($participant->fresh()->simulation_three_choice)->toBeNull()
        ->and($participant->fresh()->requiresSimulationThreeChoice())->toBeTrue();
    expect(SimulationScenario::whereIn('simulation_package', ['ci_3', 'in_tray_3'])->count())->toBe(2);
});
