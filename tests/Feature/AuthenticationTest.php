<?php

use App\Models\User;

test('login page is available to guests', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee(asset('images/backgrounds/gedung-inka-login.jpg'), false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false);
});

test('user can authenticate with email and password', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('invalid credentials are rejected', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
