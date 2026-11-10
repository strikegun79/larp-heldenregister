<?php

use App\Http\Controllers\Api\V1\AdventureController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\HeroController;
use App\Http\Controllers\Matrix\CorporalPolicyController;
use App\Http\Middleware\VerifyMatrixCorporalToken;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// -----------------------------------------------------------------------
// ARCH-007: API v1 – Serialisierungs-Grundlage
// Heroes: öffentlich (nur public_visible=true, kein Realname – PUB-02)
// Adventures + Buchungen: hinter auth:sanctum
// -----------------------------------------------------------------------
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Öffentliche Helden-Endpunkte (kein Token nötig)
    Route::get('heroes', [HeroController::class, 'index'])
        ->name('heroes.index');
    Route::get('heroes/{code}', [HeroController::class, 'show'])
        ->name('heroes.show');

    // Authentifizierte Endpunkte (Sanctum-Token erforderlich)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('adventures', [AdventureController::class, 'index'])
            ->name('adventures.index');
        Route::get('adventures/{adventure}', [AdventureController::class, 'show'])
            ->name('adventures.show');

        // Eigene Buchungen des authentifizierten Nutzers
        Route::get('me/bookings', [BookingController::class, 'index'])
            ->name('me.bookings');
    });
});

// matrix-corporal Policy-Endpoint (per Bearer-Token geschützt, zustandslos).
Route::middleware(VerifyMatrixCorporalToken::class)
    ->get('/matrix/corporal/policy', CorporalPolicyController::class)
    ->name('matrix.corporal.policy');
