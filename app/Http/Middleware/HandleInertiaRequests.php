<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Models\PromptRun;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Check if visitor has completed prompts (for banner display)
        $visitorHasCompletedPrompts = false;
        if (! $request->user()) {
            $visitorId = $request->cookie('visitor_id');
            if ($visitorId) {
                $visitorHasCompletedPrompts = PromptRun::where('visitor_id', $visitorId)
                    ->where('status', 'completed')
                    ->exists();
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? UserResource::make($request->user())->resolve() : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'error' => fn () => $request->session()->get('error'),
                'previous_answer' => fn () => $request->session()->get('previous_answer'),
            ],
            'visitorHasCompletedPrompts' => $visitorHasCompletedPrompts,
        ];
    }
}
