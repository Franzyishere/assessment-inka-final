<?php

use App\Models\User;

test('guest cannot open assessment portal', function () {
    $this->get(route('portal.index'))->assertRedirect(route('login'));
});

test('every assessment role can open assessment from portal', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSee('INKA Assessment System')
        ->assertSee('Buka Assessment')
        ->assertSee(route($user->dashboardRouteName()))
        ->assertDontSee('Recruitment');
})->with([
    User::ROLE_SUPER_ADMIN,
    User::ROLE_ADMIN,
    User::ROLE_ASESOR,
    User::ROLE_PESERTA_ASSESSMENT,
]);

test('portal description follows the signed in assessment role', function (string $role, string $text) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSee($text);
})->with([
    'super admin' => [User::ROLE_SUPER_ADMIN, 'kelola pengguna'],
    'admin hcga' => [User::ROLE_ADMIN, 'bank simulasi'],
    'asesor' => [User::ROLE_ASESOR, 'berikan penilaian'],
    'peserta assessment' => [User::ROLE_PESERTA_ASSESSMENT, 'hasil assessment Anda'],
]);
