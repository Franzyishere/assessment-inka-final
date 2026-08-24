<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

test('admin can view assessment program and simulation management pages', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

    $this->actingAs($admin)->get(route('admin.assessment-programs.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.assessment-programs.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.simulations.index'))->assertOk()->assertSee('Empat simulasi merupakan katalog tetap')->assertDontSee('Buat Simulasi');
    expect(SimulationScenario::count())->toBe(9)
        ->and(Route::has('admin.simulations.create'))->toBeFalse()
        ->and(Route::has('admin.simulations.destroy'))->toBeFalse();
});

test('admin can create and update an assessment program', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

    $this->actingAs($admin)->post(route('admin.assessment-programs.store'), [
        'name' => 'Assessment Internal Q3 2026',
        'description' => 'Program assessment internal.',
        'starts_at' => '2026-08-25 08:00:00',
        'ends_at' => '2026-08-30 17:00:00',
        'status' => 'draft',
    ])->assertRedirect(route('admin.assessment-programs.index'));

    $program = AssessmentProgram::query()->where('name', 'Assessment Internal Q3 2026')->firstOrFail();
    $internalCode = $program->code;
    expect($program->creator->is($admin))->toBeTrue()
        ->and($internalCode)->toStartWith('ASM-');

    $this->actingAs($admin)->put(route('admin.assessment-programs.update', $program), [
        'name' => 'Assessment Internal Q3 2026 Revisi',
        'description' => 'Program assessment internal.',
        'starts_at' => '2026-08-25 08:00:00',
        'ends_at' => '2026-08-30 17:00:00',
        'status' => 'active',
    ])->assertRedirect(route('admin.assessment-programs.index'));

    expect($program->refresh()->name)->toBe('Assessment Internal Q3 2026 Revisi')
        ->and($program->status)->toBe('active')
        ->and($program->code)->toBe($internalCode);
});

test('admin can update the fixed problem analysis material', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $type = SimulationType::query()->where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $pages = [['title' => 'Materi 1', 'is_required' => 1, 'attachment' => UploadedFile::fake()->create('uraian.pdf', 250, 'application/pdf')]];
    $this->actingAs($admin)->get(route('admin.simulations.index'));
    $scenario = SimulationScenario::query()->where('simulation_type_id', $type->id)->firstOrFail();

    $this->actingAs($admin)->put(route('admin.simulations.update', $scenario), [
        'simulation_type_id' => $type->id,
        'description' => 'Skenario problem analysis.',
        'duration_minutes' => 90,
        'status' => 'active',
        'material_pages' => $pages,
    ])->assertRedirect(route('admin.simulations.index'));

    expect($scenario->materialPages()->count())->toBe(1)
        ->and($scenario->code)->toStartWith('SYSTEM-')
        ->and($scenario->title)->toBe($type->name)
        ->and($scenario->materialPages()->first()->page_order)->toBe(1)
        ->and($scenario->materialPages()->first()->attachment_name)->toBe('uraian.pdf');
    Storage::disk('local')->assertExists($scenario->materialPages()->first()->attachment_path);
});

test('admin can update the fixed leaderless group discussion without pdf material', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $type = SimulationType::query()->where('code', SimulationType::LGD)->firstOrFail();
    $this->actingAs($admin)->get(route('admin.simulations.index'));
    $scenario = SimulationScenario::query()->where('simulation_type_id', $type->id)->firstOrFail();

    $this->actingAs($admin)->put(route('admin.simulations.update', $scenario), [
        'simulation_type_id' => $type->id,
        'description' => 'LGD menggunakan materi dan jawaban Problem Analysis.',
        'status' => 'active',
    ])->assertRedirect(route('admin.simulations.index'));

    expect($scenario->title)->toBe($type->name)
        ->and($scenario->materialPages()->count())->toBe(0);
});

test('assessment participant cannot access admin management pages', function () {
    $participant = User::query()->where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();

    $this->actingAs($participant)
        ->get(route('admin.assessment-programs.index'))
        ->assertForbidden();

    $this->actingAs($participant)
        ->get(route('admin.simulations.index'))
        ->assertForbidden();
});

