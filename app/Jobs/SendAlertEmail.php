<?php

namespace App\Jobs;

use App\Mail\WorkflowAlertMail;
use App\Models\AlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAlertEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private AlertNotification $notification,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $alertHistory = $this->notification->alertHistory;
            $rule = $alertHistory->rule;

            // Get recipients
            $recipients = explode(',', $rule->email_recipients);
            $recipients = array_map('trim', $recipients);

            // Send email
            Mail::to($recipients)->queue(new WorkflowAlertMail(
                errorCode: $alertHistory->error_code,
                errorMessage: $alertHistory->error_message,
                triggeredCount: $alertHistory->triggered_count,
            ));

            // Mark notification as sent
            $this->notification->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info('Alert email sent successfully', [
                'alert_notification_id' => $this->notification->id,
                'alert_history_id' => $alertHistory->id,
            ]);
        } catch (\Exception $e) {
            $this->notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Failed to send alert email', [
                'alert_notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
