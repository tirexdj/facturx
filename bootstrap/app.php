<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // API Middleware aliases
        $middleware->alias([
            'api.versioning' => \App\Http\Middleware\ApiVersioning::class,
            'api.company.access' => \App\Http\Middleware\CompanyAccess::class,
            'api.plan.limits' => \App\Http\Middleware\PlanLimits::class,
        ]);

        // API middleware group
        $middleware->api([
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'api.versioning:v1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Helper function to extract API version from request
        $getApiVersion = function (Request $request) {
            if ($request->is('api/v*')) {
                $segments = explode('/', $request->path());
                foreach ($segments as $segment) {
                    if (str_starts_with($segment, 'v')) {
                        return $segment;
                    }
                }
            }
            return 'v1'; // default version
        };

        // API Exception handling
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($getApiVersion) {
            if ($request->is('api/*')) {
                $version = $getApiVersion($request);
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'authentication_required'
                ], 401)->header('API-Version', $version);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($getApiVersion) {
            if ($request->is('api/*')) {
                $version = $getApiVersion($request);
                return response()->json([
                    'message' => 'Access denied.',
                    'error' => 'access_denied'
                ], 403)->header('API-Version', $version);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($getApiVersion) {
            if ($request->is('api/*')) {
                $version = $getApiVersion($request);
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => 'not_found'
                ], 404)->header('API-Version', $version);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($getApiVersion) {
            if ($request->is('api/*')) {
                $version = $getApiVersion($request);
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'error' => 'validation_failed',
                    'errors' => $e->errors()
                ], 422)->header('API-Version', $version);
            }
        });

        $exceptions->render(function (\Exception $e, Request $request) use ($getApiVersion) {
            if ($request->is('api/*') && !config('app.debug')) {
                $version = $getApiVersion($request);
                return response()->json([
                    'message' => 'An error occurred while processing your request.',
                    'error' => 'internal_server_error'
                ], 500)->header('API-Version', $version);
            }
        });
    })->create();
