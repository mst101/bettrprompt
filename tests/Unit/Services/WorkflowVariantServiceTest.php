<?php

use App\Services\WorkflowVariantService;

beforeEach(function () {
    $this->variantService = app(WorkflowVariantService::class);
});

test('get variants returns configured variants', function () {
    $variants = $this->variantService->getVariants(1);

    expect($variants)->toBeArray()
        ->and($variants)->not->toBeEmpty()
        ->and($variants[0])->toHaveKey('key')
        ->and($variants[0])->toHaveKey('name');
});

test('get variants includes single pass for workflow 1', function () {
    $variants = $this->variantService->getVariants(1);
    $variantKeys = array_column($variants, 'key');

    expect($variantKeys)->toContain('single-pass');
});

test('get variants includes two pass for workflow 1', function () {
    $variants = $this->variantService->getVariants(1);
    $variantKeys = array_column($variants, 'key');

    expect($variantKeys)->toContain('two-pass');
});

test('get default variant returns single pass for workflow 1', function () {
    $default = $this->variantService->getDefaultVariant(1);

    expect($default)->toBe('single-pass');
});

test('get variant config returns correct config', function () {
    $config = $this->variantService->getVariantConfig(1, 'single-pass');

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('name')
        ->and($config)->toHaveKey('workflow_file')
        ->and($config)->toHaveKey('prepare_prompt_nodes');
});

test('get variant config single pass has one node', function () {
    $config = $this->variantService->getVariantConfig(1, 'single-pass');
    $nodes = $config['prepare_prompt_nodes'];

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0])->toBe('Prepare Prompt');
});

test('get variant config two pass has two nodes', function () {
    $config = $this->variantService->getVariantConfig(1, 'two-pass');
    $nodes = $config['prepare_prompt_nodes'];

    expect($nodes)->toHaveCount(2)
        ->and($nodes[0])->toBe('Prepare Prompt 1')
        ->and($nodes[1])->toBe('Prepare Prompt 2');
});

test('extract prepare prompt node names returns node names', function () {
    $nodeNames = $this->variantService->extractPreparePromptNodeNames(1, 'single-pass');

    expect($nodeNames)->toBeArray()
        ->and($nodeNames)->toHaveCount(1)
        ->and($nodeNames[0])->toBe('Prepare Prompt');
});

test('extract prepare prompt node names two pass returns two nodes', function () {
    $nodeNames = $this->variantService->extractPreparePromptNodeNames(1, 'two-pass');

    expect($nodeNames)->toBeArray()
        ->and($nodeNames)->toHaveCount(2)
        ->and($nodeNames)->toContain('Prepare Prompt 1')
        ->and($nodeNames)->toContain('Prepare Prompt 2');
});

test('get workflow file path single pass', function () {
    $path = $this->variantService->getWorkflowFilePath(1, 'single-pass');

    expect($path)->toContain('workflow_1.json')
        ->and($path)->not->toEndWith('_two_pass.json');
});

test('get workflow file path two pass', function () {
    $path = $this->variantService->getWorkflowFilePath(1, 'two-pass');

    expect($path)->toContain('workflow_1_two_pass.json');
});

test('get variant storage path includes variant name', function () {
    $path = $this->variantService->getVariantStoragePath('two-pass', 'prepare_prompt/old');

    expect($path)->toContain('two-pass')
        ->and($path)->toContain('prepare_prompt')
        ->and($path)->toContain('old');
});

test('get variant storage path returns directory path', function () {
    $path = $this->variantService->getVariantStoragePath('single-pass', 'prompt/new');

    expect($path)->toEndWith('/')
        ->and($path)->toContain('n8n_debug');
});
