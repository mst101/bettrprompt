<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ-A',
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 55,
            'nature' => 80,
            'tactics' => 70,
            'identity' => 65,
        ],
    ]);
});

test('index page displays form for authenticated users', function () {
    $this->actingAs($this->user);

    $response = $this->getLocale(route('prompt-builder.index', [], false));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Index')
        ->has('auth.user')
    );
});

test('index page allows guests to access prompt builder', function () {
    $response = $this->getLocale(route('prompt-builder.index', [], false));

    $response->assertRedirect(route('login'));
});
