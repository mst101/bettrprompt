<?php

namespace App\Services;

use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Support\Facades\Log;

class VisitorMigrationService
{
    /**
     * Migrate a visitor's data to a user account.
     *
     * This is called when:
     * - A visitor registers a new account
     * - A visitor logs into an existing account
     *
     * @param  User  $user  The user to migrate data to
     * @param  string  $visitorId  The visitor ID from the cookie
     * @return int The number of prompt runs claimed
     */
    public function migrateVisitorToUser(User $user, string $visitorId): int
    {
        $visitor = Visitor::find($visitorId);

        if (! $visitor) {
            Log::warning('Visitor not found for migration', [
                'user_id' => $user->id,
                'visitor_id' => $visitorId,
            ]);

            return 0;
        }

        // Only migrate if visitor hasn't been claimed by another user
        if ($visitor->user_id && $visitor->user_id !== $user->id) {
            Log::warning('Visitor already claimed by another user', [
                'user_id' => $user->id,
                'visitor_id' => $visitorId,
                'claimed_by_user_id' => $visitor->user_id,
            ]);

            return 0;
        }

        // Copy visitor data to user (only if user doesn't already have it)
        $updates = [];

        if ($visitor->personality_type && ! $user->personality_type) {
            $updates['personality_type'] = $visitor->personality_type;
            $updates['trait_percentages'] = $visitor->trait_percentages;
        }

        if ($visitor->referred_by_user_id && ! $user->referred_by_user_id) {
            $updates['referred_by_user_id'] = $visitor->referred_by_user_id;
        }

        // Copy location data from visitor if user doesn't have it
        if ($visitor->hasLocationData() && ! $user->hasLocationData()) {
            $updates['country_code'] = $visitor->country_code;
            $updates['country_name'] = $visitor->country_name;
            $updates['region'] = $visitor->region;
            $updates['city'] = $visitor->city;
            $updates['timezone'] = $visitor->timezone;
            $updates['currency_code'] = $visitor->currency_code;
            $updates['latitude'] = $visitor->latitude;
            $updates['longitude'] = $visitor->longitude;
            $updates['language_code'] = $visitor->language_code;
            $updates['location_detected_at'] = $visitor->location_detected_at;
            $updates['location_manually_set'] = false; // Auto-detected
            $updates['language_manually_set'] = false; // Auto-detected
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        // Update visitor record to link it to the user
        if (! $visitor->user_id) {
            $visitor->update([
                'user_id' => $user->id,
                'converted_at' => now(),
            ]);
        }

        // Claim all guest prompt runs from this visitor
        $claimedCount = PromptRun::where('visitor_id', $visitorId)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        Log::info('Visitor migrated to user', [
            'user_id' => $user->id,
            'visitor_id' => $visitorId,
            'claimed_prompt_runs' => $claimedCount,
            'copied_personality' => isset($updates['personality_type']),
            'copied_location' => $visitor->hasLocationData() && ! $user->hasLocationData(),
            'copied_referrer' => isset($updates['referred_by_user_id']),
        ]);

        return $claimedCount;
    }
}
