<?php

namespace App\Services\PsychologicalTests;

use DomainException;

final class PapiScoringService
{
    public const ROLE_DIMENSIONS = ['G', 'L', 'I', 'T', 'V', 'S', 'R', 'D', 'C', 'E'];

    public const NEED_DIMENSIONS = ['N', 'A', 'P', 'X', 'B', 'O', 'Z', 'K', 'F', 'W'];

    public const ALL_DIMENSIONS = [...self::ROLE_DIMENSIONS, ...self::NEED_DIMENSIONS];

    public const ITEM_COUNT = 90;

    public const EXPECTED_ROLE_TOTAL = 45;

    public const EXPECTED_NEED_TOTAL = 45;

    /**
     * @param  array<int, array{A: string, B: string}>  $scoringMap
     */
    public function validateScoringMap(array $scoringMap): void
    {
        $roleItems = 0;
        $needItems = 0;

        foreach (range(1, self::ITEM_COUNT) as $number) {
            if (! array_key_exists($number, $scoringMap)) {
                throw new DomainException("Scoring key item {$number} belum tersedia.");
            }

            $categories = [];
            foreach (['A', 'B'] as $choice) {
                $dimension = $scoringMap[$number][$choice] ?? null;
                if (! is_string($dimension) || ! in_array($dimension, self::ALL_DIMENSIONS, true)) {
                    throw new DomainException("Scoring key item {$number} pilihan {$choice} tidak valid.");
                }
                $categories[] = $this->dimensionCategory($dimension);
            }

            if ($categories[0] !== $categories[1]) {
                throw new DomainException("Pilihan A dan B item {$number} harus berada pada kelompok scoring yang sama.");
            }

            $categories[0] === 'role' ? $roleItems++ : $needItems++;
        }

        if (count($scoringMap) !== self::ITEM_COUNT) {
            throw new DomainException('Scoring key hanya boleh berisi item 1 sampai 90.');
        }
        if ($roleItems !== self::EXPECTED_ROLE_TOTAL || $needItems !== self::EXPECTED_NEED_TOTAL) {
            throw new DomainException('Scoring key harus memiliki tepat 45 item Role dan 45 item Need.');
        }
    }

    /**
     * @param  array<int, string>  $answers
     */
    public function validateAnswers(array $answers): void
    {
        foreach (range(1, self::ITEM_COUNT) as $number) {
            if (! array_key_exists($number, $answers)) {
                throw new DomainException("Item {$number} belum dijawab.");
            }
            if (! is_string($answers[$number]) || ! in_array($answers[$number], ['A', 'B'], true)) {
                throw new DomainException("Jawaban item {$number} harus tepat satu pilihan: A atau B.");
            }
        }

        if (count($answers) !== self::ITEM_COUNT) {
            throw new DomainException('Jawaban hanya boleh berisi item 1 sampai 90.');
        }
    }

    /**
     * @param  array<int, string>  $answers
     * @param  array<int, array{A: string, B: string}>  $scoringMap
     * @return array{scores: array<string, int>, total_role: int, total_need: int}
     */
    public function score(array $answers, array $scoringMap): array
    {
        $this->validateScoringMap($scoringMap);
        $this->validateAnswers($answers);

        $scores = array_fill_keys(self::ALL_DIMENSIONS, 0);
        foreach ($answers as $number => $choice) {
            $scores[$scoringMap[$number][$choice]]++;
        }

        $totalRole = $this->totalFor($scores, self::ROLE_DIMENSIONS);
        $totalNeed = $this->totalFor($scores, self::NEED_DIMENSIONS);
        if ($totalRole !== self::EXPECTED_ROLE_TOTAL || $totalNeed !== self::EXPECTED_NEED_TOTAL) {
            throw new DomainException("Hasil scoring tidak valid: Role={$totalRole}, Need={$totalNeed}.");
        }

        return ['scores' => $scores, 'total_role' => $totalRole, 'total_need' => $totalNeed];
    }

    private function dimensionCategory(string $dimension): string
    {
        return in_array($dimension, self::ROLE_DIMENSIONS, true) ? 'role' : 'need';
    }

    /** @param array<string, int> $scores */
    private function totalFor(array $scores, array $dimensions): int
    {
        return array_sum(array_intersect_key($scores, array_flip($dimensions)));
    }
}
