<?php

namespace Tests\Unit\Services;

use App\Services\WorkflowVariantService;
use Tests\TestCase;

class WorkflowVariantServiceTest extends TestCase
{
    private WorkflowVariantService $variantService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->variantService = app(WorkflowVariantService::class);
    }

    public function test_get_variants_returns_configured_variants(): void
    {
        $variants = $this->variantService->getVariants(1);

        $this->assertIsArray($variants);
        $this->assertGreaterThan(0, count($variants));
        $this->assertArrayHasKey('key', $variants[0]);
        $this->assertArrayHasKey('name', $variants[0]);
    }

    public function test_get_variants_includes_single_pass_for_workflow_1(): void
    {
        $variants = $this->variantService->getVariants(1);
        $variantKeys = array_column($variants, 'key');

        $this->assertContains('single-pass', $variantKeys);
    }

    public function test_get_variants_includes_two_pass_for_workflow_1(): void
    {
        $variants = $this->variantService->getVariants(1);
        $variantKeys = array_column($variants, 'key');

        $this->assertContains('two-pass', $variantKeys);
    }

    public function test_get_default_variant_returns_single_pass_for_workflow_1(): void
    {
        $default = $this->variantService->getDefaultVariant(1);

        $this->assertEquals('single-pass', $default);
    }

    public function test_get_variant_config_returns_correct_config(): void
    {
        $config = $this->variantService->getVariantConfig(1, 'single-pass');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('workflow_file', $config);
        $this->assertArrayHasKey('prepare_prompt_nodes', $config);
    }

    public function test_get_variant_config_single_pass_has_one_node(): void
    {
        $config = $this->variantService->getVariantConfig(1, 'single-pass');
        $nodes = $config['prepare_prompt_nodes'];

        $this->assertCount(1, $nodes);
        $this->assertEquals('Prepare Prompt', $nodes[0]);
    }

    public function test_get_variant_config_two_pass_has_two_nodes(): void
    {
        $config = $this->variantService->getVariantConfig(1, 'two-pass');
        $nodes = $config['prepare_prompt_nodes'];

        $this->assertCount(2, $nodes);
        $this->assertEquals('Prepare Prompt 1', $nodes[0]);
        $this->assertEquals('Prepare Prompt 2', $nodes[1]);
    }

    public function test_extract_prepare_prompt_node_names_returns_node_names(): void
    {
        $nodeNames = $this->variantService->extractPreparePromptNodeNames(1, 'single-pass');

        $this->assertIsArray($nodeNames);
        $this->assertCount(1, $nodeNames);
        $this->assertEquals('Prepare Prompt', $nodeNames[0]);
    }

    public function test_extract_prepare_prompt_node_names_two_pass_returns_two_nodes(): void
    {
        $nodeNames = $this->variantService->extractPreparePromptNodeNames(1, 'two-pass');

        $this->assertIsArray($nodeNames);
        $this->assertCount(2, $nodeNames);
        $this->assertContains('Prepare Prompt 1', $nodeNames);
        $this->assertContains('Prepare Prompt 2', $nodeNames);
    }

    public function test_get_workflow_file_path_single_pass(): void
    {
        $path = $this->variantService->getWorkflowFilePath(1, 'single-pass');

        $this->assertStringContainsString('workflow_1.json', $path);
        $this->assertStringEndsNotWith('_two_pass.json', $path);
    }

    public function test_get_workflow_file_path_two_pass(): void
    {
        $path = $this->variantService->getWorkflowFilePath(1, 'two-pass');

        $this->assertStringContainsString('workflow_1_two_pass.json', $path);
    }

    public function test_get_variant_storage_path_includes_variant_name(): void
    {
        $path = $this->variantService->getVariantStoragePath('two-pass', 'prepare_prompt/old');

        $this->assertStringContainsString('two-pass', $path);
        $this->assertStringContainsString('prepare_prompt', $path);
        $this->assertStringContainsString('old', $path);
    }

    public function test_get_variant_storage_path_returns_directory_path(): void
    {
        $path = $this->variantService->getVariantStoragePath('single-pass', 'prompt/new');

        // Path should be a directory path ending with /
        $this->assertStringEndsWith('/', $path);
        $this->assertStringContainsString('n8n_debug', $path);
    }
}
