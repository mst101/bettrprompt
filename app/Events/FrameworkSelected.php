<?php

namespace App\Events;

use App\Models\PromptRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FrameworkSelected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'prompt_run_id' => $this->promptRun->id,
            'selected_framework' => $this->promptRun->selected_framework,
            'framework_reasoning' => $this->promptRun->framework_reasoning,
            'questions_count' => count($this->promptRun->framework_questions ?? []),
        ];
    }
}
