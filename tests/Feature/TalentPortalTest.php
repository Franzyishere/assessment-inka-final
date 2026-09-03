<?php

use App\Models\User;

test('guest cannot open talent portal', function () {
    $this->get(route('portal.index'))->assertRedirect(route('login'));
});

test('assessment user sees assessment and recruitment access based on role', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSee('INKA Talent Management System')
        ->assertSee('Buka Assessment')
        ->assertSee(route($user->dashboardRouteName()))
        ->assertSee('Rekrutmen')
        ->assertDontSee('Rekrutmen & Psikotes', false)
        ->when(in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true), fn ($response) => $response->assertSee('Buka Recruitment')->assertSee(route('admin.recruitment.index')))
        ->when(! in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true), fn ($response) => $response->assertDontSee('Buka Recruitment'))
        ->assertDontSee('Modul pengelolaan dan pelaksanaan tes psikologi');
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
        ->assertDontSee('Buka Assessment')
        ->assertSee('Buka Recruitment')
        ->assertSee(route('peserta-rekrutmen.exams.index'));
});

test('portal descriptions follow the signed in user role', function (string $role, string $assessmentText, string $recruitmentText) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSee($assessmentText)
        ->assertSee($recruitmentText);
})->with([
    'super admin' => [User::ROLE_SUPER_ADMIN, 'kelola pengguna', 'pantau aktivitas proses rekrutmen'],
    'admin hcga' => [User::ROLE_ADMIN, 'bank simulasi', 'Kelola kebutuhan rekrutmen'],
    'asesor' => [User::ROLE_ASESOR, 'berikan penilaian', 'Proses kandidat'],
    'peserta assessment' => [User::ROLE_PESERTA_ASSESSMENT, 'hasil assessment Anda', 'Proses kandidat'],
    'peserta rekrutmen' => [User::ROLE_PESERTA_REKRUTMEN, 'hak akses assessment', 'Pantau tahapan seleksi'],
]);
