<?php

test('guest is redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
