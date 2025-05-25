<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user can only access data from their company.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-authenticated routes
        if (!$request->user()) {
            return $next($request);
        }

        $user = $request->user();
        
        // Ensure user has a company
        if (!$user->company_id) {
            return response()->json([
                'message' => 'User is not associated with any company.',
                'error' => 'no_company_access'
            ], 403);
        }

        // Add company context to request
        $request->attributes->set('user_company_id', $user->company_id);
        $request->attributes->set('user_company', $user->company);

        return $next($request);
    }
}
