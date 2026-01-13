<?php

use App\Models\User;
use App\Models\Visitor;

test('home page displays correctly', function () {
    $response = $this->getCountry('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->has('canLogin')
        ->has('canRegister')
        ->has('isReturningVisitor')
    );
});

test('home page shows returning visitor flag when visitor has previous visits', function () {
    // Create a returning visitor: first visit 2 hours ago, last visit now
    $visitor = Visitor::factory()->create([
        'first_visit_at' => now()->subHours(2),
        'last_visit_at' => now(),
    ]);

    $response = $this->withCookie('visitor_id', (string) $visitor->id)->getCountry('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('isReturningVisitor', true)
    );
});

test('home page shows first time visitor when no cookie', function () {
    $response = $this->getCountry('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('isReturningVisitor', false)
    );
});

test('home page passes modal query parameter', function () {
    $response = $this->getCountry('/?modal=login');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('modal', 'login')
    );
});

test('home page has login and register flags', function () {
    $response = $this->getCountry('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('canLogin', true)
        ->where('canRegister', true)
    );
});

test('terms page displays correctly', function () {
    $response = $this->getCountry('/terms');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/Terms')
    );
});

test('privacy page displays correctly', function () {
    $response = $this->getCountry('/privacy');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/Privacy')
    );
});

test('cookies page displays correctly', function () {
    $response = $this->getCountry('/cookies');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/Cookies')
    );
});

test('dashboard redirects to prompt builder index', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)
        ->getCountry(route('prompt-builder.index', [], false));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Index')
    );
});

test('static pages are accessible without authentication', function ($path) {
    $response = $this->getCountry($path);

    $response->assertOk();
    $response->assertStatus(200);
    $this->assertGuest();
})->with(['/', '/terms', '/privacy', '/cookies']);
