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
use App\Support\SimulationCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

test('lgd reviews submitted problem analysis with timer and participant submission', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'REV-LGD', 'name' => 'Revisi LGD', 'status' => 'active', 'created_by' => $admin->id]);
    $problemType = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $lgdType = SimulationType::where('code', SimulationType::LGD)->firstOrFail();
    $problemScenario = SimulationScenario::create(['simulation_type_id' => $problemType->id, 'code' => 'REV-PA', 'title' => $problemType->name, 'duration_minutes' => 60, 'status' => 'active', 'created_by' => $admin->id]);
    $lgdScenario = SimulationScenario::create(['simulation_type_id' => $lgdType->id, 'code' => 'REV-LGD-SIM', 'title' => $lgdType->name, 'status' => 'active', 'created_by' => $admin->id]);
    Storage::disk('local')->put('simulation-materials/problem.pdf', '%PDF-1.4 test');
    Storage::disk('local')->put('simulation-materials/instruksi-lgd.pdf', '%PDF-1.4 instruction');
    $material = SimulationMaterialPage::create(['simulation_scenario_id' => $problemScenario->id, 'title' => 'Materi 1', 'page_order' => 1, 'attachment_path' => 'simulation-materials/problem.pdf', 'attachment_name' => 'problem.pdf', 'attachment_mime_type' => 'application/pdf', 'attachment_size' => 13, 'is_required' => true]);
    $instruction = SimulationMaterialPage::create(['simulation_scenario_id' => $lgdScenario->id, 'title' => 'Instruksi LGD', 'page_order' => 1, 'attachment_path' => 'simulation-materials/instruksi-lgd.pdf', 'attachment_name' => 'instruksi-lgd.pdf', 'attachment_mime_type' => 'application/pdf', 'attachment_size' => 20, 'is_required' => true]);
    $problemSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $problemScenario->id, 'status' => 'scheduled']);
    $lgdSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $lgdScenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'status' => 'assigned']);

    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $problemSimulation));
    $this->actingAs($participantUser)->put(route('peserta-assessment.simulations.material.save', [$problemSimulation, 1]), ['response' => 'Analisis final peserta.']);
    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.submit', $problemSimulation));
    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $lgdSimulation));

    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.lgd-review', $lgdSimulation))
        ->assertOk()
        ->assertSee('Instruksi Leaderless Group Discussion')
        ->assertSee(route('peserta-assessment.simulations.material.pdf', [$lgdSimulation, $instruction]), false)
        ->assertSee('Analisis final peserta.')
        ->assertSee('Sisa waktu')
        ->assertSee('Simulasi Sudah Selesai', false);
    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.material.pdf', [$problemSimulation, $material]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename="materi-assessment.pdf"')
        ->assertHeader('cache-control', 'max-age=0, must-revalidate, no-cache, no-store, private');
    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.material.pdf', [$lgdSimulation, $instruction]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('simulation three shows only the pdf package matching participant category', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::CRITICAL_INCIDENT)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'REV-CI', 'name' => 'Revisi Simulasi 3', 'status' => 'active', 'created_by' => $admin->id]);
    $ownScenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'simulation_package' => 'in_tray_1', 'code' => 'REV-INTRAY', 'title' => $type->name, 'duration_minutes' => 90, 'status' => 'active', 'created_by' => $admin->id]);
    $otherScenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'simulation_package' => 'ci_1', 'code' => 'REV-CI-OTHER', 'title' => $type->name, 'duration_minutes' => 90, 'status' => 'active', 'created_by' => $admin->id]);
    Storage::disk('local')->put('simulation-materials/intray.pdf', '%PDF-1.4 test');
    SimulationMaterialPage::create(['simulation_scenario_id' => $ownScenario->id, 'title' => 'Materi 1', 'page_order' => 1, 'attachment_path' => 'simulation-materials/intray.pdf', 'attachment_name' => 'intray.pdf', 'attachment_mime_type' => 'application/pdf', 'attachment_size' => 13, 'is_required' => true]);
    $ownSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $ownScenario->id, 'status' => 'scheduled']);
    $otherSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $otherScenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'assessment_category' => 'promotion_m', 'status' => 'assigned']);

    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.index'))
        ->assertOk()
        ->assertViewHas('participations', fn ($participations) => $participations->first()->program->simulations->contains('id', $ownSimulation->id)
            && ! $participations->first()->program->simulations->contains('id', $otherSimulation->id));
    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $ownSimulation))
        ->assertRedirect(route('peserta-assessment.simulations.material', [$ownSimulation, 1]));
    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $otherSimulation))->assertNotFound();
    expect(SimulationSession::count())->toBe(1);
});

test('admin chooses madya simulation three material and it locks after participant starts', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'REV-MADYA', 'name' => 'Pilihan Madya', 'status' => 'active', 'created_by' => $admin->id]);
    $catalog = SimulationCatalog::ensure($admin->id);
    foreach ($catalog as $scenario) {
        if ($scenario->usesSharedSimulationThreeMaterial()) {
            $path = 'simulation-materials/'.$scenario->id.'/test.pdf';
            Storage::disk('local')->put($path, '%PDF-1.4 test');
            $scenario->materialPages()->create(['title' => 'Materi', 'page_order' => 1, 'attachment_path' => $path, 'attachment_name' => 'test.pdf', 'is_required' => true]);
        }
    }
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'assessment_category' => AssessmentParticipant::MADYA_CATEGORY, 'status' => 'assigned']);
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$participantUser->id], 'participant_categories' => [$participantUser->id => AssessmentParticipant::MADYA_CATEGORY],
        'assessor_ids' => [$assessor->id],
    ])->assertSessionHasNoErrors();
    $ciLong = $program->simulations()->whereHas('scenario', fn ($query) => $query->where('simulation_package', 'ci_long'))->firstOrFail();
    $inTray = $program->simulations()->whereHas('scenario', fn ($query) => $query->where('simulation_package', 'in_tray'))->firstOrFail();

    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $ciLong))
        ->assertStatus(422);
    expect(SimulationSession::count())->toBe(0);

    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$participantUser->id], 'participant_categories' => [$participantUser->id => AssessmentParticipant::MADYA_CATEGORY],
        'participant_simulation_three_choices' => [$participantUser->id => 'in_tray'], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasNoErrors();

    expect($participant->fresh()->simulation_three_choice)->toBe('in_tray')
        ->and($participant->fresh()->simulation_three_chosen_at)->not->toBeNull()
        ->and(SimulationSession::count())->toBe(0);

    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $inTray));

    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$participantUser->id], 'participant_categories' => [$participantUser->id => AssessmentParticipant::MADYA_CATEGORY],
        'participant_simulation_three_choices' => [$participantUser->id => 'ci_long'], 'assessor_ids' => [$assessor->id],
    ])->assertSessionHasErrors("participant_simulation_three_choices.{$participantUser->id}");
    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.show', $ciLong))->assertNotFound();
});
