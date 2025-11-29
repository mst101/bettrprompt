<?php

test('home page displays correctly', function () {
    $response = $this->get('/');

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
        ->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('isReturningVisitor', true)
    );
});

test('home page shows first time visitor when no cookie', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('isReturningVisitor', false)
    );
});

test('home page passes modal query parameter', function () {
    $response = $this->get('/?modal=login');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('modal', 'login')
    );
});

test('home page can login is true when login route exists', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('canLogin', true)
    );
});

test('home page can register is true when register route exists', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('canRegister', true)
    );
});

test('terms page displays correctly', function () {
    $response = $this->get('/terms');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Terms')
    );
});

test('privacy page displays correctly', function () {
    $response = $this->get('/privacy');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Privacy')
    );
});

test('cookies page displays correctly', function () {
    $response = $this->get('/cookies');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Cookies')
    );
});

test('dashboard redirects to prompt builder index', function () {
    $response = $this->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Index')
    );
});

test('static pages are accessible to guests', function () {
    $response = $this->get('/');
    $response->assertOk();

    $response = $this->get('/terms');
    $response->assertOk();

    $response = $this->get('/privacy');
    $response->assertOk();

    $response = $this->get('/cookies');
    $response->assertOk();
});

test('static pages return successful status codes', function () {
    expect($this->get('/')->status())->toBe(200);
    expect($this->get('/terms')->status())->toBe(200);
    expect($this->get('/privacy')->status())->toBe(200);
    expect($this->get('/cookies')->status())->toBe(200);
});

test('home page does not require authentication', function () {
    $response = $this->get('/');

    $response->assertOk();
    $this->assertGuest();
});

test('terms page does not require authentication', function () {
    $response = $this->get('/terms');

    $response->assertOk();
    $this->assertGuest();
});

test('privacy page does not require authentication', function () {
    $response = $this->get('/privacy');

    $response->assertOk();
    $this->assertGuest();
});

test('cookies page does not require authentication', function () {
    $response = $this->get('/cookies');

    $response->assertOk();
    $this->assertGuest();
});
