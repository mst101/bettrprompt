<?php

namespace App\Events;

use App\Models\PromptRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class PromptOptimizationCompleted implements ShouldBroadcast
{
    use Dispatchable;

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
        // Only send minimal data - don't include optimized_prompt as it can be very large (100KB+)
        // The frontend will reload the page or fetch full details via API when it receives this notification
        return [
            'prompt_run_id' => $this->promptRun->id,
            'workflow_stage' => $this->promptRun->workflow_stage,
            'completed_at' => $this->promptRun->completed_at?->toIso8601String(),
        ];
    }
}
