<?php

use App\Models\User;

it('redirects every role from root to its dashboard', function (string $role, string $routeName) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route($routeName));
})->with([
    [User::ROLE_SUPER_ADMIN, 'super-admin.dashboard'],
    [User::ROLE_ADMIN, 'admin.dashboard'],
    [User::ROLE_ASESOR, 'asesor.dashboard'],
]);

it('allows every role to open its own dashboard', function (string $role, string $routeName) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route($routeName))->assertOk();
})->with([
    [User::ROLE_SUPER_ADMIN, 'super-admin.dashboard'],
    [User::ROLE_ADMIN, 'admin.dashboard'],
    [User::ROLE_ASESOR, 'asesor.dashboard'],
]);

test('user cannot open another role dashboard', function () {
    $asesor = User::factory()->create(['role' => User::ROLE_ASESOR]);

    $this->actingAs($asesor)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
