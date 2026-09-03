<!--  --><?php

use App\Models\AssessmentProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('super admin can access every assessment administration workspace', function () {
    $superAdmin = User::query()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($superAdmin)->get(route('admin.assessment-programs.index'))->assertOk();
    $this->actingAs($superAdmin)->get(route('admin.simulations.index'))->assertOk();
    $this->actingAs($superAdmin)->get(route('admin.participants.index'))->assertOk();
    $this->actingAs($superAdmin)->get(route('admin.assessor-assignments.index'))->assertOk();
    $this->actingAs($superAdmin)->get(route('admin.monitoring.index'))->assertOk();
    $this->actingAs($superAdmin)->get(route('admin.recruitment.index'))->assertOk();
});

test('super admin can create assessment program data', function () {
    $superAdmin = User::query()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($superAdmin)->post(route('admin.assessment-programs.store'), [
        'name' => 'Assessment Corporate 2026',
        'description' => 'Dikelola oleh Super Admin.',
        'starts_at' => '2026-09-01 08:00:00',
        'ends_at' => '2026-09-01 17:00:00',
        'status' => 'draft',
    ])->assertRedirect(route('admin.assessment-programs.index'));

    $program = AssessmentProgram::query()->where('name', 'Assessment Corporate 2026')->firstOrFail();

    expect($program->creator->is($superAdmin))->toBeTrue();
});

test('super admin cannot perform assessor or participant operational actions', function () {
    $superAdmin = User::query()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($superAdmin)->get(route('asesor.reviews.index'))->assertForbidden();
    $this->actingAs($superAdmin)->get(route('peserta-assessment.simulations.index'))->assertForbidden();
});
