<?php

namespace App\Filters;

/**
 * Reusable filter closures for user collections.
 * Static properties storing closures allow the JIT compiler to optimise
 * filters better than runtime-created closures.
 */
class UserFilters
{
    public static \Closure $active;

    public static \Closure $paid;

    public static \Closure $premium;

    public static \Closure $free;

    public static \Closure $hasCompletedProfile;

    public static function init(): void
    {
        self::$active = fn ($user) => $user['active'] === true;

        self::$paid = fn ($user) => in_array($user['subscription_tier'], ['starter', 'pro', 'premium']);

        self::$premium = fn ($user) => $user['subscription_tier'] === 'premium';

        self::$free = fn ($user) => $user['subscription_tier'] === 'free';

        self::$hasCompletedProfile = fn ($user) => ($user['profile_completion_percentage'] ?? 0) >= 80;
    }
}

// Initialise filters on class load
UserFilters::init();
