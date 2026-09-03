<?php

namespace App\Services\PsychologicalTests;

use App\Models\PsychologicalTestVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PapiQuestionImportService
{
    public const HEADERS = ['nomor', 'pernyataan_a', 'pernyataan_b'];

    public function read(string $path): array
    {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        $headers = array_map(fn ($value) => Str::of((string) $value)->lower()->trim()->replace(' ', '_')->toString(), array_shift($rows) ?? []);
        if (array_slice($headers, 0, 3) !== self::HEADERS) {
            throw ValidationException::withMessages(['question_file' => 'Kolom file harus berurutan: nomor, pernyataan_a, pernyataan_b.']);
        }

        $normalized = [];
        foreach ($rows as $index => $row) {
            if (trim(implode('', array_map('strval', array_slice($row, 0, 3)))) === '') {
                continue;
            }
            $normalized[] = [
                'row' => $index + 2,
                'number' => filter_var($row[0] ?? null, FILTER_VALIDATE_INT) ?: 0,
                'statement_a' => trim((string) ($row[1] ?? '')),
                'statement_b' => trim((string) ($row[2] ?? '')),
            ];
        }

        return $this->validateRows($normalized);
    }

    public function validateRows(array $rows): array
    {
        $numbers = [];
        $result = array_map(function (array $row) use (&$numbers) {
            $errors = [];
            if ($row['number'] < 1 || $row['number'] > 90) {
                $errors[] = 'Nomor harus berada pada range 1–90.';
            }
            if (isset($numbers[$row['number']])) {
                $errors[] = 'Nomor soal duplikat.';
            }
            if ($row['statement_a'] === '' || mb_strlen($row['statement_a']) > 2000) {
                $errors[] = 'Pernyataan A wajib diisi dan maksimal 2.000 karakter.';
            }
            if ($row['statement_b'] === '' || mb_strlen($row['statement_b']) > 2000) {
                $errors[] = 'Pernyataan B wajib diisi dan maksimal 2.000 karakter.';
            }
            $numbers[$row['number']] = true;

            return [...$row, 'valid' => $errors === [], 'errors' => $errors];
        }, $rows);

        ksort($numbers);
        if ($result === []) {
            return [['row' => 2, 'number' => 0, 'statement_a' => '', 'statement_b' => '', 'valid' => false, 'errors' => ['File harus memuat lengkap nomor 1 sampai 90.']]];
        }
        if (count($result) !== 90 || array_keys($numbers) !== range(1, 90)) {
            foreach ($result as &$row) {
                if ($row['errors'] === []) {
                    $row['errors'][] = 'File harus memuat lengkap nomor 1 sampai 90.';
                }
                $row['valid'] = false;
            }
        }

        return $result;
    }

    public function import(PsychologicalTestVersion $version, array $rows): void
    {
        if ($version->status !== 'draft') {
            throw ValidationException::withMessages(['question_file' => 'Soal hanya dapat diubah pada versi draft.']);
        }
        $rows = $this->validateRows($rows);
        if (collect($rows)->contains(fn ($row) => ! $row['valid'])) {
            throw ValidationException::withMessages(['question_file' => 'Import dibatalkan karena data soal belum lengkap atau tidak valid.']);
        }

        DB::transaction(function () use ($version, $rows) {
            $version->questions()->delete();
            foreach ($rows as $row) {
                $question = $version->questions()->create(['number' => $row['number'], 'is_required' => true]);
                $question->options()->createMany([
                    ['code' => 'A', 'statement' => $row['statement_a'], 'display_order' => 1],
                    ['code' => 'B', 'statement' => $row['statement_b'], 'display_order' => 2],
                ]);
            }
        });
    }

    public function templateResponse(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([['nomor', 'pernyataan_a', 'pernyataan_b']]);
        foreach (range(1, 90) as $number) {
            $sheet->fromArray([[$number, '', '']], null, 'A'.($number + 1));
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach (['A', 'B', 'C'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $path = tempnam(sys_get_temp_dir(), 'inka-papi-template-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'template-import-soal-papi.xlsx')->deleteFileAfterSend(true);
    }
}
