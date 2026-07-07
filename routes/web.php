<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AdventureController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GroupBookingController;
use App\Http\Controllers\EpTransactionController;
use App\Http\Controllers\HeroClassController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\HeroGalleryController;
use App\Http\Controllers\HeroSkillController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicHeroController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\SkillController;
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

// PUB-02/03/06: Öffentliche Helden-Routen mit Rate-Limiting (30/min je IP).
Route::middleware('throttle:public-hero')->group(function () {
    // PUB-03: Suchformular + Weiterleitung (vor {code}-Route registriert).
    Route::get('/h', [PublicHeroController::class, 'index'])->name('public.hero.search');
    Route::get('/h/search', [PublicHeroController::class, 'search'])->name('public.hero.search.go');

    // PUB-02: Öffentliches Helden-Profil.
    Route::get('/h/{code}', [PublicHeroController::class, 'show'])->name('public.hero');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// Funktionsübersicht & Hilfe für Vorstand/Auftraggeber (INFO-01).
Route::get('/info', fn () => view('info'))->middleware(['auth', 'verified'])->name('info');

// Alle Portal-Routen erfordern Login UND verifizierte E-Mail-Adresse (AUTH-07/AUTH-02).
Route::middleware(['auth', 'verified'])->group(function () {
    // In-App-Benachrichtigungen (NOTI-07).
    Route::get('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // UI-45: Datensparmodus manuell umschalten.
    Route::post('/save-data/toggle', function (\Illuminate\Http\Request $request) {
        $current = $request->session()->get('save_data', false);
        $request->session()->put('save_data', ! $current);
        return back();
    })->name('save-data.toggle');

    // Fertigkeiten-Katalog (SKILL-04) + aktive Helden-Modal (SKILL-09).
    Route::get('skills', [SkillController::class, 'index'])->name('skills.catalog');
    Route::get('skills/{skill}/heroes', [SkillController::class, 'heroes'])->name('skills.catalog.heroes');

    // Eigenständige EP-Buchung (EP-02).
    Route::get('ep-booking', [EpTransactionController::class, 'create'])->name('ep.create');
    Route::post('ep-booking', [EpTransactionController::class, 'storeManual'])->name('ep.store-manual');

    Route::resource('players', PlayerController::class);
    // Aktiven Helden eines Spielers setzen (HERO-07).
    Route::patch('players/{player}/active-hero', [PlayerController::class, 'setActiveHero'])->name('players.active-hero');
    // Avatar-Upload und -Löschen im Avatar-Tab (PLAY-11).
    Route::post('players/{player}/avatar', [PlayerController::class, 'uploadAvatar'])->name('players.avatar');
    Route::delete('players/{player}/avatar', [PlayerController::class, 'deleteAvatar'])->name('players.avatar.destroy');
    Route::resource('heroes', HeroController::class);
    // Verschollen-Status umschalten (HERO-08).
    Route::patch('heroes/{hero}/missing', [HeroController::class, 'toggleMissing'])->name('heroes.missing');
    Route::patch('heroes/{hero}/visibility', [HeroController::class, 'toggleVisibility'])->name('heroes.visibility');
    Route::patch('heroes/{hero}/searchable', [HeroController::class, 'toggleSearchable'])->name('heroes.searchable');
    // EP-Buchung für einen Helden (HERO-12).
    Route::post('heroes/{hero}/ep', [EpTransactionController::class, 'store'])->name('heroes.ep.store');
    // EP-Konto-Auszug als CSV (REP-02).
    Route::get('heroes/{hero}/ep-export', [HeroController::class, 'epExport'])->name('heroes.ep.export');
    // Charakterbogen als PDF (REP-05).
    Route::get('heroes/{hero}/sheet-pdf', [HeroController::class, 'sheetPdf'])->name('heroes.sheet-pdf');
    // Helden-Foto Upload + Löschen (HERO-22).
    Route::post('heroes/{hero}/photo', [HeroController::class, 'uploadPhoto'])->name('heroes.photo');
    Route::delete('heroes/{hero}/photo', [HeroController::class, 'deletePhoto'])->name('heroes.photo.destroy');
    // Galerie (HERO-24).
    Route::post('heroes/{hero}/gallery', [HeroGalleryController::class, 'store'])->name('heroes.gallery.store');
    Route::delete('heroes/{hero}/gallery/{image}', [HeroGalleryController::class, 'destroy'])->name('heroes.gallery.destroy');
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
    // Gruppen-Anmeldung (GRP-06).
    Route::get('adventures/{adventure}/group-bookings/create', [GroupBookingController::class, 'create'])
        ->name('adventures.group-bookings.create');
    Route::post('adventures/{adventure}/group-bookings', [GroupBookingController::class, 'store'])
        ->name('adventures.group-bookings.store');
    // Gast-Anmeldung (ADV-21).
    Route::get('adventures/{adventure}/bookings/create-guest', [BookingController::class, 'createGuest'])
        ->name('adventures.bookings.create-guest');
    Route::post('adventures/{adventure}/bookings/guest', [BookingController::class, 'storeGuest'])
        ->name('adventures.bookings.store-guest');
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
        Route::get('roles', [Admin\RoleController::class, 'index'])->name('roles.index');
        // Nutzerverwaltung erfordert zusätzlich users.manage.
        Route::middleware('can:users.manage')->group(function () {
            Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [Admin\UserController::class, 'create'])->name('users.create');
            Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
            Route::get('users/{user}/profile', [Admin\UserController::class, 'showProfile'])->name('users.profile');
            Route::patch('users/{user}/profile', [Admin\UserController::class, 'updateProfile'])->name('users.profile.update');
            Route::patch('users/{user}/password', [Admin\UserController::class, 'updatePassword'])->name('users.password.update');
            Route::patch('users/{user}/notifications', [Admin\UserController::class, 'updateNotifications'])->name('users.notifications.update');
            Route::delete('users/{id}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
            Route::patch('users/{id}/restore', [Admin\UserController::class, 'restore'])->name('users.restore');
        });
        Route::get('players', [Admin\PlayerController::class, 'index'])->name('players.index');
        Route::get('players/export', [Admin\PlayerController::class, 'export'])->name('players.export'); // REP-04
        // Soft-Delete + Wiederherstellung (PLAY-08).
        Route::delete('players/{id}', [Admin\PlayerController::class, 'destroy'])->name('players.destroy');
        Route::patch('players/{id}/restore', [Admin\PlayerController::class, 'restore'])->name('players.restore');
        // Kinder-Anschrift bearbeiten (PLAY-13 / ORGA-01).
        Route::get('players/{player}/edit', [Admin\PlayerController::class, 'edit'])->name('players.edit');
        Route::put('players/{player}', [Admin\PlayerController::class, 'update'])->name('players.update');
        // Betreuer je Spieler verwalten (PLAY-06).
        Route::get('players/{player}/caretakers', [Admin\PlayerController::class, 'caretakers'])->name('players.caretakers');
        Route::post('players/{player}/caretakers', [Admin\PlayerController::class, 'attachCaretaker'])->name('players.caretakers.store');
        Route::delete('players/{player}/caretakers/{user}', [Admin\PlayerController::class, 'detachCaretaker'])->name('players.caretakers.destroy');

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

        Route::get('event-statuses', [Admin\EventStatusController::class, 'index'])->name('event-statuses.index');
        Route::get('event-statuses/{eventStatus}/edit', [Admin\EventStatusController::class, 'edit'])->name('event-statuses.edit');
        Route::put('event-statuses/{eventStatus}', [Admin\EventStatusController::class, 'update'])->name('event-statuses.update');
        Route::delete('event-statuses/{eventStatus}', [Admin\EventStatusController::class, 'destroy'])->name('event-statuses.destroy');

        Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');

        Route::get('hero-classes', [Admin\HeroClassController::class, 'index'])->name('hero-classes.index');
        Route::get('hero-classes/create', [Admin\HeroClassController::class, 'create'])->name('hero-classes.create');
        Route::post('hero-classes', [Admin\HeroClassController::class, 'store'])->name('hero-classes.store');
        Route::get('hero-classes/{heroClass}/edit', [Admin\HeroClassController::class, 'edit'])->name('hero-classes.edit');
        Route::put('hero-classes/{heroClass}', [Admin\HeroClassController::class, 'update'])->name('hero-classes.update');
        // Klassenband-Bild hochladen / löschen (162×600 px).
        Route::post('hero-classes/{heroClass}/ribbon', [Admin\HeroClassController::class, 'storeRibbon'])->name('hero-classes.ribbon.store');
        Route::delete('hero-classes/{heroClass}/ribbon', [Admin\HeroClassController::class, 'destroyRibbon'])->name('hero-classes.ribbon.destroy');

        // Fertigkeiten-Verwaltung (SKILL-02 + SKILL-03).
        Route::get('skills', [Admin\SkillController::class, 'index'])->name('skills.index');
        Route::get('skills/create', [Admin\SkillController::class, 'create'])->name('skills.create');
        Route::post('skills', [Admin\SkillController::class, 'store'])->name('skills.store');
        Route::get('skills/{skill}/edit', [Admin\SkillController::class, 'edit'])->name('skills.edit');
        Route::put('skills/{skill}', [Admin\SkillController::class, 'update'])->name('skills.update');
        Route::delete('skills/{skill}', [Admin\SkillController::class, 'destroy'])->name('skills.destroy');
        // Fertigkeits-Symbol (SKILL-08).
        Route::post('skills/{skill}/icon', [Admin\SkillIconController::class, 'store'])->name('skills.icon.store');
        Route::delete('skills/{skill}/icon', [Admin\SkillIconController::class, 'destroy'])->name('skills.icon.destroy');
        // Aktive Helden je Fertigkeit – Modal (SKILL-09).
        Route::get('skills/{skill}/heroes', [Admin\SkillController::class, 'heroes'])->name('skills.heroes');

        // Perlenfarben pflegen (EP-05).
        Route::get('perl-colors', [Admin\PerlColorController::class, 'index'])->name('perl-colors.index');
        Route::get('perl-colors/create', [Admin\PerlColorController::class, 'create'])->name('perl-colors.create');
        Route::post('perl-colors', [Admin\PerlColorController::class, 'store'])->name('perl-colors.store');
        Route::get('perl-colors/{perlColor}/edit', [Admin\PerlColorController::class, 'edit'])->name('perl-colors.edit');
        Route::put('perl-colors/{perlColor}', [Admin\PerlColorController::class, 'update'])->name('perl-colors.update');
        Route::delete('perl-colors/{perlColor}', [Admin\PerlColorController::class, 'destroy'])->name('perl-colors.destroy');

        // EP-Buchungsarten pflegen (EP-06).
        Route::get('ep-transaction-types', [Admin\EpTransactionTypeController::class, 'index'])->name('ep-transaction-types.index');
        Route::get('ep-transaction-types/create', [Admin\EpTransactionTypeController::class, 'create'])->name('ep-transaction-types.create');
        Route::post('ep-transaction-types', [Admin\EpTransactionTypeController::class, 'store'])->name('ep-transaction-types.store');
        Route::get('ep-transaction-types/{epTransactionType}/edit', [Admin\EpTransactionTypeController::class, 'edit'])->name('ep-transaction-types.edit');
        Route::put('ep-transaction-types/{epTransactionType}', [Admin\EpTransactionTypeController::class, 'update'])->name('ep-transaction-types.update');
        Route::delete('ep-transaction-types/{epTransactionType}', [Admin\EpTransactionTypeController::class, 'destroy'])->name('ep-transaction-types.destroy');

        // Matrix-Konto-Provisionierung pro Spieler (corporal User-DB).
        Route::get('players/{player}/matrix', [Admin\MatrixAccountController::class, 'edit'])->name('players.matrix.edit');
        Route::put('players/{player}/matrix', [Admin\MatrixAccountController::class, 'update'])->name('players.matrix.update');
        Route::delete('players/{player}/matrix', [Admin\MatrixAccountController::class, 'destroy'])->name('players.matrix.destroy');

        // Matrix-Räume verwalten (MTX-05).
        Route::resource('matrix/rooms', Admin\MatrixRoomController::class)
            ->parameters(['rooms' => 'room'])
            ->names('matrix.rooms');
    });

    // Teamer-Anmeldungen (ADV-27): Teamer/Lehrmeister melden sich zu Events an.
    Route::get('adventures/{adventure}/teamer-signup', [\App\Http\Controllers\TeamerSignupController::class, 'create'])
        ->name('adventures.teamer.create');
    Route::post('adventures/{adventure}/teamer-signup', [\App\Http\Controllers\TeamerSignupController::class, 'store'])
        ->name('adventures.teamer.store');
    Route::delete('adventures/{adventure}/teamer-signup/{signup}', [\App\Http\Controllers\TeamerSignupController::class, 'destroy'])
        ->name('adventures.teamer.destroy');
    Route::patch('adventures/{adventure}/teamer-signup/{signup}/role', [\App\Http\Controllers\TeamerSignupController::class, 'updateRole'])
        ->name('adventures.teamer.update-role');
    Route::post('adventures/{adventure}/teamer-invite', [\App\Http\Controllers\TeamerSignupController::class, 'invite'])
        ->name('adventures.teamer.invite');
    Route::get('adventures/{adventure}/teamer-signup/{signup}/edit', [\App\Http\Controllers\TeamerSignupController::class, 'edit'])
        ->name('adventures.teamer.edit');
    Route::put('adventures/{adventure}/teamer-signup/{signup}', [\App\Http\Controllers\TeamerSignupController::class, 'update'])
        ->name('adventures.teamer.update');
    Route::patch('adventures/{adventure}/teamer-signup/{signup}/approve', [\App\Http\Controllers\TeamerSignupController::class, 'approve'])
        ->name('adventures.teamer.approve');
    Route::patch('adventures/{adventure}/teamer-signup/{signup}/reject', [\App\Http\Controllers\TeamerSignupController::class, 'reject'])
        ->name('adventures.teamer.reject');

    // PUB-10: Heldenausweis-Generator (Admin/Bürokrat = heldenregister.edit).
    Route::prefix('admin')->name('admin.')->middleware('can:heldenregister.edit')->group(function () {
        Route::get('id-cards', [Admin\IdCardController::class, 'index'])->name('id-cards.index');
        Route::post('id-cards/generate', [Admin\IdCardController::class, 'generate'])->name('id-cards.generate');
        Route::get('id-cards/{hero}/reprint', [Admin\IdCardController::class, 'reprint'])->name('id-cards.reprint');
        Route::delete('id-cards/{code}', [Admin\IdCardController::class, 'destroy'])->name('id-cards.destroy');
    });

    // Code einem Helden zuweisen (heldenregister.edit).
    Route::patch('heroes/{hero}/assign-code', [Admin\IdCardController::class, 'assign'])
        ->middleware('can:heldenregister.edit')
        ->name('heroes.assign-code');

    // Gruppen-CRUD (GRP-02) + Mitglieder (GRP-03): Berechtigung groups.manage.
    Route::prefix('admin')->name('admin.')->middleware('can:groups.manage')->group(function () {
        Route::get('groups', [Admin\GroupController::class, 'index'])->name('groups.index');
        Route::get('groups/create', [Admin\GroupController::class, 'create'])->name('groups.create');
        Route::post('groups', [Admin\GroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{group}', [Admin\GroupController::class, 'show'])->name('groups.show');
        Route::get('groups/{group}/edit', [Admin\GroupController::class, 'edit'])->name('groups.edit');
        Route::put('groups/{group}', [Admin\GroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}', [Admin\GroupController::class, 'destroy'])->name('groups.destroy');
        // GRP-03: Mitglieder verwalten.
        Route::get('groups/{group}/members', [Admin\GroupMemberController::class, 'index'])->name('groups.members');
        Route::post('groups/{group}/members', [Admin\GroupMemberController::class, 'store'])->name('groups.members.store');
        Route::delete('groups/{group}/members/{hero}', [Admin\GroupMemberController::class, 'destroy'])->name('groups.members.destroy');
    });
});

require __DIR__.'/auth.php';
