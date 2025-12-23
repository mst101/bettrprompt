<?php

test('registration route redirects to home page with register modal open', function () {
    $response = $this->get('/register');

    $response->assertRedirect(route('home', ['modal' => 'register'], absolute: false));
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('prompt-builder.index', absolute: false));
});
