<?php

use App\Models\PapiDimension;
use App\Models\PsychologicalResult;
use App\Models\PsychologicalTest;
use App\Models\PsychologicalTestSession;
use App\Models\RecruitmentBatch;
use App\Models\RecruitmentBatchPsychologicalTest;
use App\Models\RecruitmentParticipant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function recruitmentExamFixture(): array
{
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $user = User::factory()->create(['role' => User::ROLE_PESERTA_REKRUTMEN]);
    $participant = RecruitmentParticipant::create(['user_id' => $user->id, 'participant_number' => 'PAPI-001']);
    $batch = RecruitmentBatch::create(['name' => 'Batch Ujian PAPI', 'status' => 'active', 'created_by' => $admin->id]);
    $participant->batches()->attach($batch->id, ['status' => 'assigned', 'assigned_at' => now()]);

    $test = PsychologicalTest::query()->where('code', 'PAPI_KOSTICK')->firstOrFail();
    $version = $test->versions()->create(['version' => 'PARTICIPANT-FLOW-01', 'status' => 'published', 'published_at' => now(), 'published_by' => $admin->id]);
    $role = PapiDimension::query()->where('category', 'role')->orderBy('display_order')->get();
    $need = PapiDimension::query()->where('category', 'need')->orderBy('display_order')->get();

    foreach (range(1, 90) as $number) {
        $question = $version->questions()->create(['number' => $number]);
        $dimensions = $number <= 45 ? $role : $need;
        foreach (['A', 'B'] as $offset => $choice) {
            $option = $question->options()->create(['code' => $choice, 'statement' => "Pernyataan {$choice} {$number}", 'display_order' => $offset + 1]);
            $option->scoringRule()->create(['papi_dimension_id' => $dimensions[($number + $offset) % 10]->id, 'weight' => 1]);
        }
    }

    $assignment = RecruitmentBatchPsychologicalTest::create([
        'recruitment_batch_id' => $batch->id,
        'psychological_test_version_id' => $version->id,
        'available_from' => now()->subMinute(),
        'available_until' => now()->addHour(),
        'duration_minutes' => 60,
        'max_attempts' => 1,
        'is_active' => true,
    ]);

    return compact('user', 'participant', 'batch', 'version', 'assignment');
}

test('recruitment participant starts answers and submits all 90 PAPI items', function () {
    $fixture = recruitmentExamFixture();
    $user = $fixture['user'];
    $assignment = $fixture['assignment'];

    $this->actingAs($user)->get(route('peserta-rekrutmen.exams.index'))->assertOk()->assertSee('PAPI Kostick');
    $this->actingAs($user)->get(route('peserta-rekrutmen.exams.instructions', $assignment))->assertOk()->assertSee('90 pasangan');
    $this->actingAs($user)->post(route('peserta-rekrutmen.exams.start', $assignment))->assertRedirect();

    $session = PsychologicalTestSession::query()->firstOrFail();
    expect($session->status)->toBe('in_progress')->and($session->expires_at)->not->toBeNull();

    foreach ($fixture['version']->questions()->with('options')->orderBy('number')->get() as $question) {
        $this->actingAs($user)->postJson(route('peserta-rekrutmen.exams.answers.store', $session), [
            'question_id' => $question->id,
            'choice' => 'A',
        ])->assertOk();
    }

    $this->actingAs($user)->post(route('peserta-rekrutmen.exams.submit', $session))->assertRedirect(route('peserta-rekrutmen.exams.index'));

    expect($session->fresh()->status)->toBe('submitted')
        ->and($session->answers()->count())->toBe(90)
        ->and(PsychologicalResult::count())->toBe(1)
        ->and($session->result->scores()->count())->toBe(20)
        ->and($session->result->total_role)->toBe(45)
        ->and($session->result->total_need)->toBe(45);
});

test('participant cannot submit an incomplete PAPI exam', function () {
    $fixture = recruitmentExamFixture();
    $this->actingAs($fixture['user'])->post(route('peserta-rekrutmen.exams.start', $fixture['assignment']));
    $session = PsychologicalTestSession::query()->firstOrFail();

    $this->actingAs($fixture['user'])->post(route('peserta-rekrutmen.exams.submit', $session))
        ->assertSessionHas('error');

    expect($session->fresh()->status)->toBe('in_progress')->and(PsychologicalResult::count())->toBe(0);
});

test('recruitment participant cannot access another participants exam session', function () {
    $fixture = recruitmentExamFixture();
    $this->actingAs($fixture['user'])->post(route('peserta-rekrutmen.exams.start', $fixture['assignment']));
    $session = PsychologicalTestSession::query()->firstOrFail();
    $otherUser = User::factory()->create(['role' => User::ROLE_PESERTA_REKRUTMEN]);
    RecruitmentParticipant::create(['user_id' => $otherUser->id, 'participant_number' => 'PAPI-OTHER']);

    $this->actingAs($otherUser)->get(route('peserta-rekrutmen.exams.take', $session))->assertForbidden();
});
