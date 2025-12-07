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
        $channelName = 'prompt-run.'.$this->promptRun->id;
        Log::info('PreAnalysisCompleted::broadcastOn() called', [
            'prompt_run_id' => $this->promptRun->id,
            'channel' => $channelName,
        ]);
        return new Channel($channelName);
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        Log::info('PreAnalysisCompleted::broadcastAs() called', [
            'prompt_run_id' => $this->promptRun->id,
            'channel' => 'prompt-run.'.$this->promptRun->id,
        ]);
        return 'PreAnalysisCompleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $data = [
            'prompt_run_id' => $this->promptRun->id,
            'workflow_stage' => $this->promptRun->workflow_stage,
            'questions_count' => count($this->promptRun->pre_analysis_questions ?? []),
        ];
        Log::info('PreAnalysisCompleted::broadcastWith() called', [
            'data' => $data,
        ]);
        return $data;
    }
}
