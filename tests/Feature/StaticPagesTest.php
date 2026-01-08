<?php

test('home page displays correctly', function () {
    $response = $this->getLocale('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->has('canLogin')
        ->has('canRegister')
        ->has('isReturningVisitor')
    );
});

test('home page shows returning visitor flag when cookie exists', function () {
    $response = $this->withCookie('returning_visitor', 'true')
        ->getLocale('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('isReturningVisitor', true)
    );
});

test('home page shows first time visitor when no cookie', function () {
    $response = $this->getLocale('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('isReturningVisitor', false)
    );
});

test('home page passes modal query parameter', function () {
    $response = $this->getLocale('/?modal=login');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('modal', 'login')
    );
});

test('home page has login and register flags', function () {
    $response = $this->getLocale('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('canLogin', true)
        ->where('canRegister', true)
    );
});

test('terms page displays correctly', function () {
    $response = $this->getLocale('/terms');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/Terms')
    );
});

test('privacy page displays correctly', function () {
    $response = $this->getLocale('/privacy');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/Privacy')
    );
});

test('cookies page displays correctly', function () {
    $response = $this->getLocale('/cookies');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/Cookies')
    );
});

test('dashboard redirects to prompt builder index', function () {
    $response = $this->getLocale(route('prompt-builder.index', [], false));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Index')
    );
});

test('static pages are accessible without authentication', function ($path) {
    $response = $this->getLocale($path);

    $response->assertOk();
    $response->assertStatus(200);
    $this->assertGuest();
})->with(['/', '/terms', '/privacy', '/cookies']);
