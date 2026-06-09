<?php

use App\Http\Controllers\AdventureController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('heroes', HeroController::class);
    Route::resource('adventures', AdventureController::class);

    // Anmeldungen zu einem Abenteuer.
    Route::post('adventures/{adventure}/bookings', [BookingController::class, 'store'])
        ->name('adventures.bookings.store');
    Route::delete('adventures/{adventure}/bookings/{booking}', [BookingController::class, 'destroy'])
        ->name('adventures.bookings.destroy');
});

require __DIR__.'/auth.php';
