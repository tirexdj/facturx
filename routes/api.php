<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint (non-versioned)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// API V1 Routes
Route::prefix('v1')->group(function () {
    // Public routes (no authentication required)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/register', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'register']);
        Route::post('/forgot-password', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'forgotPassword'])->middleware('throttle:login');
        Route::post('/reset-password', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'resetPassword']);
    });

    // Protected routes (authentication required)
    Route::middleware(['auth:sanctum', 'api.company.access'])->group(function () {
        
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout']);
            Route::get('/me', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'me']);
            Route::put('/profile', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'updateProfile']);
            Route::put('/password', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'updatePassword']);
        });

        // Company routes (user's own company)
        Route::prefix('company')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Company\CompanyController::class, 'showOwnCompany']);
            Route::put('/', [\App\Http\Controllers\Api\V1\Company\CompanyController::class, 'updateOwnCompany']);
        });

        // Companies management routes (admin)
        Route::prefix('companies')->middleware('api.plan.limits:manage_companies')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Company\CompanyController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\Company\CompanyController::class, 'store']);
            Route::get('/{company}', [\App\Http\Controllers\Api\V1\Company\CompanyController::class, 'show']);
            Route::put('/{company}', [\App\Http\Controllers\Api\V1\Company\CompanyController::class, 'update']);
            Route::delete('/{company}', [\App\Http\Controllers\Api\V1\Company\CompanyController::class, 'destroy']);
        });

        // Plans routes (read-only)
        Route::prefix('plans')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Company\PlanController::class, 'index']);
            Route::get('/{plan}', [\App\Http\Controllers\Api\V1\Company\PlanController::class, 'show']);
        });

        // Clients routes
        Route::prefix('clients')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'store']);
            Route::get('/statistics', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'statistics']);
            Route::post('/import', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'import']);
            Route::post('/validate-siren', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'validateSiren']);
            Route::get('/{client}', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'show']);
            Route::put('/{client}', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'update']);
            Route::delete('/{client}', [\App\Http\Controllers\Api\V1\Customer\ClientController::class, 'destroy']);
        });

        // Products routes
        // Route::prefix('products')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'index']);
        //     Route::post('/', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'store']);
        //     Route::get('/{product}', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'show']);
        //     Route::put('/{product}', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'update']);
        //     Route::delete('/{product}', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'destroy']);
        // });

        // Services routes
        // Route::prefix('services')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Api\V1\Product\ServiceController::class, 'index']);
        //     Route::post('/', [\App\Http\Controllers\Api\V1\Product\ServiceController::class, 'store']);
        //     Route::get('/{service}', [\App\Http\Controllers\Api\V1\Product\ServiceController::class, 'show']);
        //     Route::put('/{service}', [\App\Http\Controllers\Api\V1\Product\ServiceController::class, 'update']);
        //     Route::delete('/{service}', [\App\Http\Controllers\Api\V1\Product\ServiceController::class, 'destroy']);
        // });

        // // Quotes routes
        // Route::prefix('quotes')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Api\V1\Quote\QuoteController::class, 'index']);
        //     Route::post('/', [\App\Http\Controllers\Api\V1\Quote\QuoteController::class, 'store']);
        //     Route::get('/{quote}', [\App\Http\Controllers\Api\V1\Quote\QuoteController::class, 'show']);
        //     Route::put('/{quote}', [\App\Http\Controllers\Api\V1\Quote\QuoteController::class, 'update']);
        //     Route::delete('/{quote}', [\App\Http\Controllers\Api\V1\Quote\QuoteController::class, 'destroy']);
        //     Route::post('/{quote}/send', [\App\Http\Controllers\Api\V1\Quote\QuoteController::class, 'send']);
        //     Route::post('/{quote}/convert-to-invoice', [\App\Http\Controllers\Api\V1\Quote\QuoteController::class, 'convertToInvoice']);
        // });

        // // Invoices routes
        // Route::prefix('invoices')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Api\V1\Invoice\InvoiceController::class, 'index']);
        //     Route::post('/', [\App\Http\Controllers\Api\V1\Invoice\InvoiceController::class, 'store']);
        //     Route::get('/{invoice}', [\App\Http\Controllers\Api\V1\Invoice\InvoiceController::class, 'show']);
        //     Route::put('/{invoice}', [\App\Http\Controllers\Api\V1\Invoice\InvoiceController::class, 'update']);
        //     Route::delete('/{invoice}', [\App\Http\Controllers\Api\V1\Invoice\InvoiceController::class, 'destroy']);
        //     Route::post('/{invoice}/send', [\App\Http\Controllers\Api\V1\Invoice\InvoiceController::class, 'send']);
        //     Route::get('/{invoice}/pdf', [\App\Http\Controllers\Api\V1\Invoice\InvoiceController::class, 'downloadPdf']);
        // });

        // // Payments routes
        // Route::prefix('payments')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'index']);
        //     Route::post('/', [\App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'store']);
        //     Route::get('/{payment}', [\App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'show']);
        //     Route::put('/{payment}', [\App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'update']);
        //     Route::delete('/{payment}', [\App\Http\Controllers\Api\V1\Payment\PaymentController::class, 'destroy']);
        // });
    });
});
