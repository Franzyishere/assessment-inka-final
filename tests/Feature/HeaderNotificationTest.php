<?php

use App\Models\User;

it('requires authentication for header notifications', function () {
    $this->getJson(route('header.notifications'))->assertUnauthorized();
});

it('returns an empty summary when the role has no assessment activity', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->getJson(route('header.notifications'))->assertOk()->assertExactJson(['items' => []]);
})->with(['admin', 'super_admin', 'asesor']);
