<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersioning
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $version = 'v1'): Response
    {
        // Set the API version in the request
        $request->attributes->set('api_version', $version);

        // Ensure JSON responses
        $request->headers->set('Accept', 'application/json');

        // Add version to response headers
        $response = $next($request);
        
        // Add API-Version header to all responses for API routes
        if ($request->is('api/*')) {
            $response->headers->set('API-Version', $version);
        }

        return $response;
    }
}
