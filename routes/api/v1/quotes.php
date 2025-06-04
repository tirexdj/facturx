<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Quote\QuoteController;

/*
|--------------------------------------------------------------------------
| Quote API Routes V1
|--------------------------------------------------------------------------
|
| Routes pour la gestion des devis dans l'API V1
|
*/

Route::group([
    'prefix' => 'v1',
    'middleware' => ['auth:sanctum', 'company.access'],
], function () {
    
    // Routes des devis
    Route::prefix('quotes')->name('quotes.')->group(function () {
        
        // CRUD de base
        Route::get('/', [QuoteController::class, 'index'])->name('index');
        Route::post('/', [QuoteController::class, 'store'])->name('store');
        Route::get('/{quote}', [QuoteController::class, 'show'])->name('show');
        Route::put('/{quote}', [QuoteController::class, 'update'])->name('update');
        Route::delete('/{quote}', [QuoteController::class, 'destroy'])->name('destroy');
        
        // Actions spécifiques
        Route::post('/{quote}/send', [QuoteController::class, 'send'])->name('send');
        Route::post('/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('duplicate');
        Route::post('/{quote}/convert', [QuoteController::class, 'convertToInvoice'])->name('convert');
        
        // Génération PDF
        Route::get('/{quote}/pdf', [QuoteController::class, 'downloadPdf'])->name('pdf');
        
    });
    
});
