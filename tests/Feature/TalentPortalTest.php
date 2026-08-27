<?php

use App\Models\User;

test('guest cannot open talent portal', function () {
    $this->get(route('portal.index'))->assertRedirect(route('login'));
});

test('assessment user sees active assessment module and locked future modules', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSee('INKA Talent Management System')
        ->assertSee('Buka Assessment')
        ->assertSee(route($user->dashboardRouteName()))
        ->assertSee('Menunggu Kebutuhan Client')
        ->assertSee('Menunggu Finalisasi Flow');
})->with([
    User::ROLE_SUPER_ADMIN,
    User::ROLE_ADMIN,
    User::ROLE_ASESOR,
    User::ROLE_PESERTA_ASSESSMENT,
]);

test('user without assessment role cannot open assessment from portal', function () {
    $user = User::factory()->create(['role' => User::ROLE_PESERTA_REKRUTMEN]);

    $this->actingAs($user)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSee('Akses Tidak Tersedia')
        ->assertDontSee('Buka Assessment');
});
