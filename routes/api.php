<?php

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

// matrix-corporal Policy-Endpoint (per Bearer-Token geschützt, zustandslos).
Route::middleware(VerifyMatrixCorporalToken::class)
    ->get('/matrix/corporal/policy', CorporalPolicyController::class)
    ->name('matrix.corporal.policy');
