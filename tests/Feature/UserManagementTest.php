<?php

use App\Models\AssessmentProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('super admin creates and updates users with any supported role', function () {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    $this->actingAs($superAdmin)->get(route('super-admin.users.index'))->assertOk();
    $this->actingAs($superAdmin)->post(route('super-admin.users.store'), [
        'name' => 'Asesor Baru', 'email' => 'asesor.baru@example.test', 'role' => User::ROLE_ASESOR,
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertRedirect(route('super-admin.users.index'));

    $user = User::where('email', 'asesor.baru@example.test')->firstOrFail();
    expect($user->role)->toBe(User::ROLE_ASESOR);
    $this->actingAs($superAdmin)->put(route('super-admin.users.update', $user), [
        'name' => 'Asesor Diperbarui', 'email' => $user->email, 'role' => User::ROLE_ADMIN,
        'password' => '', 'password_confirmation' => '',
    ])->assertRedirect(route('super-admin.users.index'));
    expect($user->fresh()->name)->toBe('Asesor Diperbarui')->and($user->fresh()->role)->toBe(User::ROLE_ADMIN);
});

test('admin hcga only manages assessment participant accounts', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $this->actingAs($admin)->post(route('admin.participants.store'), [
        'name' => 'Peserta Baru', 'email' => 'peserta.baru@example.test',
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertRedirect(route('admin.participants.index'));
    expect(User::where('email', 'peserta.baru@example.test')->firstOrFail()->role)->toBe(User::ROLE_PESERTA_ASSESSMENT);

    $this->actingAs($admin)->get(route('admin.participants.edit', $assessor))->assertNotFound();
    $this->actingAs($admin)->put(route('admin.participants.update', $assessor), [
        'name' => 'Tidak Boleh', 'email' => $assessor->email,
    ])->assertNotFound();
});

test('admin cannot access super admin user management', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $this->actingAs($admin)->get(route('super-admin.users.index'))->assertForbidden();
});

test('admin can delete an unused assessment participant but not one assigned to a program', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $unused = User::factory()->create(['role' => User::ROLE_PESERTA_ASSESSMENT]);

    $this->actingAs($admin)->delete(route('admin.participants.destroy', $unused))
        ->assertRedirect(route('admin.participants.index'));
    $this->assertDatabaseMissing('users', ['id' => $unused->id]);

    $assigned = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $program = AssessmentProgram::create([
        'code' => 'PARTICIPANT-DELETE-GUARD', 'name' => 'Program Guard', 'status' => 'draft', 'created_by' => $admin->id,
    ]);
    $program->participants()->create(['user_id' => $assigned->id, 'status' => 'assigned']);

    $this->actingAs($admin)->delete(route('admin.participants.destroy', $assigned))->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $assigned->id]);
});
