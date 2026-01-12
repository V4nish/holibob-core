<?php

use App\Http\Controllers\Api\SearchController;
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

// Search Routes
Route::prefix('search')->group(function () {
    Route::get('/properties', [SearchController::class, 'properties'])->name('api.search.properties');
    Route::get('/suggest', [SearchController::class, 'suggest'])->name('api.search.suggest');
    Route::get('/popular', [SearchController::class, 'popular'])->name('api.search.popular');
    Route::get('/statistics', [SearchController::class, 'statistics'])->name('api.search.statistics');
});
