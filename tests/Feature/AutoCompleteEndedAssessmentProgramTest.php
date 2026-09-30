<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

test('artisan command completes active programs 1 hour after ends_at has passed', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

    // 1. Program active with ends_at 1 hour and 5 minutes ago -> SHOULD BE COMPLETED
    $dueForCompletion = AssessmentProgram::create([
        'code' => 'DUE-COMPLETION-01',
        'name' => 'Program Lewat 1 Jam Selesai',
        'status' => 'active',
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHour()->subMinutes(5),
        'created_by' => $admin->id,
    ]);

    // 2. Program active with ends_at 30 minutes ago (buffer < 1 hour) -> SHOULD REMAIN ACTIVE
    $stillWithinBuffer = AssessmentProgram::create([
        'code' => 'WITHIN-BUFFER-01',
        'name' => 'Program Masih Dalam Buffer 1 Jam',
        'status' => 'active',
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subMinutes(30),
        'created_by' => $admin->id,
    ]);

    // 3. Program active with ends_at in the future -> SHOULD REMAIN ACTIVE
    $futureProgram = AssessmentProgram::create([
        'code' => 'FUTURE-ACTIVE-01',
        'name' => 'Program Masih Berjalan',
        'status' => 'active',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHours(2),
        'created_by' => $admin->id,
    ]);

    // 4. Program active with ends_at NULL -> SHOULD REMAIN ACTIVE
    $openEndedProgram = AssessmentProgram::create([
        'code' => 'OPEN-ENDED-01',
        'name' => 'Program Tanpa Batas Selesai',
        'status' => 'active',
        'starts_at' => now()->subHours(2),
        'ends_at' => null,
        'created_by' => $admin->id,
    ]);

    // 5. Program draft with ends_at in the past -> SHOULD REMAIN DRAFT
    $draftProgram = AssessmentProgram::create([
        'code' => 'PAST-DRAFT-01',
        'name' => 'Program Draft Masa Lalu',
        'status' => 'draft',
        'starts_at' => now()->subHours(4),
        'ends_at' => now()->subHours(2),
        'created_by' => $admin->id,
    ]);

    Artisan::call('assessment:complete-ended-programs');

    expect($dueForCompletion->fresh()->status)->toBe('completed')
        ->and($stillWithinBuffer->fresh()->status)->toBe('active')
        ->and($futureProgram->fresh()->status)->toBe('active')
        ->and($openEndedProgram->fresh()->status)->toBe('active')
        ->and($draftProgram->fresh()->status)->toBe('draft');
});

test('admin assessment programs list synchronizes ended programs at runtime', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

    $dueProgram = AssessmentProgram::create([
        'code' => 'RUNTIME-DUE-01',
        'name' => 'Program Selesai Runtime Sync',
        'status' => 'active',
        'starts_at' => now()->subHours(4),
        'ends_at' => now()->subHour()->subMinutes(2),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)->get(route('admin.assessment-programs.index'))
        ->assertOk();

    expect($dueProgram->fresh()->status)->toBe('completed');
});

