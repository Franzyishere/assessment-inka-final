<?php

use App\Models\User;

it('redirects every role from root to the talent portal', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('portal.index'));
})->with([
    User::ROLE_SUPER_ADMIN,
    User::ROLE_ADMIN,
    User::ROLE_ASESOR,
    User::ROLE_PESERTA_ASSESSMENT,
]);

it('allows every role to open its own dashboard', function (string $role, string $routeName) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route($routeName))->assertOk();
})->with([
    [User::ROLE_SUPER_ADMIN, 'super-admin.dashboard'],
    [User::ROLE_ADMIN, 'admin.dashboard'],
    [User::ROLE_ASESOR, 'asesor.dashboard'],
    [User::ROLE_PESERTA_ASSESSMENT, 'peserta-assessment.dashboard'],
]);

test('user cannot open another role dashboard', function () {
    $asesor = User::factory()->create(['role' => User::ROLE_ASESOR]);

    $this->actingAs($asesor)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
