<?php

use App\Models\PapiScoringRule;
use App\Models\PsychologicalQuestion;
use App\Models\PsychologicalQuestionOption;
use App\Models\PsychologicalTest;
use App\Models\RecruitmentBatch;
use App\Models\RecruitmentBatchPsychologicalTest;
use App\Models\User;
use App\Services\PsychologicalTests\PapiQuestionImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function papiQuestionFile(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'papi-question-test-');
    $spreadsheet = new Spreadsheet;
    $rows = [['nomor', 'pernyataan_a', 'pernyataan_b']];

    foreach (range(1, 90) as $number) {
        $rows[] = [$number, "Pernyataan A nomor {$number}", "Pernyataan B nomor {$number}"];
    }

    $spreadsheet->getActiveSheet()->fromArray($rows);
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return new UploadedFile($path, 'soal-papi.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

function papiScoringFile(bool $mixedCategory = false): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'papi-scoring-test-');
    $spreadsheet = new Spreadsheet;
    $rows = [['nomor', 'dimensi_a', 'dimensi_b']];
    $role = ['G', 'L', 'I', 'T', 'V', 'S', 'R', 'D', 'C', 'E'];
    $need = ['N', 'A', 'P', 'X', 'B', 'O', 'Z', 'K', 'F', 'W'];

    foreach (range(1, 90) as $number) {
        $dimensions = $number <= 45 ? $role : $need;
        $dimensionA = $dimensions[($number - 1) % 10];
        $dimensionB = $dimensions[$number % 10];
        if ($mixedCategory && $number === 1) {
            $dimensionB = 'N';
        }
        $rows[] = [$number, $dimensionA, $dimensionB];
    }

    $spreadsheet->getActiveSheet()->fromArray($rows);
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return new UploadedFile($path, 'scoring-papi.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('admin imports the 90 PAPI question pairs once into a global version', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $test = PsychologicalTest::query()->where('code', 'PAPI_KOSTICK')->firstOrFail();
    $version = $test->versions()->create(['version' => 'GLOBAL-2026-01']);

    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.questions.import.preview', $version), [
        'question_file' => papiQuestionFile(),
    ])->assertRedirect(route('admin.recruitment.psychotests.questions.import.create', $version));

    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.questions.import.store', $version))
        ->assertRedirect(route('admin.recruitment.psychotests.show', $version));

    expect($version->questions()->count())->toBe(90)
        ->and(PsychologicalQuestion::count())->toBe(90)
        ->and(PsychologicalQuestionOption::count())->toBe(180);
});

test('one published global PAPI version is reused by multiple recruitment batches', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $test = PsychologicalTest::query()->where('code', 'PAPI_KOSTICK')->firstOrFail();
    $version = $test->versions()->create(['version' => 'GLOBAL-PUBLISHED-01', 'status' => 'draft']);
    $rows = collect(range(1, 90))->map(fn (int $number) => [
        'row' => $number + 1,
        'number' => $number,
        'statement_a' => "Pernyataan A {$number}",
        'statement_b' => "Pernyataan B {$number}",
    ])->all();
    app(PapiQuestionImportService::class)->import($version, $rows);
    $version->update(['status' => 'published', 'published_at' => now(), 'published_by' => $admin->id]);

    $batches = collect(['Batch September', 'Batch Oktober'])->map(fn (string $name) => RecruitmentBatch::create([
        'name' => $name,
        'status' => 'draft',
        'created_by' => $admin->id,
    ]));

    foreach ($batches as $batch) {
        $this->actingAs($admin)->post(route('admin.recruitment.psychotests.assign', $batch), [
            'psychological_test_version_id' => $version->id,
            'duration_minutes' => 60,
            'is_active' => true,
        ])->assertRedirect();
    }

    expect(RecruitmentBatchPsychologicalTest::where('psychological_test_version_id', $version->id)->count())->toBe(2)
        ->and($version->questions()->count())->toBe(90)
        ->and(PsychologicalQuestion::count())->toBe(90);
});

test('a draft PAPI version cannot be assigned to a recruitment batch', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $test = PsychologicalTest::query()->where('code', 'PAPI_KOSTICK')->firstOrFail();
    $version = $test->versions()->create(['version' => 'DRAFT-NOT-ASSIGNABLE']);
    $batch = RecruitmentBatch::create(['name' => 'Batch Draft', 'status' => 'draft', 'created_by' => $admin->id]);

    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.assign', $batch), [
        'psychological_test_version_id' => $version->id,
        'is_active' => true,
    ])->assertSessionHasErrors('psychological_test_version_id');

    expect(RecruitmentBatchPsychologicalTest::count())->toBe(0);
});

test('admin imports a complete scoring key and validates the global version', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $test = PsychologicalTest::query()->where('code', 'PAPI_KOSTICK')->firstOrFail();
    $version = $test->versions()->create(['version' => 'SCORING-VALID-01', 'status' => 'draft']);

    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.questions.import.preview', $version), [
        'question_file' => papiQuestionFile(),
    ]);
    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.questions.import.store', $version));

    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.scoring.import.preview', $version), [
        'scoring_file' => papiScoringFile(),
    ])->assertRedirect(route('admin.recruitment.psychotests.scoring.import.create', $version));
    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.scoring.import.store', $version))
        ->assertRedirect(route('admin.recruitment.psychotests.show', $version));

    expect(PapiScoringRule::count())->toBe(180);

    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.validate', $version))->assertRedirect();

    expect($version->fresh()->status)->toBe('validated');
});

test('scoring key preview rejects mixed role and need mapping in one item', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $test = PsychologicalTest::query()->where('code', 'PAPI_KOSTICK')->firstOrFail();
    $version = $test->versions()->create(['version' => 'SCORING-INVALID-01', 'status' => 'draft']);

    $this->actingAs($admin)->post(route('admin.recruitment.psychotests.scoring.import.preview', $version), [
        'scoring_file' => papiScoringFile(true),
    ])->assertRedirect();

    $preview = session('papi_scoring_preview.'.$version->id);
    expect(collect($preview)->where('valid', false))->not->toBeEmpty()
        ->and(PapiScoringRule::count())->toBe(0);
});
