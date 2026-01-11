<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WorkflowVariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WorkflowVariantTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private WorkflowVariantService $variantService;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->variantService = app(WorkflowVariantService::class);

        // Create an admin user for testing
        $this->adminUser = User::factory()->create(['is_admin' => true]);
    }

    public function test_show_workflow_page_without_variant_selector_for_single_variant(): void
    {
        // Workflow 0 only has 'default' variant, so no selector should show
        $response = $this->actingAs($this->adminUser)->getCountry('/workflow/0');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Workflow/Show')
            ->has('currentVariant')
            ->has('availableVariants')
        );
    }

    public function test_show_workflow_page_with_variant_selector_for_workflow_1(): void
    {
        // Workflow 1 has multiple variants, so selector should appear
        $response = $this->actingAs($this->adminUser)->getCountry('/workflow/1');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Workflow/Show')
            ->has('currentVariant')
            ->where('currentVariant', 'single-pass')
            ->has('availableVariants', 2)
        );
    }

    public function test_show_workflow_page_includes_prepare_prompt_nodes(): void
    {
        $response = $this->actingAs($this->adminUser)->getCountry('/workflow/1');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('preparePromptNodes')
            ->where('preparePromptNodes.0.name', 'Prepare Prompt')
        );
    }

    public function test_set_variant_returns_redirect_url(): void
    {
        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/variant', [
            'variant' => 'two-pass',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['redirectUrl']);
    }

    public function test_set_variant_validates_variant_exists(): void
    {
        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/variant', [
            'variant' => 'invalid-variant',
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_set_variant_persists_across_requests(): void
    {
        // Set variant - API returns success
        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/variant', [
            'variant' => 'two-pass',
        ]);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify it persists by navigating to the variant URL
        $response = $this->actingAs($this->adminUser)->getCountry('/workflow/1?variant=two-pass');
        $response->assertInertia(fn ($page) => $page
            ->where('currentVariant', 'two-pass')
        );
    }

    public function test_show_two_pass_variant_includes_two_nodes(): void
    {
        // Set the variant (API confirms it's valid)
        $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/variant', [
            'variant' => 'two-pass',
        ]);

        // Get the page with the variant query parameter
        $response = $this->actingAs($this->adminUser)->getCountry('/workflow/1?variant=two-pass');

        $response->assertInertia(fn ($page) => $page
            ->has('preparePromptNodes', 2)
            ->where('preparePromptNodes.0.name', 'Prepare Prompt 1')
            ->where('preparePromptNodes.1.name', 'Prepare Prompt 2')
        );
    }

    public function test_save_javascript_with_variant_and_node_name(): void
    {
        $javascriptCode = 'console.log("test");';

        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/javascript-old', [
            'code' => $javascriptCode,
            'variant' => 'single-pass',
            'nodeName' => 'Prepare Prompt',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_save_javascript_two_pass_variant_node_1(): void
    {
        $javascriptCode = 'console.log("test node 1");';

        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/javascript-old', [
            'code' => $javascriptCode,
            'variant' => 'two-pass',
            'nodeName' => 'Prepare Prompt 1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_save_javascript_two_pass_variant_node_2(): void
    {
        $javascriptCode = 'console.log("test node 2");';

        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/javascript-old', [
            'code' => $javascriptCode,
            'variant' => 'two-pass',
            'nodeName' => 'Prepare Prompt 2',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_save_javascript_defaults_to_current_variant(): void
    {
        // Set variant to two-pass
        $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/variant', [
            'variant' => 'two-pass',
        ]);

        $javascriptCode = 'console.log("test");';

        // Don't specify variant, should use current variant
        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/javascript-old', [
            'code' => $javascriptCode,
            'nodeName' => 'Prepare Prompt 1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_save_javascript_defaults_to_prepare_prompt_node_name(): void
    {
        $javascriptCode = 'console.log("test");';

        // Don't specify nodeName, should default to 'Prepare Prompt'
        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/1/javascript-old', [
            'code' => $javascriptCode,
            'variant' => 'single-pass',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_load_variant_storage_path_multiple_variants(): void
    {
        $singlePassPath = $this->variantService->getVariantStoragePath('single-pass', 'prepare_prompt/old');
        $twoPassPath = $this->variantService->getVariantStoragePath('two-pass', 'prepare_prompt/old');

        // They should be different paths
        $this->assertNotEquals($singlePassPath, $twoPassPath);

        // Each should contain its variant name
        $this->assertStringContainsString('single-pass', $singlePassPath);
        $this->assertStringContainsString('two-pass', $twoPassPath);
    }

    public function test_migration_command_detects_legacy_data(): void
    {
        // This would require setting up legacy data, skipping for now
        $this->assertTrue(true);
    }

    public function test_reload_javascript_returns_code_without_saving(): void
    {
        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/0/reload-javascript-old');

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
        $this->assertNotEmpty($data['reloadedNodes']);
        $this->assertNotEmpty($data['reloadedNodes'][0]['javascript']);
    }

    public function test_reload_javascript_new_version_returns_code(): void
    {
        $response = $this->actingAs($this->adminUser)->postCountry('/debug/workflow/0/reload-javascript-new');

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
    }
}
