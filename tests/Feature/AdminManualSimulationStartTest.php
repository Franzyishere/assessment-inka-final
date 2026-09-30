<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationType;
use App\Models\User;

beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\EnsureAssessmentInvitation::class);
});

function manualSessionFixture(int $sequence = 1): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'peserta_assessment']);
    $program = AssessmentProgram::create(['code' => 'MANUAL', 'name' => 'Program Manual', 'status' => 'active', 'created_by' => $admin->id]);
    $type = SimulationType::create(['code' => 'MANUAL', 'name' => 'Simulasi Manual', 'sequence' => $sequence, 'delivery_mode' => $sequence === 4 ? 'file_upload' : 'multi_page_response', 'is_active' => true]);
    $scenario = SimulationScenario::create(['simulation_type_id' => $type->id, 'code' => 'MANUAL', 'title' => 'Materi Manual', 'duration_minutes' => 60, 'status' => 'published', 'created_by' => $admin->id]);
    $scenario->materialPages()->create(['title' => 'Materi', 'content' => 'Kasus', 'page_order' => 1, 'is_required' => true]);
    $simulation = AssessmentProgramSimulation::create(['assessment_program_id' => $program->id, 'simulation_scenario_id' => $scenario->id, 'status' => 'scheduled']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $user->id, 'status' => 'assigned']);
    return compact('admin', 'user', 'program', 'simulation');
}

test('closing blocks active work and reopening preserves answers and original deadline', function () {
    extract(manualSessionFixture());
    $this->actingAs($user)->post(route('peserta-assessment.simulations.start', $simulation))->assertForbidden();
    $this->post(route('admin.monitoring.simulations.start', [$program, 1]))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.start', [$program, 1]))->assertRedirect();
    $this->actingAs($user)->post(route('peserta-assessment.simulations.start', $simulation))->assertRedirect();
    $save = route('peserta-assessment.simulations.material.save', [$simulation, 1]);
    $this->putJson($save, ['response' => '<p>Jawaban aman</p>', 'draft_only' => true])->assertOk();
    $session = SimulationSession::firstOrFail();
    $deadline = $session->expires_at->toIso8601String();
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.close', [$program, 1]))->assertRedirect();
    $this->actingAs($user)->get(route('peserta-assessment.simulations.material', [$simulation, 1]))->assertForbidden();
    $this->putJson($save, ['response' => 'Tidak boleh berubah', 'draft_only' => true])->assertForbidden();
    $this->post(route('peserta-assessment.simulations.submit', $simulation))->assertForbidden();
    expect($session->submissions()->firstOrFail()->response_text)->toContain('Jawaban aman');
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.start', [$program, 1]))->assertRedirect();
    expect($simulation->fresh()->closes_at)->toBeNull();
    $this->actingAs($user)->get(route('peserta-assessment.simulations.index'))->assertOk()->assertSee('Simulasi Manual');
    $this->post(route('peserta-assessment.simulations.start', $simulation))->assertRedirect();
    expect($session->fresh()->expires_at->toIso8601String())->toBe($deadline);
    $this->putJson($save, ['response' => 'Lanjut', 'draft_only' => true])->assertOk();
});

test('monitoring uses compact controls and correctly shows a closed presentation', function () {
    extract(manualSessionFixture(4));
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.close', [$program, 4]))->assertRedirect();
    $this->get(route('admin.monitoring.show', $program))->assertOk()
        ->assertSee('Ditutup')->assertSee('Buka Kembali')->assertSee('confirm-dialog')
        ->assertDontSee('Selalu Terbuka')->assertDontSee('Selesai Dinilai')->assertDontSee('return confirm(', false);
});

test('admin cannot reopen inactive or archived programs', function () {
    extract(manualSessionFixture());
    foreach (['draft', 'completed', 'cancelled'] as $status) {
        $program->update(['status' => $status]);
        $this->actingAs($admin)->post(route('admin.monitoring.simulations.start', [$program, 1]))->assertSessionHas('error');
        expect($simulation->fresh()->status)->toBe('scheduled');
    }
    $program->forceFill(['status' => 'active', 'archived_at' => now()])->save();
    $this->post(route('admin.monitoring.simulations.start', [$program, 1]))->assertSessionHas('error');
});

test('closed presentation rejects upload and duplicate submission preserves the original file', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    extract(manualSessionFixture(4));
    $url = route('peserta-assessment.simulations.presentation.submit', $simulation);
    $this->actingAs($user)->get(route('peserta-assessment.simulations.presentation', $simulation))->assertOk();
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.close', [$program, 4]))->assertRedirect();
    $file = fn () => \Illuminate\Http\UploadedFile::fake()->create('presentasi.pdf', 10, 'application/pdf');
    $this->actingAs($user)->post($url, ['presentation' => $file()])->assertForbidden();
    $this->assertDatabaseCount('simulation_submissions', 0);
    expect(\Illuminate\Support\Facades\Storage::disk('local')->allFiles())->toBe([]);
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.start', [$program, 4]))->assertRedirect();
    $this->actingAs($user)->post($url, ['presentation' => $file()])->assertRedirect();
    $original = \App\Models\SimulationSubmission::firstOrFail()->getAttributes();
    $this->post($url, ['presentation' => $file()])->assertForbidden();
    $this->assertDatabaseCount('simulation_submissions', 1);
    expect(\App\Models\SimulationSubmission::firstOrFail()->getAttributes())->toBe($original);
    expect(\Illuminate\Support\Facades\Storage::disk('local')->allFiles())->toHaveCount(1);
});

test('direct material URL requires an open unexpired participant session', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    extract(manualSessionFixture());
    $material = $simulation->scenario->materialPages()->firstOrFail();
    $material->update(['attachment_path' => 'test.pdf']);
    \Illuminate\Support\Facades\Storage::disk('local')->put('test.pdf', '%PDF-1.4 test');
    $url = route('peserta-assessment.simulations.material.pdf', [$simulation, $material]);
    $this->actingAs($user)->get($url)->assertForbidden();
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.start', [$program, 1]))->assertRedirect();
    $this->actingAs($user)->get($url)->assertForbidden();
    $this->post(route('peserta-assessment.simulations.start', $simulation))->assertRedirect();
    $this->get($url)->assertOk();
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.close', [$program, 1]))->assertRedirect();
    $this->actingAs($user)->get($url)->assertForbidden();
    $this->actingAs($admin)->post(route('admin.monitoring.simulations.start', [$program, 1]));
    SimulationSession::firstOrFail()->update(['expires_at' => now()->subMinute()]);
    $this->actingAs($user)->get($url)->assertForbidden();
});
