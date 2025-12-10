<?php

namespace App\Events;

use App\Models\PromptRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromptOptimizationCompleted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PromptRun $promptRun
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('prompt-run.'.$this->promptRun->id);
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'PromptOptimizationCompleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'prompt_run_id' => $this->promptRun->id,
            'workflow_stage' => $this->promptRun->workflow_stage,
            'optimized_prompt' => $this->promptRun->optimized_prompt,
            'completed_at' => $this->promptRun->completed_at?->toIso8601String(),
        ];
    }
}
