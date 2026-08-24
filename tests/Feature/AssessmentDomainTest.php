<?php

use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\AssessorAssignment;
use App\Models\SimulationMaterialPage;
use App\Models\SimulationReview;
use App\Models\SimulationScenario;
use App\Models\SimulationSession;
use App\Models\SimulationSubmission;
use App\Models\SimulationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('four official simulation types are seeded in the correct order', function () {
    expect(SimulationType::query()->orderBy('sequence')->pluck('code')->all())->toBe([
        SimulationType::PROBLEM_ANALYSIS,
        SimulationType::LGD,
        SimulationType::CRITICAL_INCIDENT,
        SimulationType::PRESENTATION,
    ]);
});

test('problem analysis supports ordered multi page material', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $type = SimulationType::query()->where('code', SimulationType::PROBLEM_ANALYSIS)->firstOrFail();
    $scenario = SimulationScenario::create([
        'simulation_type_id' => $type->id,
        'code' => 'SIM-01-TEST',
        'title' => 'Problem Analysis Test',
        'duration_minutes' => 90,
        'status' => 'active',
        'created_by' => $admin->id,
    ]);

    foreach (range(1, 7) as $page) {
        SimulationMaterialPage::create([
            'simulation_scenario_id' => $scenario->id,
            'title' => "Materi {$page}",
            'content' => "Isi materi halaman {$page}",
            'page_order' => $page,
        ]);
    }

    expect($scenario->materialPages()->count())->toBe(7)
        ->and($scenario->materialPages->first()->page_order)->toBe(1)
        ->and($scenario->materialPages->last()->page_order)->toBe(7);
});

test('program links participants assessors sessions submissions and reviews', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $asesor = User::query()->where('role', User::ROLE_ASESOR)->firstOrFail();
    $participantUser = User::query()->where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $presentationType = SimulationType::query()->where('code', SimulationType::PRESENTATION)->firstOrFail();

    $program = AssessmentProgram::create([
        'code' => 'PROGRAM-TEST',
        'name' => 'Program Assessment Test',
        'status' => 'active',
        'created_by' => $admin->id,
    ]);
    $scenario = SimulationScenario::create([
        'simulation_type_id' => $presentationType->id,
        'code' => 'SIM-04-TEST',
        'title' => 'Presentasi Strategis',
        'duration_minutes' => 60,
        'status' => 'active',
        'created_by' => $admin->id,
    ]);
    $programSimulation = AssessmentProgramSimulation::create([
        'assessment_program_id' => $program->id,
        'simulation_scenario_id' => $scenario->id,
        'status' => 'open',
    ]);
    $participant = AssessmentParticipant::create([
        'assessment_program_id' => $program->id,
        'user_id' => $participantUser->id,
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);
    AssessorAssignment::create([
        'assessment_program_simulation_id' => $programSimulation->id,
        'assessor_id' => $asesor->id,
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);
    $session = SimulationSession::create([
        'assessment_program_simulation_id' => $programSimulation->id,
        'assessment_participant_id' => $participant->id,
        'status' => 'submitted',
        'started_at' => now()->subHour(),
        'submitted_at' => now(),
    ]);
    $submission = SimulationSubmission::create([
        'simulation_session_id' => $session->id,
        'original_filename' => 'presentasi.pptx',
        'storage_path' => 'simulation-submissions/presentation/private-file.pptx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'file_size' => 1024,
        'file_checksum' => str_repeat('a', 64),
        'submitted_at' => now(),
    ]);
    $review = SimulationReview::create([
        'simulation_session_id' => $session->id,
        'assessor_id' => $asesor->id,
        'recommendation' => SimulationReview::RECOMMENDED,
        'assessment_notes' => 'Materi tersusun dengan baik.',
        'status' => 'final',
        'reviewed_at' => now(),
    ]);

    expect($program->participants->first()->user->is($participantUser))->toBeTrue()
        ->and($programSimulation->assessorAssignments->first()->assessor->is($asesor))->toBeTrue()
        ->and($submission->session->participant->user->is($participantUser))->toBeTrue()
        ->and($review->session->programSimulation->scenario->type->code)->toBe(SimulationType::PRESENTATION);
});

test('participant can only be assigned once to the same program', function () {
    $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    $participant = User::query()->where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $program = AssessmentProgram::create([
        'code' => 'PROGRAM-UNIQUE',
        'name' => 'Unique Assignment Program',
        'status' => 'draft',
        'created_by' => $admin->id,
    ]);

    AssessmentParticipant::create([
        'assessment_program_id' => $program->id,
        'user_id' => $participant->id,
    ]);

    expect(fn () => AssessmentParticipant::create([
        'assessment_program_id' => $program->id,
        'user_id' => $participant->id,
    ]))->toThrow(QueryException::class);
});
