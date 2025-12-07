<?php

namespace App\Events;

use App\Models\PromptRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PreAnalysisCompleted implements ShouldBroadcast
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
     * Get the broadcast event name.
     *
     * For Reverb, the event name needs to match what Laravel uses by default
     * when the class name gets converted. This is typically the short class name
     * followed by the namespace-based identifier.
     */
    public function broadcastAs(): string
    {
        return 'PreAnalysisCompleted';
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
            'questions_count' => count($this->promptRun->pre_analysis_questions ?? []),
        ];
    }
}
