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

test('assigned assessor views submitted results but cannot save reviews', function () {
    ['assessor' => $assessor, 'session' => $session] = submittedSessionForReview();
    $program = $session->programSimulation->program;
    $this->actingAs($assessor)->get(route('asesor.reviews.index'))
        ->assertOk()->assertSee('Hasil Assessment')->assertSee('Review Test');
    $this->get(route('asesor.reviews.program', $program))->assertOk()
        ->assertSee('Lihat &amp; Unduh Jawaban', false)->assertSee('format=pdf', false);
    $this->get(route('asesor.reviews.edit', $session))->assertOk()
        ->assertSee('Lembar Jawaban Peserta')->assertSee('Unduh PDF')
        ->assertDontSee('Simpan Draft')->assertDontSee('Finalisasi')->assertDontSee('name="recommendation"', false);
    $this->put(route('asesor.reviews.update', $session), ['status' => 'draft'])->assertForbidden();
    expect($session->reviews()->count())->toBe(0);

    $review = $session->reviews()->create([
        'assessor_id' => $assessor->id, 'status' => 'draft', 'assessment_notes' => 'Catatan lama',
    ]);
    $before = $review->fresh()->getAttributes();
    $this->put(route('asesor.reviews.update', $session), [
        'status' => 'submitted', 'recommendation' => 'recommended', 'assessment_notes' => 'Diubah',
    ])->assertForbidden();
    expect($review->fresh()->getAttributes())->toBe($before);
});

test('unfinished sessions are excluded and cannot be viewed or downloaded', function () {
    ['assessor' => $assessor, 'session' => $session] = submittedSessionForReview();
    $session->update(['status' => 'in_progress']);
    $this->actingAs($assessor)->get(route('asesor.reviews.index'))->assertOk()->assertDontSee('Review Test');
    $this->get(route('asesor.reviews.edit', $session))->assertForbidden();
    $this->get(route('asesor.reviews.download', $session))->assertForbidden();
});

test('unassigned assessor cannot view or review participant submission', function () {
    ['session' => $session] = submittedSessionForReview();
    $other = User::create(['name' => 'Asesor Tidak Ditugaskan', 'email' => 'unauthorized-assessor@example.test', 'role' => User::ROLE_ASESOR, 'password' => 'password']);

    $this->actingAs($other)->get(route('asesor.reviews.edit', $session))->assertForbidden();
    $this->actingAs($other)->get(route('asesor.reviews.program', $session->programSimulation->program))->assertForbidden();
    $this->actingAs($other)->put(route('asesor.reviews.update', $session), ['status' => 'draft'])->assertForbidden();
});

test('assigned assessor can download participant answer as pdf and word', function () {
    ['assessor' => $assessor, 'session' => $session] = submittedSessionForReview();

    $responsePdf = $this->actingAs($assessor)->get(route('asesor.reviews.download', [$session, 'format' => 'pdf']));
    $responsePdf->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    $pdfBytes = $responsePdf->getContent();
    expect($pdfBytes)->toStartWith('%PDF-');
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdfBytes, $streams);
    $decodedStreams = implode('', array_map(fn ($stream) => @gzuncompress($stream) ?: $stream, $streams[1]));
    expect(str_contains($decodedStreams, mb_convert_encoding('Jawaban peserta', 'UTF-16BE', 'UTF-8')))->toBeTrue();

    $responseWord = $this->actingAs($assessor)->get(route('asesor.reviews.download', [$session, 'format' => 'word']));
    $responseWord->assertOk()
        ->assertHeader('content-type', 'application/msword; charset=utf-8')
        ->assertSee('Jawaban peserta')->assertSee('Review Test')->assertSee('Lembar Jawaban Peserta');
});

test('export includes rich text tables plain answers and textbox text without current material pages', function () {
    ['assessor' => $assessor, 'session' => $session] = submittedSessionForReview();
    $scene = htmlspecialchars(json_encode([['type' => 'text', 'points' => [[10, 20]], 'text' => 'Penyebab mesin']]), ENT_QUOTES);
    $answer = '<p>Analisis lengkap</p><table><tr><td>Data penting</td></tr></table><span data-answer-scene="'.$scene.'"></span>';
    $submission = $session->submissions()->first();
    $submission->update(['response_text' => json_encode([1 => $answer, 2 => 'Jawaban kedua'])]);
    $word = $this->actingAs($assessor)->get(route('asesor.reviews.download', [$session, 'format' => 'word']))->assertOk()->getContent();
    expect(quoted_printable_decode($word))->toContain('Analisis lengkap', '<table>', 'Data penting', 'Penyebab mesin', 'Jawaban kedua');
    expect($word)->toContain('Content-Type: image/png', 'multipart/related');
    $pdf = $this->get(route('asesor.reviews.download', [$session, 'format' => 'pdf']))->assertOk()->getContent();
    expect($pdf)->toContain('/Subtype /Image');
    $submission->update(['response_text' => 'Respons kasus teks biasa']);
    $this->get(route('asesor.reviews.download', [$session, 'format' => 'word']))
        ->assertOk()->assertSee('Respons kasus teks biasa');
});
