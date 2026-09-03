<?php

use App\Services\PsychologicalTests\PapiScoringService;

function validPapiMap(): array
{
    $map = [];
    foreach (range(1, 90) as $number) {
        $dimensions = $number <= 45
            ? PapiScoringService::ROLE_DIMENSIONS
            : PapiScoringService::NEED_DIMENSIONS;
        $map[$number] = [
            'A' => $dimensions[($number - 1) % count($dimensions)],
            'B' => $dimensions[$number % count($dimensions)],
        ];
    }

    return $map;
}

function validPapiAnswers(string $choice = 'A'): array
{
    return array_fill_keys(range(1, 90), $choice);
}

test('complete scoring map contains 90 items with A and B', function () {
    $map = validPapiMap();
    (new PapiScoringService)->validateScoringMap($map);

    expect($map)->toHaveCount(90);
    foreach ($map as $choices) {
        expect($choices)->toHaveKeys(['A', 'B']);
    }
});

test('missing scoring key is rejected', function () {
    $map = validPapiMap();
    unset($map[37]['B']);

    (new PapiScoringService)->validateScoringMap($map);
})->throws(DomainException::class, 'item 37 pilihan B');

test('unknown scoring dimension is rejected', function () {
    $map = validPapiMap();
    $map[1]['A'] = 'UNKNOWN';

    (new PapiScoringService)->validateScoringMap($map);
})->throws(DomainException::class, 'item 1 pilihan A');

test('incomplete answers are rejected', function () {
    $answers = validPapiAnswers();
    unset($answers[90]);

    (new PapiScoringService)->score($answers, validPapiMap());
})->throws(DomainException::class, 'Item 90 belum dijawab');

test('participant cannot submit two choices', function () {
    $answers = validPapiAnswers();
    $answers[1] = ['A', 'B'];

    (new PapiScoringService)->score($answers, validPapiMap());
})->throws(DomainException::class, 'tepat satu pilihan');

test('participant cannot submit an invalid choice', function (mixed $choice) {
    $answers = validPapiAnswers();
    $answers[1] = $choice;

    (new PapiScoringService)->score($answers, validPapiMap());
})->with(['C', '', null])->throws(DomainException::class, 'tepat satu pilihan');

test('scoring returns all dimensions and exact role and need totals', function () {
    $result = (new PapiScoringService)->score(validPapiAnswers('A'), validPapiMap());

    expect($result['scores'])->toHaveCount(20)
        ->and(array_sum($result['scores']))->toBe(90)
        ->and($result['total_role'])->toBe(45)
        ->and($result['total_need'])->toBe(45);
});

test('map cannot mix role and need dimensions inside one item', function () {
    $map = validPapiMap();
    $map[1]['B'] = 'N';

    (new PapiScoringService)->validateScoringMap($map);
})->throws(DomainException::class, 'kelompok scoring yang sama');
