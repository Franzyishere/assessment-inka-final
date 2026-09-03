<?php

namespace App\Services\PsychologicalTests;

use App\Models\PsychologicalTestVersion;
use DomainException;

final class PapiConfigurationService
{
    public function __construct(private readonly PapiScoringService $scoringService) {}

    /** @return array<int, array{A: string, B: string}> */
    public function scoringMap(PsychologicalTestVersion $version): array
    {
        if ($version->item_count !== PapiScoringService::ITEM_COUNT) {
            throw new DomainException('Versi PAPI harus dikonfigurasi dengan tepat 90 item.');
        }

        $version->load(['questions.options.scoringRule.dimension']);
        if ($version->questions->count() !== PapiScoringService::ITEM_COUNT) {
            throw new DomainException('Versi PAPI belum memiliki tepat 90 soal.');
        }

        $map = [];
        foreach ($version->questions as $question) {
            if ($question->number < 1 || $question->number > PapiScoringService::ITEM_COUNT) {
                throw new DomainException("Nomor soal {$question->number} berada di luar range 1 sampai 90.");
            }
            $options = $question->options->keyBy('code');
            if ($options->count() !== 2 || ! $options->has('A') || ! $options->has('B')) {
                throw new DomainException("Soal {$question->number} harus mempunyai tepat opsi A dan B.");
            }
            foreach (['A', 'B'] as $choice) {
                $rule = $options[$choice]->scoringRule;
                if (! $rule?->dimension || $rule->weight !== 1) {
                    throw new DomainException("Scoring rule soal {$question->number} pilihan {$choice} belum valid.");
                }
                $map[$question->number][$choice] = $rule->dimension->code;
            }
        }

        ksort($map);
        $this->scoringService->validateScoringMap($map);

        return $map;
    }
}
