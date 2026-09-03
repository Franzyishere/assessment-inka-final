<?php

namespace App\Services\PsychologicalTests;

use App\Models\PapiDimension;
use App\Models\PsychologicalTestVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PapiScoringKeyImportService
{
    public const HEADERS = ['nomor', 'dimensi_a', 'dimensi_b'];

    public function read(string $path): array
    {
        $sheetRows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        $headers = array_map(
            fn ($value) => Str::of((string) $value)->lower()->trim()->replace(' ', '_')->toString(),
            array_shift($sheetRows) ?? [],
        );

        if (array_slice($headers, 0, 3) !== self::HEADERS) {
            throw ValidationException::withMessages(['scoring_file' => 'Kolom file harus berurutan: nomor, dimensi_a, dimensi_b.']);
        }

        $rows = [];
        foreach ($sheetRows as $index => $row) {
            if (trim(implode('', array_map('strval', array_slice($row, 0, 3)))) === '') {
                continue;
            }
            $rows[] = [
                'row' => $index + 2,
                'number' => filter_var($row[0] ?? null, FILTER_VALIDATE_INT) ?: 0,
                'dimension_a' => Str::upper(trim((string) ($row[1] ?? ''))),
                'dimension_b' => Str::upper(trim((string) ($row[2] ?? ''))),
            ];
        }

        return $this->validateRows($rows);
    }

    public function validateRows(array $rows): array
    {
        $dimensions = PapiDimension::query()->where('is_active', true)->get()->keyBy('code');
        $numbers = [];
        $categoryTotals = ['role' => 0, 'need' => 0];

        $result = array_map(function (array $row) use ($dimensions, &$numbers, &$categoryTotals) {
            $errors = [];
            if ($row['number'] < 1 || $row['number'] > 90) {
                $errors[] = 'Nomor harus berada pada rentang 1–90.';
            }
            if (isset($numbers[$row['number']])) {
                $errors[] = 'Nomor soal duplikat.';
            }

            $dimensionA = $dimensions->get($row['dimension_a']);
            $dimensionB = $dimensions->get($row['dimension_b']);
            if (! $dimensionA) {
                $errors[] = 'Dimensi A tidak dikenal.';
            }
            if (! $dimensionB) {
                $errors[] = 'Dimensi B tidak dikenal.';
            }
            if ($dimensionA && $dimensionB && $dimensionA->category !== $dimensionB->category) {
                $errors[] = 'Dimensi A dan B pada satu item harus sama-sama Role atau sama-sama Need.';
            }
            if ($dimensionA && $dimensionB && $dimensionA->category === $dimensionB->category) {
                $categoryTotals[$dimensionA->category]++;
            }

            $numbers[$row['number']] = true;

            return [...$row, 'valid' => $errors === [], 'errors' => $errors];
        }, $rows);

        ksort($numbers);
        $structureValid = count($result) === 90
            && array_keys($numbers) === range(1, 90)
            && $categoryTotals === ['role' => 45, 'need' => 45];

        if (! $structureValid) {
            foreach ($result as &$row) {
                if ($row['errors'] === []) {
                    $row['errors'][] = 'File wajib memuat nomor 1–90 dengan tepat 45 item Role dan 45 item Need.';
                }
                $row['valid'] = false;
            }
        }

        if ($result === []) {
            return [[
                'row' => 2, 'number' => 0, 'dimension_a' => '', 'dimension_b' => '', 'valid' => false,
                'errors' => ['File scoring key tidak boleh kosong.'],
            ]];
        }

        return $result;
    }

    public function import(PsychologicalTestVersion $version, array $rows): void
    {
        if ($version->status !== 'draft') {
            throw ValidationException::withMessages(['scoring_file' => 'Scoring key hanya dapat diubah pada versi draft.']);
        }
        if ($version->questions()->count() !== 90) {
            throw ValidationException::withMessages(['scoring_file' => 'Import 90 pasangan soal terlebih dahulu.']);
        }

        $rows = $this->validateRows($rows);
        if (collect($rows)->contains(fn (array $row) => ! $row['valid'])) {
            throw ValidationException::withMessages(['scoring_file' => 'Import dibatalkan karena scoring key belum lengkap atau tidak valid.']);
        }

        $dimensions = PapiDimension::query()->pluck('id', 'code');
        $questions = $version->questions()->with('options')->get()->keyBy('number');

        DB::transaction(function () use ($rows, $dimensions, $questions) {
            foreach ($rows as $row) {
                $options = $questions[$row['number']]->options->keyBy('code');
                foreach (['A', 'B'] as $choice) {
                    $options[$choice]->scoringRule()->updateOrCreate([], [
                        'papi_dimension_id' => $dimensions[$row['dimension_'.strtolower($choice)]],
                        'weight' => 1,
                    ]);
                }
            }
        });
    }

    public function templateResponse(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([self::HEADERS]);
        foreach (range(1, 90) as $number) {
            $sheet->fromArray([[$number, '', '']], null, 'A'.($number + 1));
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach (['A', 'B', 'C'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $path = tempnam(sys_get_temp_dir(), 'inka-papi-scoring-template-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'template-scoring-key-papi.xlsx')->deleteFileAfterSend(true);
    }
}
