<?php

// Exercise assessment business rules independently; real invitation/OTP gating is
// covered without middleware bypass in AssessmentInvitationAccessTest.
beforeEach(fn () => $this->withoutMiddleware(\App\Http\Middleware\EnsureAssessmentInvitation::class));

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationScenario;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

test('artisan command activates draft programs when starts_at has arrived', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

    $dueDraft = AssessmentProgram::create([
        'code' => 'DUE-DRAFT-01',
        'name' => 'Program Draft Jatuh Tempo',
        'status' => 'draft',
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addHours(2),
        'created_by' => $admin->id,
    ]);

    $futureDraft = AssessmentProgram::create([
        'code' => 'FUTURE-DRAFT-01',
        'name' => 'Program Draft Masa Depan',
        'status' => 'draft',
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(3),
        'created_by' => $admin->id,
    ]);

    $expiredDraft = AssessmentProgram::create([
        'code' => 'EXPIRED-DRAFT-01',
        'name' => 'Program Draft Lewat Batas Selesai',
        'status' => 'draft',
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHour(),
        'created_by' => $admin->id,
    ]);

    Artisan::call('assessment:activate-due-programs');

    expect($dueDraft->fresh()->status)->toBe('active')
        ->and($futureDraft->fresh()->status)->toBe('draft')
        ->and($expiredDraft->fresh()->status)->toBe('draft');
});

test('participant can view simulations of due draft program via runtime sync', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $participantUser = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();

    $program = AssessmentProgram::create([
        'code' => 'PART-DRAFT-DUE',
        'name' => 'Program Draft Peserta Jatuh Tempo',
        'status' => 'draft',
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addHours(2),
        'created_by' => $admin->id,
    ]);

    $type = \App\Models\SimulationType::firstOrFail();
    $scenario = SimulationScenario::create([
        'simulation_type_id' => $type->id,
        'code' => 'AUTO-SIM-01',
        'title' => 'Simulasi Auto Draft',
        'duration_minutes' => 60,
        'status' => 'published',
        'created_by' => $admin->id,
    ]);

    AssessmentProgramSimulation::create([
        'assessment_program_id' => $program->id,
        'simulation_scenario_id' => $scenario->id,
        'status' => 'scheduled',
    ]);

    AssessmentParticipant::create([
        'assessment_program_id' => $program->id,
        'user_id' => $participantUser->id,
        'status' => 'assigned',
    ]);

    // Before visit, status in DB is draft
    expect($program->fresh()->status)->toBe('draft');

    // Participant visits simulations page
    $response = $this->actingAs($participantUser)->get(route('peserta-assessment.simulations.index'));
    $response->assertOk();
    $response->assertSee($program->name);

    // After runtime sync, program status in DB is now active
    expect($program->fresh()->status)->toBe('active');
});

test('admin cannot delete program that was auto-activated because its start date arrived', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

    $program = AssessmentProgram::create([
        'code' => 'PREV-DRAFT-NOW-ACTIVE',
        'name' => 'Program Draft Otomatis Aktif',
        'status' => 'draft',
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addHours(2),
        'created_by' => $admin->id,
    ]);

    // Admin visits programs index which triggers activation
    $this->actingAs($admin)->get(route('admin.assessment-programs.index'))->assertOk();
    expect($program->fresh()->status)->toBe('active');

    // Admin tries to delete it
    $this->actingAs($admin)->delete(route('admin.assessment-programs.destroy', $program))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('assessment_programs', ['id' => $program->id]);
});
