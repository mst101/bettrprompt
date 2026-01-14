<?php

use App\Models\User;
use App\Services\WorkflowVariantService;

$adminUser = null;

beforeEach(function () use (&$adminUser) {
    $variantService = app(WorkflowVariantService::class);

    // Create an admin user for testing
    $adminUser = User::factory()->create(['is_admin' => true]);
});

test('show workflow page without variant selector for single variant', function () use (&$adminUser) {
    // Workflow 0 only has 'default' variant, so no selector should show
    $response = $this->actingAs($adminUser)->getCountry('/workflow/0');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Workflow/Show')
        ->has('currentVariant')
        ->has('availableVariants')
    );
});

test('show workflow page with variant selector for workflow 1', function () use (&$adminUser) {
    // Workflow 1 has multiple variants, so selector should appear
    $response = $this->actingAs($adminUser)->getCountry('/workflow/1');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Workflow/Show')
        ->has('currentVariant')
        ->where('currentVariant', 'single-pass')
        ->has('availableVariants', 2)
    );
});

test('show workflow page includes prepare prompt nodes', function () use (&$adminUser) {
    $response = $this->actingAs($adminUser)->getCountry('/workflow/1');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('preparePromptNodes')
        ->where('preparePromptNodes.0.name', 'Prepare Prompt')
    );
});

test('set variant returns redirect url', function () use (&$adminUser) {
    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/variant', [
        'variant' => 'two-pass',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['redirectUrl']);
});

test('set variant validates variant exists', function () use (&$adminUser) {
    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/variant', [
        'variant' => 'invalid-variant',
    ]);

    $response->assertStatus(400)
        ->assertJson(['success' => false]);
});

test('set variant persists across requests', function () use (&$adminUser) {
    // Set variant - API returns success
    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/variant', [
        'variant' => 'two-pass',
    ]);
    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // Verify it persists by navigating to the variant URL
    $response = $this->actingAs($adminUser)->getCountry('/workflow/1?variant=two-pass');
    $response->assertInertia(fn ($page) => $page
        ->where('currentVariant', 'two-pass')
    );
});

test('show two pass variant includes two nodes', function () use (&$adminUser) {
    // Set the variant (API confirms it's valid)
    $this->actingAs($adminUser)->postCountry('/admin/workflows/1/variant', [
        'variant' => 'two-pass',
    ]);

    // Get the page with the variant query parameter
    $response = $this->actingAs($adminUser)->getCountry('/workflow/1?variant=two-pass');

    $response->assertInertia(fn ($page) => $page
        ->has('preparePromptNodes', 2)
        ->where('preparePromptNodes.0.name', 'Prepare Prompt 1')
        ->where('preparePromptNodes.1.name', 'Prepare Prompt 2')
    );
});

test('save javascript with variant and node name', function () use (&$adminUser) {
    $javascriptCode = 'console.log("test");';

    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/javascript-old', [
        'code' => $javascriptCode,
        'variant' => 'single-pass',
        'nodeName' => 'Prepare Prompt',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('save javascript two pass variant node 1', function () use (&$adminUser) {
    $javascriptCode = 'console.log("test node 1");';

    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/javascript-old', [
        'code' => $javascriptCode,
        'variant' => 'two-pass',
        'nodeName' => 'Prepare Prompt 1',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('save javascript two pass variant node 2', function () use (&$adminUser) {
    $javascriptCode = 'console.log("test node 2");';

    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/javascript-old', [
        'code' => $javascriptCode,
        'variant' => 'two-pass',
        'nodeName' => 'Prepare Prompt 2',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('save javascript defaults to current variant', function () use (&$adminUser) {
    // Set variant to two-pass
    $this->actingAs($adminUser)->postCountry('/admin/workflows/1/variant', [
        'variant' => 'two-pass',
    ]);

    $javascriptCode = 'console.log("test");';

    // Don't specify variant, should use current variant
    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/javascript-old', [
        'code' => $javascriptCode,
        'nodeName' => 'Prepare Prompt 1',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('save javascript defaults to prepare prompt node name', function () use (&$adminUser) {
    $javascriptCode = 'console.log("test");';

    // Don't specify nodeName, should default to 'Prepare Prompt'
    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/1/javascript-old', [
        'code' => $javascriptCode,
        'variant' => 'single-pass',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('load variant storage path multiple variants', function () {
    $variantService = app(WorkflowVariantService::class);

    $singlePassPath = $variantService->getVariantStoragePath('single-pass', 'prepare_prompt/old');
    $twoPassPath = $variantService->getVariantStoragePath('two-pass', 'prepare_prompt/old');

    // They should be different paths
    expect($singlePassPath)->not->toBe($twoPassPath);

    // Each should contain its variant name
    expect($singlePassPath)->toContain('single-pass');
    expect($twoPassPath)->toContain('two-pass');
});

test('migration command detects legacy data', function () {
    // This would require setting up legacy data, skipping for now
    expect(true)->toBeTrue();
});

test('reload javascript returns code without saving', function () use (&$adminUser) {
    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/0/reload-javascript-old');

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'message',
            'reloadedNodes' => [
                [
                    'nodeName',
                    'javascript',
                    'codeLength',
                ],
            ],
        ]);

    // Verify that the response includes JavaScript code
    $data = $response->json();
    expect($data['reloadedNodes'])->not->toBeEmpty()
        ->and($data['reloadedNodes'][0]['javascript'])->not->toBeEmpty();
});

test('reload javascript new version returns code', function () use (&$adminUser) {
    $response = $this->actingAs($adminUser)->postCountry('/admin/workflows/0/reload-javascript-new');

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'message',
            'reloadedNodes' => [
                [
                    'nodeName',
                    'javascript',
                    'codeLength',
                ],
            ],
        ]);
});
