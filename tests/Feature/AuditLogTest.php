<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('successful login and logout are audited without passwords', function () {
    $user = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->post(route('logout'))->assertRedirect(route('login'));

    expect(AuditLog::pluck('action')->all())->toContain('auth.login', 'auth.logout');
    expect(AuditLog::get()->toJson())->not->toContain('password');
});

test('user management activity is visible only to super admin', function () {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $this->actingAs($superAdmin)->post(route('super-admin.users.store'), [
        'name' => 'Audit User', 'email' => 'audit-user@example.test', 'role' => User::ROLE_ASESOR,
        'password' => 'password123', 'password_confirmation' => 'password123',
    ]);

    expect(AuditLog::where('action', 'user.created')->exists())->toBeTrue();
    $this->actingAs($superAdmin)->get(route('super-admin.audit-logs.index'))->assertOk()->assertSee('user.created');
    $this->actingAs($admin)->get(route('super-admin.audit-logs.index'))->assertForbidden();
});

test('audit log renders nested metadata without breaking the layout', function () {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    AuditLog::create([
        'user_id' => $superAdmin->id,
        'action' => 'assessment.updated',
        'subject_type' => 'App\\Models\\AssessmentProgram',
        'subject_id' => 10,
        'metadata' => ['changes' => ['status' => ['from' => 'draft', 'to' => 'active']]],
        'ip_address' => '127.0.0.1',
        'user_agent' => str_repeat('Browser Agent ', 20),
        'occurred_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('super-admin.audit-logs.index'))
        ->assertOk()
        ->assertSee('Assessment Updated')
        ->assertSee('AssessmentProgram')
        ->assertSee('Lihat metadata');
});

test('role access matrix is available to super admin', function () {
    $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
    $this->actingAs($superAdmin)->get(route('super-admin.roles.index'))->assertOk()->assertSee('Admin HCGA')->assertSee('Peserta Assessment')->assertDontSee('Peserta Rekrutmen');
});
