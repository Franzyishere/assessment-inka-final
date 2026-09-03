<?php

use App\Models\RecruitmentBatch;
use App\Models\RecruitmentParticipant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('admin and super admin can manage recruitment batches', function (string $role) {
    $user = User::query()->where('role', $role)->firstOrFail();

    $this->actingAs($user)->get(route('admin.recruitment.index'))->assertOk();
    $this->actingAs($user)->post(route('admin.recruitment.store'), [
        'name' => 'Rekrutmen September 2026',
        'starts_at' => '2026-09-10 08:00:00',
        'ends_at' => '2026-09-10 12:00:00',
        'status' => 'draft',
    ])->assertRedirect();

    expect(RecruitmentBatch::where('name', 'Rekrutmen September 2026')->exists())->toBeTrue();
})->with([User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]);

test('assessor cannot access recruitment administration', function () {
    $assessor = User::query()->where('role', User::ROLE_ASESOR)->firstOrFail();

    $this->actingAs($assessor)->get(route('admin.recruitment.index'))->assertForbidden();
});

test('admin previews and imports recruitment participants from excel', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $batch = RecruitmentBatch::create(['name' => 'Batch PAPI', 'status' => 'draft', 'created_by' => $admin->id]);
    $path = tempnam(sys_get_temp_dir(), 'recruitment-test-');
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        ['nomor_peserta', 'nama', 'email'],
        ['REC-001', 'Peserta Pertama', 'peserta.pertama@example.com'],
        ['REC-002', 'Peserta Kedua', 'peserta.kedua@example.com'],
    ]);
    (new Xlsx($spreadsheet))->save($path);

    $this->actingAs($admin)->post(route('admin.recruitment.import.preview', $batch), [
        'participant_file' => new UploadedFile($path, 'peserta.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
    ])->assertRedirect(route('admin.recruitment.import.create', $batch));

    $this->actingAs($admin)->post(route('admin.recruitment.import.store', $batch))->assertDownload();

    expect(RecruitmentParticipant::count())->toBe(2)
        ->and($batch->participants()->count())->toBe(2)
        ->and(User::where('role', User::ROLE_PESERTA_REKRUTMEN)->whereIn('email', [
            'peserta.pertama@example.com',
            'peserta.kedua@example.com',
        ])->count())->toBe(2);
});

test('invalid and duplicate excel rows cannot be confirmed', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $batch = RecruitmentBatch::create(['name' => 'Batch Invalid', 'status' => 'draft', 'created_by' => $admin->id]);
    $path = tempnam(sys_get_temp_dir(), 'recruitment-test-');
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        ['nomor_peserta', 'nama', 'email'],
        ['REC-001', 'Peserta Pertama', 'email-tidak-valid'],
        ['REC-001', 'Peserta Kedua', 'peserta.kedua@example.com'],
    ]);
    (new Xlsx($spreadsheet))->save($path);

    $this->actingAs($admin)->post(route('admin.recruitment.import.preview', $batch), [
        'participant_file' => new UploadedFile($path, 'peserta.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.recruitment.import.store', $batch))->assertRedirect();

    expect(RecruitmentParticipant::count())->toBe(0);
});
