<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AdventureController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EpTransactionController;
use App\Http\Controllers\HeroClassController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\HeroSkillController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkilltreeController;
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

    Route::resource('players', PlayerController::class);
    // Aktiven Helden eines Spielers setzen (HERO-07).
    Route::patch('players/{player}/active-hero', [PlayerController::class, 'setActiveHero'])->name('players.active-hero');
    Route::resource('heroes', HeroController::class);
    // Verschollen-Status umschalten (HERO-08).
    Route::patch('heroes/{hero}/missing', [HeroController::class, 'toggleMissing'])->name('heroes.missing');
    // EP-Buchung für einen Helden (HERO-12).
    Route::post('heroes/{hero}/ep', [EpTransactionController::class, 'store'])->name('heroes.ep.store');
    // Fertigkeit erlernen (HERO-14) / aberkennen (HERO-16).
    Route::post('heroes/{hero}/skills', [HeroSkillController::class, 'store'])->name('heroes.skills.store');
    Route::delete('heroes/{hero}/skills/{skill}', [HeroSkillController::class, 'destroy'])->name('heroes.skills.destroy');
    // Klasse hinzufügen/entfernen mit EP-Verbuchung (HERO-06).
    Route::post('heroes/{hero}/classes', [HeroClassController::class, 'store'])->name('heroes.classes.store');
    Route::delete('heroes/{hero}/classes/{heroClass}', [HeroClassController::class, 'destroy'])->name('heroes.classes.destroy');

    // Fertigkeitsbaum-Positions-Editor je Klasse (HERO-17).
    Route::get('skilltree/{heroClass}/edit', [SkilltreeController::class, 'edit'])->name('skilltree.edit');
    Route::patch('skilltree/{heroClass}', [SkilltreeController::class, 'update'])->name('skilltree.update');
    Route::resource('adventures', AdventureController::class);

    // Anmeldungen zu einem Abenteuer.
    Route::post('adventures/{adventure}/bookings', [BookingController::class, 'store'])
        ->name('adventures.bookings.store');
    // Anmeldedetails nachträglich bearbeiten (BOOK-04).
    Route::get('adventures/{adventure}/bookings/{booking}/edit', [BookingController::class, 'edit'])
        ->name('adventures.bookings.edit');
    Route::put('adventures/{adventure}/bookings/{booking}', [BookingController::class, 'update'])
        ->name('adventures.bookings.update');
    // Anmeldung bestätigen/freigeben – Toggle approved_at (BOOK-05).
    Route::patch('adventures/{adventure}/bookings/{booking}/approval', [BookingController::class, 'approve'])
        ->name('adventures.bookings.approval');
    // Teilnahmebeitrag-Status – Toggle paid (BOOK-06).
    Route::patch('adventures/{adventure}/bookings/{booking}/payment', [BookingController::class, 'togglePaid'])
        ->name('adventures.bookings.payment');
    Route::delete('adventures/{adventure}/bookings/{booking}', [BookingController::class, 'destroy'])
        ->name('adventures.bookings.destroy');

    // Teilnahme/Check-in erfassen (BOOK-08).
    Route::put('adventures/{adventure}/attendance', [AttendanceController::class, 'update'])
        ->name('adventures.attendance');
    // EP für anwesende Teilnehmer verbuchen (BOOK-09).
    Route::post('adventures/{adventure}/award-ep', [AttendanceController::class, 'awardEp'])
        ->name('adventures.award-ep');

    // Verwaltung (Portal-Administration, Berechtigung portal.manage).
    Route::prefix('admin')->name('admin.')->middleware('can:portal.manage')->group(function () {
        Route::get('/', [Admin\AdminController::class, 'index'])->name('index');
        // Nutzerverwaltung erfordert zusätzlich users.manage.
        Route::middleware('can:users.manage')->group(function () {
            Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
            Route::get('users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        });
        Route::get('players', [Admin\PlayerController::class, 'index'])->name('players.index');

        // Helden-Klassen-Lookup pflegen (HERO-05).
        Route::get('hero-classes', [Admin\HeroClassController::class, 'index'])->name('hero-classes.index');
        Route::get('hero-classes/create', [Admin\HeroClassController::class, 'create'])->name('hero-classes.create');
        Route::post('hero-classes', [Admin\HeroClassController::class, 'store'])->name('hero-classes.store');
        Route::get('hero-classes/{heroClass}/edit', [Admin\HeroClassController::class, 'edit'])->name('hero-classes.edit');
        Route::put('hero-classes/{heroClass}', [Admin\HeroClassController::class, 'update'])->name('hero-classes.update');

        // Matrix-Konto-Provisionierung pro Spieler (corporal User-DB).
        Route::get('players/{player}/matrix', [Admin\MatrixAccountController::class, 'edit'])->name('players.matrix.edit');
        Route::put('players/{player}/matrix', [Admin\MatrixAccountController::class, 'update'])->name('players.matrix.update');
        Route::delete('players/{player}/matrix', [Admin\MatrixAccountController::class, 'destroy'])->name('players.matrix.destroy');
    });
});

require __DIR__.'/auth.php';
