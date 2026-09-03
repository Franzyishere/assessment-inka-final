<?php

namespace App\Services\Recruitment;

use App\Models\RecruitmentBatch;
use App\Models\RecruitmentParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecruitmentParticipantImportService
{
    public const HEADERS = ['nomor_peserta', 'nama', 'email'];

    public function read(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        $headers = array_map(fn ($value) => Str::of((string) $value)->lower()->trim()->replace(' ', '_')->toString(), array_shift($rows) ?? []);

        if (array_slice($headers, 0, 3) !== self::HEADERS) {
            throw ValidationException::withMessages([
                'participant_file' => 'Kolom file harus berurutan: nomor_peserta, nama, email.',
            ]);
        }

        $normalized = [];
        foreach ($rows as $index => $row) {
            $number = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            $email = Str::lower(trim((string) ($row[2] ?? '')));
            if ($number === '' && $name === '' && $email === '') {
                continue;
            }
            $normalized[] = ['row' => $index + 2, 'participant_number' => $number, 'name' => $name, 'email' => $email];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages(['participant_file' => 'File tidak berisi data peserta.']);
        }
        if (count($normalized) > 1000) {
            throw ValidationException::withMessages(['participant_file' => 'Maksimal 1.000 peserta dalam satu proses import.']);
        }

        return $this->validateRows($normalized);
    }

    public function validateRows(array $rows): array
    {
        $numbers = [];
        $emails = [];

        return array_map(function (array $row) use (&$numbers, &$emails) {
            $errors = [];
            if ($row['participant_number'] === '' || mb_strlen($row['participant_number']) > 100) {
                $errors[] = 'Nomor peserta wajib diisi dan maksimal 100 karakter.';
            }
            if ($row['name'] === '' || mb_strlen($row['name']) > 255) {
                $errors[] = 'Nama wajib diisi dan maksimal 255 karakter.';
            }
            if (! filter_var($row['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($row['email']) > 255) {
                $errors[] = 'Format email tidak valid.';
            }
            if (isset($numbers[$row['participant_number']])) {
                $errors[] = 'Nomor peserta duplikat di dalam file.';
            }
            if (isset($emails[$row['email']])) {
                $errors[] = 'Email duplikat di dalam file.';
            }
            $numbers[$row['participant_number']] = true;
            $emails[$row['email']] = true;

            $existingProfile = RecruitmentParticipant::where('participant_number', $row['participant_number'])->with('user')->first();
            $existingUser = User::where('email', $row['email'])->first();
            if ($existingProfile && $existingProfile->user->email !== $row['email']) {
                $errors[] = 'Nomor peserta sudah digunakan oleh email lain.';
            }
            if ($existingUser && ! $existingUser->hasRole(User::ROLE_PESERTA_REKRUTMEN)) {
                $errors[] = 'Email sudah digunakan oleh role lain.';
            }
            if ($existingUser?->recruitmentParticipant && $existingUser->recruitmentParticipant->participant_number !== $row['participant_number']) {
                $errors[] = 'Email sudah memiliki nomor peserta yang berbeda.';
            }
            if ($existingUser && $existingProfile && ! $existingProfile->user->is($existingUser)) {
                $errors[] = 'Nomor peserta dan email mengarah ke akun yang berbeda.';
            }

            return [...$row, 'errors' => $errors, 'valid' => $errors === [], 'existing' => (bool) ($existingProfile || $existingUser)];
        }, $rows);
    }

    public function import(RecruitmentBatch $batch, array $rows): array
    {
        if (collect($rows)->contains(fn ($row) => ! ($row['valid'] ?? false))) {
            throw ValidationException::withMessages(['participant_file' => 'Import dibatalkan karena masih terdapat baris tidak valid.']);
        }

        return DB::transaction(function () use ($batch, $rows) {
            $credentials = [];
            foreach ($rows as $row) {
                $user = User::where('email', $row['email'])->first();
                $password = null;
                if (! $user) {
                    $password = Str::password(12, symbols: false);
                    $user = User::create([
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'password' => $password,
                        'role' => User::ROLE_PESERTA_REKRUTMEN,
                    ]);
                }

                $participant = RecruitmentParticipant::firstOrCreate(
                    ['user_id' => $user->id],
                    ['participant_number' => $row['participant_number']]
                );
                $batch->participants()->syncWithoutDetaching([
                    $participant->id => ['status' => 'registered', 'assigned_at' => now()],
                ]);
                $credentials[] = [
                    'participant_number' => $participant->participant_number,
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $password ?? 'Akun sudah tersedia—gunakan password lama',
                    'is_new' => $password !== null,
                ];
            }

            return $credentials;
        });
    }

    public function templateResponse(): BinaryFileResponse
    {
        return $this->spreadsheetResponse('template-import-peserta-rekrutmen.xlsx', [
            ['nomor_peserta', 'nama', 'email'],
            ['INKA-REC-0001', 'Nama Peserta', 'peserta@example.com'],
        ]);
    }

    public function credentialResponse(RecruitmentBatch $batch, array $credentials): BinaryFileResponse
    {
        return $this->spreadsheetResponse('kredensial-'.$batch->id.'-'.now()->format('YmdHis').'.xlsx', [
            ['nomor_peserta', 'nama', 'email', 'password_awal'],
            ...array_map(fn ($row) => [
                $row['participant_number'],
                $row['name'],
                $row['email'],
                $row['password'],
            ], $credentials),
        ]);
    }

    private function spreadsheetResponse(string $filename, array $rows): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $spreadsheet->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);
        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestColumn()) as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }
        $path = tempnam(sys_get_temp_dir(), 'inka-recruitment-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
