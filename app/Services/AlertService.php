<?php

namespace App\Services;

use App\Models\AlertHistory;
use App\Models\AlertRule;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Trigger an alert for a workflow failure
     *
     * @param  int  $workflowStage  The workflow stage (0, 1, or 2)
     * @param  string  $status  The status (failed or timeout)
     */
    public function triggerWorkflowAlert(
        int $workflowStage,
        string $status,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): ?AlertHistory {
        $alertRule = AlertRule::where('slug', 'workflow_failure')
            ->where('is_active', true)
            ->first();

        if (! $alertRule) {
            return null;
        }

        // Check if conditions are met
        if (! $this->conditionsMet($alertRule, $workflowStage, $status)) {
            return null;
        }

        // Apply debouncing
        $alertHistory = $this->checkDebounce($alertRule, $errorCode);

        if ($alertHistory === null) {
            // Create new alert history
            $alertHistory = AlertHistory::create([
                'alert_rule_id' => $alertRule->id,
                'triggered_count' => 1,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'last_triggered_at' => now(),
            ]);
        } else {
            // Update existing alert (increment triggered_count, update timestamp)
            $alertHistory->update([
                'triggered_count' => $alertHistory->triggered_count + 1,
                'last_triggered_at' => now(),
            ]);
        }

        // Create notifications
        $this->createNotifications($alertRule, $alertHistory);

        return $alertHistory;
    }

    /**
     * Check if alert conditions are met
     */
    private function conditionsMet(AlertRule $rule, int $workflowStage, string $status): bool
    {
        $conditions = $rule->conditions;

        if (! $conditions) {
            return true;
        }

        // Check workflow_stages
        if (isset($conditions['workflow_stages'])) {
            if (! in_array($workflowStage, $conditions['workflow_stages'], true)) {
                return false;
            }
        }

        // Check statuses
        if (isset($conditions['statuses'])) {
            if (! in_array($status, $conditions['statuses'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check debounce - return existing alert if within debounce window, null otherwise
     * Returns the alert history record if one exists within the debounce window
     */
    private function checkDebounce(AlertRule $rule, ?string $errorCode): ?AlertHistory
    {
        $debounceMinutes = $rule->debounce_minutes;
        $windowStart = now()->subMinutes($debounceMinutes);

        // Check if there's a recent alert with the same error_code
        $recentAlert = AlertHistory::where('alert_rule_id', $rule->id)
            ->where('error_code', $errorCode)
            ->where('last_triggered_at', '>=', $windowStart)
            ->first();

        return $recentAlert;
    }

    /**
     * Create notifications for an alert
     */
    private function createNotifications(AlertRule $rule, AlertHistory $alertHistory): void
    {
        try {
            // Email notification
            if ($rule->email_enabled && $rule->email_recipients) {
                $alertHistory->notifications()->create([
                    'type' => 'email',
                    'status' => 'pending',
                ]);
            }

            // In-app notification
            if ($rule->in_app_enabled) {
                // Get all admin users to notify
                $adminUsers = \App\Models\User::where('is_admin', true)->get();

                foreach ($adminUsers as $user) {
                    $alertHistory->notifications()->create([
                        'user_id' => $user->id,
                        'type' => 'in_app',
                        'status' => 'pending',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to create alert notifications', [
                'alert_history_id' => $alertHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
