<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanLimits
{
    /**
     * Handle an incoming request.
     *
     * Checks if the user's company plan allows the requested action.
     */
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        $user = $request->user();
        
        if (!$user || !$user->company) {
            return $next($request);
        }

        $company = $user->company;
        $plan = $company->plan;

        if (!$plan) {
            return response()->json([
                'message' => 'No active plan found for this company.',
                'error' => 'no_active_plan'
            ], 403);
        }

        // Add plan context to request
        $request->attributes->set('user_plan', $plan);
        $request->attributes->set('plan_features', $plan->features);

        // If specific feature check is requested
        if ($feature) {
            $hasFeature = $plan->features()
                ->where('code', $feature)
                ->where('is_enabled', true)
                ->exists();

            if (!$hasFeature) {
                return response()->json([
                    'message' => "This feature ({$feature}) is not available in your current plan.",
                    'error' => 'feature_not_available',
                    'current_plan' => $plan->name,
                    'feature' => $feature
                ], 403);
            }
        }

        return $next($request);
    }
}
