<?php

use App\Models\User;

test('login screen redirects to home page with login modal open', function () {
    $response = $this->get('/login');

    $response->assertRedirect($this->localeRoute('home', ['modal' => 'login'], absolute: false));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'language_code' => 'en-US',
    ]);

    $response = $this->withHeader('Accept-Language', $this->testLocale)->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect();

    $location = $response->headers->get('Location');
    expect($location)->toContain('/prompt-builder');

    $path = parse_url($location, PHP_URL_PATH) ?? '';
    $segments = array_values(array_filter(explode('/', $path)));
    $locale = $segments[0] ?? null;

    expect($locale)->toBeIn(config('app.supported_locales'));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
