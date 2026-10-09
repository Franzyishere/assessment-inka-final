<?php

use App\Mail\AssessmentAccessMail;
use App\Models\AssessmentParticipant;
use App\Models\AssessmentProgram;
use App\Models\AssessmentProgramSimulation;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use App\Models\User;
use App\Services\AssessmentInvitationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 23)->setTime(8, 0));
    config(['assessment_access.mailer' => 'smtp']);
    Mail::fake();
    Queue::fake();
});

function setupProgramFixture(array $programAttributes = []): array
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $assessor = User::factory()->create(['role' => User::ROLE_ASESOR]);

    $type1 = SimulationType::firstOrCreate(['code' => SimulationType::PROBLEM_ANALYSIS], ['name' => 'PA', 'sequence' => 1, 'delivery_mode' => 'multi_page_response']);
    $type2 = SimulationType::firstOrCreate(['code' => SimulationType::LGD], ['name' => 'LGD', 'sequence' => 2, 'delivery_mode' => 'sync_discussion']);
    $type3 = SimulationType::firstOrCreate(['code' => SimulationType::CRITICAL_INCIDENT], ['name' => 'S3', 'sequence' => 3, 'delivery_mode' => 'case_response']);
    $type4 = SimulationType::firstOrCreate(['code' => SimulationType::PRESENTATION], ['name' => 'Pres', 'sequence' => 4, 'delivery_mode' => 'presentation_upload']);

    $scenarios = [
        SimulationScenario::firstOrCreate(['code' => 'PA-1'], ['simulation_type_id' => $type1->id, 'title' => 'PA', 'duration_minutes' => 60, 'created_by' => $admin->id]),
        SimulationScenario::firstOrCreate(['code' => 'LGD-1'], ['simulation_type_id' => $type2->id, 'title' => 'LGD', 'duration_minutes' => 45, 'created_by' => $admin->id]),
        SimulationScenario::firstOrCreate(['code' => 'S3-CI1'], ['simulation_type_id' => $type3->id, 'title' => 'CI 1', 'simulation_package' => 'ci_1', 'duration_minutes' => 60, 'created_by' => $admin->id]),
        SimulationScenario::firstOrCreate(['code' => 'PRES-1'], ['simulation_type_id' => $type4->id, 'title' => 'Pres', 'duration_minutes' => 60, 'created_by' => $admin->id]),
    ];

    $program = AssessmentProgram::create(array_merge([
        'code' => 'PROG-' . uniqid(),
        'name' => 'Program Otomatis Undangan',
        'status' => 'draft',
        'starts_at' => now()->setTime(10, 0),
        'ends_at' => now()->setTime(17, 0),
        'auto_send_invitations' => true,
        'created_by' => $admin->id,
    ], $programAttributes));

    foreach ($scenarios as $scenario) {
        AssessmentProgramSimulation::create([
            'assessment_program_id' => $program->id,
            'simulation_scenario_id' => $scenario->id,
            'status' => 'scheduled',
        ]);
    }

    return compact('admin', 'assessor', 'program', 'scenarios');
}

test('invitations are automatically sent 10 minutes before program starts', function () {
    extract(setupProgramFixture(['starts_at' => now()->setTime(10, 0)]));

    $participantUser1 = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT, 'email' => 'peserta1@example.test']);
    $participantUser2 = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT, 'email' => 'peserta2@example.test']);

    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser1->id, 'status' => 'assigned', 'assessment_category' => 'grade_1_to_2']);
    AssessmentParticipant::create(['assessment_program_id' => $program->id, 'user_id' => $participantUser2->id, 'status' => 'assigned', 'assessment_category' => 'grade_1_to_2']);

    // At 08:00 (2 hours before), no invitations sent
    $this->artisan('assessment:send-due-invitations')->expectsOutput('Tidak ada undangan assessment yang jatuh tempo untuk dikirimkan.')->assertSuccessful();
    expect($program->participants()->whereHas('invitation')->count())->toBe(0);

    // Travel to 09:49 (11 minutes before), still not due
    $this->travelTo(now()->setTime(9, 49));
    $this->artisan('assessment:send-due-invitations')->expectsOutput('Tidak ada undangan assessment yang jatuh tempo untuk dikirimkan.')->assertSuccessful();
    expect($program->participants()->whereHas('invitation')->count())->toBe(0);

    // Travel to 09:50 (exactly 10 minutes before starts_at 10:00)
    $this->travelTo(now()->setTime(9, 50));
    $this->artisan('assessment:send-due-invitations')->expectsOutput('2 undangan assessment masuk antrean pengiriman.')->assertSuccessful();

    expect($program->participants()->whereHas('invitation')->count())->toBe(2);
});

