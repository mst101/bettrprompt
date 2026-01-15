<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonality()->create();
});

test('index page displays form for authenticated users', function () {
    $this->actingAs($this->user);

    $response = $this->getCountry(route('prompt-builder.index', [], false));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Index')
        ->has('auth.user')
    );
});

test('index page allows guests to access prompt builder', function () {
    $response = $this->getCountry(route('prompt-builder.index', [], false));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Index')
    );
});
