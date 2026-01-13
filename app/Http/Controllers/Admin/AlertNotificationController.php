<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertHistory;
use App\Models\AlertNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertNotificationController extends Controller
{
    /**
     * Get pending in-app notifications for current admin user
     */
    public function getPending(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notifications = AlertNotification::where('user_id', $user->id)
            ->where('type', 'in_app')
            ->where('status', 'pending')
            ->with(['alertHistory' => function ($query) {
                $query->with('rule');
            }])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'alertId' => $notification->alertHistory->id,
                    'ruleSlug' => $notification->alertHistory->rule->slug,
                    'ruleName' => $notification->alertHistory->rule->name,
                    'errorCode' => $notification->alertHistory->error_code,
                    'errorMessage' => $notification->alertHistory->error_message,
                    'triggeredCount' => $notification->alertHistory->triggered_count,
                    'createdAt' => $notification->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'count' => count($notifications),
        ]);
    }

    /**
     * Mark a notification as read (acknowledge it)
     */
    public function acknowledge(Request $request, int $notificationId): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification = AlertNotification::find($notificationId);

        if (! $notification || $notification->user_id !== $user->id) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->update(['status' => 'read']);

        // Acknowledge the alert history if all admin notifications for it are read
        $alertHistory = $notification->alertHistory;
        $pendingCount = $alertHistory->notifications()
            ->where('status', 'pending')
            ->count();

        if ($pendingCount === 0 && ! $alertHistory->acknowledged_at) {
            $alertHistory->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification acknowledged',
        ]);
    }

    /**
     * Get all alert history for the dashboard
     */
    public function getAlerts(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $alerts = AlertHistory::with(['rule', 'acknowledgedByUser'])
            ->orderBy('last_triggered_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'ruleName' => $alert->rule->name,
                    'errorCode' => $alert->error_code,
                    'errorMessage' => $alert->error_message,
                    'triggeredCount' => $alert->triggered_count,
                    'lastTriggeredAt' => $alert->last_triggered_at->toIso8601String(),
                    'acknowledgedAt' => $alert->acknowledged_at?->toIso8601String(),
                    'acknowledgedByName' => $alert->acknowledgedByUser?->name,
                ];
            });

        return response()->json([
            'alerts' => $alerts,
        ]);
    }

    /**
     * Acknowledge an alert history record
     */
    public function acknowledgeAlert(Request $request, int $alertId): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $alert = AlertHistory::find($alertId);

        if (! $alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }

        $alert->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        // Mark all associated notifications as read
        $alert->notifications()->update(['status' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged',
        ]);
    }
}