test('followup participant receives invitation immediately while existing participants are untouched', function () {
    $this->travelTo(now()->setTime(9, 5));
    extract(setupProgramFixture([
        'status' => 'active',
        'starts_at' => now()->setTime(9, 0),
    ]));

    $existingUser = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT, 'email' => 'existing@example.test']);
    $existingParticipant = AssessmentParticipant::create([
        'assessment_program_id' => $program->id,
        'user_id' => $existingUser->id,
        'status' => 'assigned',
        'assessment_category' => 'grade_1_to_2',
    ]);
    // Existing participant already has an invitation issued
    $service = app(AssessmentInvitationService::class);
    $existingInvitation = $service->issue($existingParticipant, $admin->id);
    $originalTokenHash = $existingInvitation->token_hash;

    // A followup participant is added
    $followupUser = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT, 'email' => 'followup@example.test']);

    // Admin saves setup with both existing and followup participant
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$existingUser->id, $followupUser->id],
        'participant_categories' => [
            $existingUser->id => 'grade_1_to_2',
            $followupUser->id => 'grade_1_to_2',
        ],
        'assessor_ids' => [$assessor->id],
    ])->assertRedirect(route('admin.assessment-programs.index'))
      ->assertSessionHas('success', fn ($msg) => str_contains($msg, '1 undangan masuk antrean pengiriman'));

    // Existing participant's invitation is unchanged (no duplicate or re-issue)
    expect($existingInvitation->fresh()->token_hash)->toBe($originalTokenHash);

    // Followup participant got a new invitation immediately
    $followupParticipant = AssessmentParticipant::where('assessment_program_id', $program->id)->where('user_id', $followupUser->id)->firstOrFail();
    expect($followupParticipant->invitation)->not->toBeNull();
});

test('legacy manual programs stay manual when setup is saved', function (array $extra) {
    extract(setupProgramFixture([
        'status' => 'active',
        'starts_at' => now()->setTime(9, 0),
        'auto_send_invitations' => false,
    ]));

    $user = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT, 'email' => 'manual@example.test']);

    // A client cannot re-enable a historical opt-out through setup payloads.
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id],
        'participant_categories' => [$user->id => 'grade_1_to_2'],
        'assessor_ids' => [$assessor->id],
        ...$extra,
    ])->assertRedirect(route('admin.assessment-programs.index'));

    expect($program->fresh()->auto_send_invitations)->toBeFalse();

    $participant = AssessmentParticipant::where('assessment_program_id', $program->id)->where('user_id', $user->id)->firstOrFail();
    expect($participant->invitation)->toBeNull();

    // Running the command also respects the false flag
    $this->artisan('assessment:send-due-invitations')->expectsOutput('Tidak ada undangan assessment yang jatuh tempo untuk dikirimkan.')->assertSuccessful();
    expect($participant->fresh()->invitation)->toBeNull();
})->with([ 'no toggle' => [[]], 'forged toggle' => [['auto_send_invitations' => '1']] ]);

test('new programs always enable automatic invitations without a checkbox', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->actingAs($admin)->post(route('admin.assessment-programs.store'), [
        'name' => 'Program Baru Otomatis', 'status' => 'draft',
        'starts_at' => now()->setTime(10, 0)->toDateTimeString(),
        'ends_at' => now()->setTime(16, 0)->toDateTimeString(),
        'auto_send_invitations' => '0',
    ])->assertRedirect(route('admin.assessment-programs.index'))->assertSessionHasNoErrors();
    $program = AssessmentProgram::where('name', 'Program Baru Otomatis')->firstOrFail();
    expect($program->auto_send_invitations)->toBeTrue();
    $this->actingAs($admin)->get(route('admin.assessment-programs.setup.edit', $program))
        ->assertOk()->assertDontSee('name="auto_send_invitations"', false)
        ->assertSee('Undangan diproses otomatis');
});

test('setup cannot disable automatic sending and repeat scheduling does not duplicate invitations', function () {
    extract(setupProgramFixture(['starts_at' => now()->subMinutes(5)]));
    $user = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT]);
    $this->actingAs($admin)->put(route('admin.assessment-programs.setup.update', $program), [
        'participant_ids' => [$user->id],
        'participant_categories' => [$user->id => 'grade_1_to_2'],
        'assessor_ids' => [$assessor->id], 'auto_send_invitations' => '0',
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($program->fresh()->auto_send_invitations)->toBeTrue();
    $invitation = $program->participants()->firstOrFail()->invitation;
    expect($invitation)->not->toBeNull();
    $token = $invitation->token_hash;
    expect(AssessmentProgram::sendDueInvitations())->toBe(0);
    expect($invitation->fresh()->token_hash)->toBe($token);
    expect($invitation->deliveries()->where('kind', 'invitation')->count())->toBe(1);
});

test('archived programs never receive automatic invitations', function () {
    extract(setupProgramFixture(['starts_at' => now()->subMinutes(5)]));
    $program->forceFill(['archived_at' => now()])->save();
    $user = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT]);
    $participant = AssessmentParticipant::create([
        'assessment_program_id' => $program->id, 'user_id' => $user->id,
        'status' => 'assigned', 'assessment_category' => 'grade_1_to_2',
    ]);
    expect(AssessmentProgram::sendDueInvitations())->toBe(0);
    expect(app(AssessmentInvitationService::class)->issue($participant, $admin->id, automatic: true))->toBeNull();
    Queue::assertNothingPushed();
});
