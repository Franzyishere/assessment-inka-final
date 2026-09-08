<?php

use App\Models\AssessmentProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('super admin creates and updates internal users', function () {
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

test('super admin can delete unused internal accounts with an audit record', function (string $role) {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    $target = User::factory()->create(['role' => $role]);
    $this->actingAs($superAdmin)->get(route('super-admin.users.index'))->assertOk()
        ->assertSee(route('super-admin.users.destroy', $target), false)->assertSee('Hapus Pengguna?');
    $this->delete(route('super-admin.users.destroy', $target))
        ->assertRedirect(route('super-admin.users.index'))->assertSessionHas('success');
    $this->assertDatabaseMissing('users', ['id' => $target->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted', 'subject_id' => $target->id, 'user_id' => $superAdmin->id]);
})->with(User::STAFF_ROLES);

test('user deletion protects own account participant accounts and program history', function () {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $program = AssessmentProgram::create(['code' => 'DELETE-USER-GUARD', 'name' => 'Guard', 'status' => 'draft', 'created_by' => $admin->id]);
    $this->actingAs($superAdmin)->delete(route('super-admin.users.destroy', $superAdmin))->assertSessionHas('error');
    $this->delete(route('super-admin.users.destroy', $participant))->assertNotFound();
    $this->delete(route('super-admin.users.destroy', $admin))->assertSessionHas('error');
    foreach ([$superAdmin, $participant, $admin] as $user) {
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
    $this->assertDatabaseHas('assessment_programs', ['id' => $program->id]);
    $this->assertDatabaseMissing('audit_logs', ['action' => 'user.deleted']);
});

test('only super admin can delete internal accounts', function () {
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $target = User::factory()->create(['role' => User::ROLE_ASESOR]);
    $this->actingAs($admin)->delete(route('super-admin.users.destroy', $target))->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $target->id]);
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

test('internal user list and search exclude participants while participant menu remains accessible', function () {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    $participant = User::factory()->create(['name' => 'Separation Match Participant', 'role' => User::ROLE_PESERTA_ASSESSMENT]);
    $assessor = User::factory()->create(['name' => 'Separation Match Assessor', 'role' => User::ROLE_ASESOR]);

    foreach (['', 'Separation Match'] as $search) {
        $this->actingAs($superAdmin)->get(route('super-admin.users.index', ['search' => $search]))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->contains('id', $assessor->id)
                && $users->every(fn ($user) => in_array($user->role, User::STAFF_ROLES, true)));
    }

    $this->get(route('admin.participants.index', ['search' => $participant->email]))
        ->assertOk()->assertSee($participant->email);

    $this->get(route('super-admin.users.create'))->assertOk()->assertDontSee('value="peserta_assessment"', false);
    $this->get(route('super-admin.users.edit', $assessor))->assertOk()->assertDontSee('value="peserta_assessment"', false);
});

test('internal user endpoints cannot create or convert participant accounts', function () {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    $assessor = User::where('role', User::ROLE_ASESOR)->firstOrFail();
    $participant = User::where('role', User::ROLE_PESERTA_ASSESSMENT)->firstOrFail();

    $this->actingAs($superAdmin)->post(route('super-admin.users.store'), [
        'name' => 'Invalid Participant', 'email' => 'invalid-participant@example.test',
        'role' => User::ROLE_PESERTA_ASSESSMENT,
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('role');
    $this->assertDatabaseMissing('users', ['email' => 'invalid-participant@example.test']);

    $this->put(route('super-admin.users.update', $assessor), [
        'name' => $assessor->name, 'email' => $assessor->email, 'role' => User::ROLE_PESERTA_ASSESSMENT,
    ])->assertSessionHasErrors('role');
    expect($assessor->fresh()->role)->toBe(User::ROLE_ASESOR);

    $this->get(route('super-admin.users.edit', $participant))->assertNotFound();
    $this->put(route('super-admin.users.update', $participant), [
        'name' => 'Changed', 'email' => $participant->email, 'role' => User::ROLE_ADMIN,
    ])->assertNotFound();
    expect($participant->fresh()->role)->toBe(User::ROLE_PESERTA_ASSESSMENT);
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
