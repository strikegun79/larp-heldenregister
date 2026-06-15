<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AdventureController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EpTransactionController;
use App\Http\Controllers\HeroClassController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\HeroSkillController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatureController;
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

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // In-App-Benachrichtigungen (NOTI-07).
    Route::get('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

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
    // EP-Konto-Auszug als CSV (REP-02).
    Route::get('heroes/{hero}/ep-export', [HeroController::class, 'epExport'])->name('heroes.ep.export');
    // Charakterbogen als PDF (REP-05).
    Route::get('heroes/{hero}/sheet-pdf', [HeroController::class, 'sheetPdf'])->name('heroes.sheet-pdf');
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
    // Kalenderansicht kommender Events (ADV-12).
    Route::get('adventures-calendar', [AdventureController::class, 'calendar'])->name('adventures.calendar');
    // Verwaltungsliste der Events (ADV-06): getrennt von der Browse-Liste.
    Route::get('adventures-manage', [AdventureController::class, 'manageIndex'])->name('adventures.manage-index');
    // Verwaltungs-Modal mit Tabs (ADV-16): Editor + Anmeldungen + Check-in.
    Route::get('adventures/{adventure}/manage', [AdventureController::class, 'manage'])->name('adventures.manage');
    // Teilnehmerliste als PDF (ADV-17).
    Route::get('adventures/{adventure}/participants-pdf', [AdventureController::class, 'participantsPdf'])->name('adventures.participants-pdf');
    // Teilnahme-/Belegungsreport als CSV (REP-03).
    Route::get('adventures/{adventure}/participation-csv', [AdventureController::class, 'participationCsv'])->name('adventures.participation-csv');
    // Event absagen (ADV-07).
    Route::patch('adventures/{adventure}/cancel', [AdventureController::class, 'cancel'])->name('adventures.cancel');

    // Anmeldungen zu einem Abenteuer.
    // Anmeldeformular als Modal-Unteransicht (ADV-15).
    Route::get('adventures/{adventure}/bookings/create', [BookingController::class, 'create'])
        ->name('adventures.bookings.create');
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
    // Anmeldung ablehnen – Toggle (ADV-18).
    Route::patch('adventures/{adventure}/bookings/{booking}/rejection', [BookingController::class, 'reject'])
        ->name('adventures.bookings.rejection');
    // Unterschrift bei Teilnahme erfassen (ADV-17).
    Route::get('adventures/{adventure}/bookings/{booking}/signature', [SignatureController::class, 'edit'])
        ->name('adventures.bookings.signature.edit');
    Route::put('adventures/{adventure}/bookings/{booking}/signature', [SignatureController::class, 'update'])
        ->name('adventures.bookings.signature.update');
    Route::delete('adventures/{adventure}/bookings/{booking}/signature', [SignatureController::class, 'destroy'])
        ->name('adventures.bookings.signature.destroy');
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
    // Einzel-Check-in umschalten / Teilnehmer abmelden (ADV-18).
    Route::patch('adventures/{adventure}/bookings/{booking}/checkin', [AttendanceController::class, 'toggle'])
        ->name('adventures.bookings.checkin');
    Route::patch('adventures/{adventure}/bookings/{booking}/deregister', [AttendanceController::class, 'deregister'])
        ->name('adventures.bookings.deregister');

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
        Route::get('players/export', [Admin\PlayerController::class, 'export'])->name('players.export'); // REP-04

        // Helden-Klassen-Lookup pflegen (HERO-05).
        // Veranstaltungsorte pflegen (ADV-08).
        Route::get('locations', [Admin\LocationController::class, 'index'])->name('locations.index');
        Route::get('locations/create', [Admin\LocationController::class, 'create'])->name('locations.create');
        Route::post('locations', [Admin\LocationController::class, 'store'])->name('locations.store');
        Route::get('locations/{location}/edit', [Admin\LocationController::class, 'edit'])->name('locations.edit');
        Route::put('locations/{location}', [Admin\LocationController::class, 'update'])->name('locations.update');
        Route::delete('locations/{location}', [Admin\LocationController::class, 'destroy'])->name('locations.destroy');

        // Event-Kategorien (Soft-Delete) & Auftraggeber pflegen (ADV-09).
        Route::get('event-categories', [Admin\EventCategoryController::class, 'index'])->name('event-categories.index');
        Route::get('event-categories/create', [Admin\EventCategoryController::class, 'create'])->name('event-categories.create');
        Route::post('event-categories', [Admin\EventCategoryController::class, 'store'])->name('event-categories.store');
        Route::get('event-categories/{eventCategory}/edit', [Admin\EventCategoryController::class, 'edit'])->name('event-categories.edit');
        Route::put('event-categories/{eventCategory}', [Admin\EventCategoryController::class, 'update'])->name('event-categories.update');
        Route::delete('event-categories/{eventCategory}', [Admin\EventCategoryController::class, 'destroy'])->name('event-categories.destroy');

        // Teilnahme-Rollen pflegen (ADV-10).
        Route::get('event-roles', [Admin\EventRoleController::class, 'index'])->name('event-roles.index');
        Route::get('event-roles/create', [Admin\EventRoleController::class, 'create'])->name('event-roles.create');
        Route::post('event-roles', [Admin\EventRoleController::class, 'store'])->name('event-roles.store');
        Route::get('event-roles/{eventRole}/edit', [Admin\EventRoleController::class, 'edit'])->name('event-roles.edit');
        Route::put('event-roles/{eventRole}', [Admin\EventRoleController::class, 'update'])->name('event-roles.update');
        Route::delete('event-roles/{eventRole}', [Admin\EventRoleController::class, 'destroy'])->name('event-roles.destroy');

        Route::get('event-clients', [Admin\EventClientController::class, 'index'])->name('event-clients.index');
        Route::get('event-clients/create', [Admin\EventClientController::class, 'create'])->name('event-clients.create');
        Route::post('event-clients', [Admin\EventClientController::class, 'store'])->name('event-clients.store');
        Route::get('event-clients/{eventClient}/edit', [Admin\EventClientController::class, 'edit'])->name('event-clients.edit');
        Route::put('event-clients/{eventClient}', [Admin\EventClientController::class, 'update'])->name('event-clients.update');
        Route::delete('event-clients/{eventClient}', [Admin\EventClientController::class, 'destroy'])->name('event-clients.destroy');

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
