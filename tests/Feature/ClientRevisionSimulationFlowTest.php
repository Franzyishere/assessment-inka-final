<?php

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
    $material = SimulationMaterialPage::create(['simulation_scenario_id' => $problemScenario->id, 'title' => 'Materi 1', 'page_order' => 1, 'attachment_path' => 'simulation-materials/problem.pdf', 'attachment_name' => 'problem.pdf', 'attachment_mime_type' => 'application/pdf', 'attachment_size' => 13, 'is_required' => true]);
    $problemSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $problemScenario->id, 'status' => 'scheduled']);
    $lgdSimulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $lgdScenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'status' => 'assigned']);

    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $problemSimulation));
    $this->actingAs($participantUser)->put(route('peserta-assessment.simulations.material.save', [$problemSimulation, 1]), ['response' => 'Analisis final peserta.']);
    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.submit', $problemSimulation));
    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $lgdSimulation));

    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.lgd-review', $lgdSimulation))
        ->assertOk()
        ->assertSee('Analisis final peserta.')
        ->assertSee('Sisa waktu')
        ->assertSee('Simpan & Kumpulkan', false);
    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.material.pdf', [$problemSimulation, $material]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename="materi-assessment.pdf"')
        ->assertHeader('cache-control', 'max-age=0, must-revalidate, no-cache, no-store, private');
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

test('assessor chooses madya simulation three package and it locks after participant starts', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'REV-MADYA', 'name' => 'Pilihan Madya', 'status' => 'active', 'created_by' => $admin->id]);
    $catalog = SimulationCatalog::ensure($admin->id);
    $ci3Scenario = $catalog->firstWhere('simulation_package', 'ci_3');
    $inTray3Scenario = $catalog->firstWhere('simulation_package', 'in_tray_3');
    $ci3 = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $ci3Scenario->id, 'status' => 'scheduled']);
    $inTray3 = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $inTray3Scenario->id, 'status' => 'scheduled']);
    $participant = AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser->id, 'assessment_category' => AssessmentParticipant::MADYA_CATEGORY, 'status' => 'assigned']);
    AssessorAssignment::create(['assessment_program_simulation_id' => $ci3->id, 'assessor_id' => $assessor->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);

    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $ci3))
        ->assertStatus(422);
    expect(SimulationSession::count())->toBe(0);

    $this->actingAs($assessor)->put(route('asesor.participants.simulation-three-choice.update', $participant), [
        'simulation_package' => 'in_tray_3',
    ])->assertRedirect();

    expect($participant->fresh()->simulation_three_choice)->toBe('in_tray_3')
        ->and($participant->fresh()->simulation_three_chosen_at)->not->toBeNull()
        ->and(SimulationSession::count())->toBe(0);

    $this->actingAs($participantUser)->post(route('peserta-assessment.simulations.start', $inTray3));

    $this->actingAs($assessor)->put(route('asesor.participants.simulation-three-choice.update', $participant), [
        'simulation_package' => 'ci_3',
    ])->assertStatus(409);
    $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.show', $ci3))->assertNotFound();
});