test('program end must be after its start', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

    $this->actingAs($admin)->post(route('admin.assessment-programs.store'), [
        'code' => 'INVALID-DATE',
        'name' => 'Invalid Program',
        'starts_at' => '2026-08-30 17:00:00',
        'ends_at' => '2026-08-25 08:00:00',
        'status' => 'draft',
    ])->assertSessionHasErrors('ends_at');
});

test('program setup integrates selected simulation participant and assessor', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'SETUP-01', 'name' => 'Program Setup', 'status' => 'draft', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'SETUP-SIM-01', 'title' => 'Simulasi Terintegrasi', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);

    $this->actingAs($admin)->get(route('admin.assessment-programs.setup.edit', $program))
        ->assertOk()->assertSee('Tim Asesor Program')->assertSee('Pilih semua asesor')->assertSee('Tim Asesor Belum Dipilih')->assertSee('Cari nama atau email')->assertSee($type->name);
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'simulation_ids' => [$scenario->id],
        'participant_ids' => [$participant->id],
        'participant_categories' => [$participant->id => 'grade_1_to_2'],
        'assessor_ids' => [$assessor->id],
    ])->assertRedirect(route('admin.assessment-programs.setup.edit', $program));

    $programSimulation = AssessmentProgramSimulation::where('assessment_program_id', $program->id)->firstOrFail();
    expect($programSimulation->simulation_scenario_id)->toBe($scenario->id)
        ->and(AssessmentParticipant::where('assessment_program_id', $program->id)->value('user_id'))->toBe($participant->id)
        ->and(AssessorAssignment::where('assessment_program_simulation_id', $programSimulation->id)->value('assessor_id'))->toBe($assessor->id);
});

test('active simulation created from admin form is available in program setup', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $type = SimulationType::where('code', SimulationType::PRESENTATION)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'ACTIVE-SETUP', 'name' => 'Active Setup', 'status' => 'draft', 'created_by' => $admin->id]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'ACTIVE-SIM', 'title' => 'Simulasi Aktif dari UI', 'duration_minutes' => 30, 'status' => 'active', 'created_by' => $admin->id]);

    $this->actingAs($admin)->get(route('admin.assessment-programs.setup.edit', $program))
        ->assertOk()->assertSee($type->name)->assertDontSee('Belum ada simulasi berstatus aktif.');
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'simulation_ids' => [$scenario->id], 'participant_ids' => [], 'assessor_ids' => [$assessor->id],
    ])->assertRedirect(route('admin.assessment-programs.setup.edit', $program));
    expect($program->simulations()->where('simulation_scenario_id', $scenario->id)->exists())->toBeTrue();
});

test('program setup requires at least one assessor', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'ASSESSOR-REQUIRED', 'name' => 'Asesor Wajib', 'status' => 'draft', 'created_by' => $admin->id]);

    $this->actingAs($admin)
        ->from(route('admin.assessment-programs.setup.edit', $program))
        ->put(route('admin.assessment-programs.setup.update', $program), [
            'simulation_ids' => [],
            'participant_ids' => [],
            'assessor_ids' => [],
        ])
        ->assertRedirect(route('admin.assessment-programs.setup.edit', $program))
        ->assertSessionHasErrors('assessor_ids');
});

test('admin can delete a draft program but cannot delete an active program', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $draft = AssessmentProgram::create(['code' => 'DELETE-DRAFT', 'name' => 'Draft Dihapus', 'status' => 'draft', 'created_by' => $admin->id]);
    $active = AssessmentProgram::create(['code' => 'KEEP-ACTIVE', 'name' => 'Aktif Dipertahankan', 'status' => 'active', 'created_by' => $admin->id]);

    $this->actingAs($admin)->delete(route('admin.assessment-programs.destroy', $draft))
        ->assertRedirect(route('admin.assessment-programs.index'));
    $this->assertDatabaseMissing('assessment_programs', ['id' => $draft->id]);

    $this->actingAs($admin)->delete(route('admin.assessment-programs.destroy', $active))
        ->assertSessionHas('error');
    $this->assertDatabaseHas('assessment_programs', ['id' => $active->id]);
});
