<?php

return [
    1 => [
        'default' => 'single-pass',
        'variants' => [
            'single-pass' => [
                'name' => 'Single Pass',
                'workflow_file' => 'workflow_1.json',
                'prepare_prompt_nodes' => ['Prepare Prompt'],
                'description' => 'Original single-pass implementation',
            ],
            'two-pass' => [
                'name' => 'Two Pass',
                'workflow_file' => 'workflow_1_two_pass.json',
                'prepare_prompt_nodes' => ['Prepare Prompt 1', 'Prepare Prompt 2'],
                'description' => 'Two-pass with separate analysis stages',
            ],
        ],
    ],

    // Workflows 0 and 2 (no variants yet)
    0 => [
        'default' => 'default',
        'variants' => [
            'default' => [
                'name' => 'Default',
                'workflow_file' => 'workflow_0.json',
                'prepare_prompt_nodes' => ['Prepare Prompt'],
            ],
        ],
    ],
    2 => [
        'default' => 'default',
        'variants' => [
            'default' => [
                'name' => 'Default',
                'workflow_file' => 'workflow_2.json',
                'prepare_prompt_nodes' => ['Prepare Prompt'],
            ],
        ],
    ],
];
