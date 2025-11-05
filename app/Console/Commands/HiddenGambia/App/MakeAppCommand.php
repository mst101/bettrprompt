<?php

namespace App\Console\Commands\HiddenGambia\App;

use Illuminate\Console\Command;

class MakeAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hg:make:app
                            {models?* : The model classes to generate resources and TypeScript for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate both resources and TypeScript definitions for models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = $this->argument('models');

        $this->info('Running hg:make:app - generating resources and TypeScript definitions...');

        // Build arguments for sub-commands
        $resourceArgs = [];
        $typescriptArgs = [];

        if (! empty($models)) {
            $resourceArgs['models'] = $models;
            $typescriptArgs['models'] = $models;
        }

        // Run hg:make:resources
        $this->info('Step 1/2: Generating resources...');
        $resourceResult = $this->call('hg:make:resources', $resourceArgs);

        if ($resourceResult !== 0) {
            $this->error('Resource generation failed. Stopping execution.');

            return $resourceResult;
        }

        // Run hg:make:typescript
        $this->info('Step 2/2: Generating TypeScript definitions...');
        $typescriptResult = $this->call('hg:make:typescript', $typescriptArgs);

        if ($typescriptResult !== 0) {
            $this->error('TypeScript generation failed.');

            return $typescriptResult;
        }

        $this->info('✅ Successfully generated resources and TypeScript definitions!');

        return 0;
    }
}
