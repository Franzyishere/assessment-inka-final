<?php

use App\Models\PapiDimension;
use App\Models\PapiScoringRule;
use App\Models\PsychologicalQuestionOption;
use App\Models\PsychologicalTest;
use App\Models\PsychologicalTestVersion;
use App\Services\PsychologicalTests\PapiConfigurationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function createCompletePapiVersion(): PsychologicalTestVersion
{
    $test = PsychologicalTest::query()->where('code', 'PAPI_KOSTICK')->firstOrFail();
    $version = $test->versions()->create(['version' => 'INKA-TEST-01', 'item_count' => 90]);
    $roleDimensions = PapiDimension::where('category', 'role')->orderBy('display_order')->get();
    $needDimensions = PapiDimension::where('category', 'need')->orderBy('display_order')->get();

    foreach (range(1, 90) as $number) {
        $question = $version->questions()->create(['number' => $number]);
        $dimensions = $number <= 45 ? $roleDimensions : $needDimensions;
        foreach (['A', 'B'] as $offset => $choice) {
            $option = $question->options()->create([
                'code' => $choice,
                'statement' => "DEMO ONLY — Item {$number} {$choice}",
                'display_order' => $offset + 1,
            ]);
            $option->scoringRule()->create([
                'papi_dimension_id' => $dimensions[($number + $offset) % 10]->id,
                'weight' => 1,
            ]);
        }
    }

    return $version;
}

test('database stores a versioned complete PAPI configuration', function () {
    $version = createCompletePapiVersion();
    $map = app(PapiConfigurationService::class)->scoringMap($version);

    expect(PapiDimension::count())->toBe(20)
        ->and($version->questions()->count())->toBe(90)
        ->and(PsychologicalQuestionOption::count())->toBe(180)
        ->and(PapiScoringRule::count())->toBe(180)
        ->and($map)->toHaveCount(90)
        ->and($map[1])->toHaveKeys(['A', 'B']);
});

test('configuration validator rejects an incomplete PAPI version', function () {
    $test = PsychologicalTest::create(['code' => 'PAPI_INCOMPLETE', 'name' => 'PAPI Incomplete']);
    $version = $test->versions()->create(['version' => 'DRAFT-01', 'item_count' => 90]);
    $version->questions()->create(['number' => 1]);

    app(PapiConfigurationService::class)->scoringMap($version);
})->throws(DomainException::class, 'belum memiliki tepat 90 soal');

test('question number and option code are unique inside their parent', function () {
    $test = PsychologicalTest::create(['code' => 'PAPI_UNIQUE', 'name' => 'PAPI Unique']);
    $version = $test->versions()->create(['version' => 'DRAFT-01']);
    $question = $version->questions()->create(['number' => 1]);
    $question->options()->create(['code' => 'A', 'statement' => 'Demo A', 'display_order' => 1]);

    expect(fn () => $version->questions()->create(['number' => 1]))->toThrow(QueryException::class);
    expect(fn () => $question->options()->create(['code' => 'A', 'statement' => 'Duplikat', 'display_order' => 2]))->toThrow(QueryException::class);
});
