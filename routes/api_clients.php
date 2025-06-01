<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ClientController;

/*
|--------------------------------------------------------------------------
| Client API Routes
|--------------------------------------------------------------------------
|
| Routes pour la gestion des clients et prospects
|
*/

Route::prefix('clients')->group(function () {
    // Routes principales CRUD
    Route::get('/', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/statistics', [ClientController::class, 'statistics'])->name('clients.statistics');
    Route::get('/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    
    // Actions spéciales
    Route::post('/{client}/convert-to-client', [ClientController::class, 'convertToClient'])->name('clients.convert');
    
    // Import/Export
    Route::post('/import', [ClientController::class, 'import'])->name('clients.import');
    Route::get('/export', [ClientController::class, 'export'])->name('clients.export');
    
    // Validation
    Route::post('/validate-siren', [ClientController::class, 'validateSiren'])->name('clients.validate-siren');
});
