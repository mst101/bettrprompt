<?php

namespace App\Events;

use App\Models\PromptRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromptOptimizationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PromptRun $promptRun
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('prompt-run.' . $this->promptRun->id);
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
            'status' => $this->promptRun->status,
            'optimized_prompt' => $this->promptRun->optimized_prompt,
            'completed_at' => $this->promptRun->completed_at?->toIso8601String(),
        ];
    }
}
