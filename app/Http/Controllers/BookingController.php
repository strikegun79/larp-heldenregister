<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingCancelledParticipant;
use App\Notifications\BookingMovedToWaitlist;
use App\Notifications\BookingReinstateRequested;
use App\Notifications\BookingReinstateResult;
use App\Notifications\BookingRejected;
use App\Notifications\BookingWaitlisted;
use App\Notifications\FotoerlaubnisRevoked;
use App\Notifications\PaymentConfirmed;
use App\Notifications\WaitlistPromoted;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['approveReinstate', 'rejectReinstate']);
        // Buchen: adventure.book; Stornieren/Abmelden: adventure.cancel.
        $this->middleware('can:adventure.book')->only(['create', 'store', 'createGuest', 'storeGuest']);
        $this->middleware('can:adventure.cancel')->only('destroy');
        $this->middleware('can:approve-bookings')->only('reinstate');
        // Anmeldedetails nachträglich ändern (BOOK-04).
        $this->middleware('can:adventure.modify')->only(['edit', 'update']);
        // Anmeldebestätigung erneut senden (BOOK-05): Zugriff wird in der Methode geprüft.
        // Ablehnen (ADV-18) + Teamer bestätigen: Bürokrat/Projektleiter/Admin.
        $this->middleware('can:approve-bookings')->only(['reject', 'confirm']);
        // Auf Warteliste verschieben: gleiche Berechtigung wie die Wartelisten-Promotion
        // (vorher ungeschützt – jeder angemeldete Nutzer konnte fremde Buchungen verschieben).
        $this->middleware('can:approve-bookings')->only('moveToWaitlist');
        // Bezahlt-Status pflegen (BOOK-06).
        $this->middleware('can:manage-payments')->only(['togglePaid', 'updatePaymentsReconciled']);
        // Inspektor in der Event-Verwaltung (Issue #9): gleiche Berechtigung wie die Verwaltungsansicht.
        $this->middleware('can:events.edit')->only('inspector');
        // Admin-Anmeldung (Issue #8): Betreuer + Gesundheitsdaten eines beliebigen Spielers laden.
        $this->middleware('can:book-any-player')->only('playerContext');
    }

    /**
     * Inspektor (Slide-In) mit allen wesentlichen Anmeldedaten und den
     * Status-Aktionen einer Anmeldung in der Event-Verwaltung (Issue #9).
     */
    public function inspector(Adventure $adventure, Booking $booking): View
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->load(['player.users', 'bookedBy', 'role', 'requirementConsents']);

        return view('bookings._inspector', [
            'adventure' => $adventure,
            'booking' => $booking,
        ]);
    }

    /**
     * Anmeldeformular als Modal-Unteransicht (ADV-15). Spielerliste auf
     * eigene/betreute begrenzt (BOOK-10).
     * Mit ?all_players=1 (nur für book-any-player) werden alle Spieler angezeigt.
     */
    public function create(Request $request, Adventure $adventure): View
    {
        // Bereits angemeldete Spieler nicht mehr zur Auswahl anbieten (ADV-13).
        $bookedPlayerIds = $adventure->bookings()->pluck('player_id');

        // Admin-Modus: alle Spieler anzeigen (nur wenn berechtigt und explizit angefordert).
        $adminMode = $request->boolean('all_players') && Gate::allows('book-any-player');

        $players = $adminMode
            ? Player::whereNotIn('id', $bookedPlayerIds)->orderBy('name')->get()
            : $request->user()->players()->whereNotIn('players.id', $bookedPlayerIds)->orderBy('name')->get();

        return view('bookings._create', [
            'adventure' => $adventure,
            'players' => $players,
            'roles' => $adminMode
                ? EventRole::orderBy('id')->get()
                : EventRole::forParticipant()->orderBy('id')->get(),
            'adminMode' => $adminMode,
            // Admin-Modus: Notfallnummer kommt vom gewählten Betreuer, nicht vom Admin (Issue #8).
            'userPhone' => $adminMode ? null : $request->user()->phone,
        ]);
    }

    /**
     * Admin-Anmeldung (Issue #8): Betreuer des Spielers (für das Auswahlfeld und die
     * Notfallnummer) sowie Allergien/Medikamente zum Vorbefüllen.
     */
    public function playerContext(Adventure $adventure, Player $player): JsonResponse
    {
        return response()->json([
            'caretakers' => $player->users()->orderBy('lastname')->orderBy('name')->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => trim("{$u->name} {$u->lastname}"),
                    'email' => $u->email,
                    'phone' => $u->phone,
                ])->values(),
            'player_email' => $player->email,
            'allergien' => $player->allergien,
            'medikamente' => $player->medikamente,
        ]);
    }

    /**
     * Einen Spieler zu einem Abenteuer anmelden.
     */
    public function store(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        // Admin-Anmeldung (Issue #8): Bürokrat/Projektleitung/Admin über das Admin-Formular.
        // Ohne Status-Sperre (außer „abgesagt“), immer regulär + bestätigt, Betreuer statt Admin.
        $adminMode = $request->boolean('admin_mode') && Gate::allows('book-any-player');

        $data = $request->validate([
            'caretaker_user_id' => $adminMode
                ? [
                    // Pflicht, sofern der Spieler Betreuer hat; muss zu diesem Spieler gehören.
                    Rule::requiredIf(fn () => DB::table('player_user')->where('player_id', $request->input('player_id'))->exists()),
                    'nullable',
                    'integer',
                    Rule::exists('player_user', 'user_id')->where('player_id', $request->input('player_id')),
                ]
                : ['prohibited'],
            'player_id' => ['required', 'exists:players,id'],
            'event_role_id' => Gate::allows('book-any-player')
                ? ['required', 'exists:event_roles,id']
                : ['required', Rule::in(EventRole::forParticipant()->pluck('id'))],
            'agb' => ['accepted'],
            'fotoerlaubnis' => ['boolean'],
            'vegetarier' => ['boolean'],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'nsc' => ['boolean'],
            'allergien' => ['nullable', 'string'],
            'medikamente' => ['nullable', 'string'],
            // DSGVO Art. 9: Einwilligung erforderlich wenn Gesundheitsdaten angegeben werden.
            'health_data_consent' => [
                Rule::requiredIf(fn () => filled($request->allergien) || filled($request->medikamente)),
                'boolean',
            ],
            'erreichbarkeit' => ['nullable', 'string'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
            'ermaessigung' => ['boolean'],
            // Zusätzliche Anforderungen des Auftraggebers sind Pflicht (Issue #14).
        ] + $adventure->requirementRules(), $adventure->requirementMessages());

        // Ohne book-any-player nur eigene/betreute Spieler buchen (BOOK-10).
        if (! Gate::allows('book-any-player')
            && ! $request->user()->players()->where('players.id', $data['player_id'])->exists()) {
            return $this->fail($request, 'Für diesen Spieler darfst du keine Buchung anlegen.');
        }

        // Kontaktdaten der erziehungsberechtigten Person müssen vollständig sein (ADV-24 / ORGA-01).
        if (! Gate::allows('book-any-player') && ! $request->user()->hasCompleteAddress()) {
            $missing = $request->user()->missingAddressFields();
            $fieldStr = implode(', ', $missing);
            $msg = "Deine Kontaktdaten sind unvollständig ({$fieldStr}). Bitte ergänze sie in deinem Profil.";

            return $this->fail($request, $msg);
        }

        if ($adminMode) {
            if ((int) $adventure->event_status_id === EventStatus::CANCELLED) {
                return $this->fail($request, 'Dieses Abenteuer ist abgesagt – keine Anmeldungen mehr möglich.');
            }
        } elseif (! $adventure->registrationOpen()) {
            return $this->fail($request, 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
        }

        if ($adventure->bookings()->where('player_id', $data['player_id'])->exists()) {
            return $this->fail($request, 'Dieser Spieler ist bereits angemeldet.');
        }

        // Der teilnehmende Held ist automatisch der aktive Held des Spielers (HERO-21).
        $player = Player::find($data['player_id']);

        $isTeamer = EventRole::find($data['event_role_id'])?->is_teamer_like ?? false;
        $ageViolation = ! $isTeamer && $adventure->isOutsideAgeRange($player);
        // Admin-Anmeldung: immer regulärer Platz, auch bei vollem Event oder Altersverstoß.
        $overbooked = $adminMode && ! $isTeamer && $adventure->isFull();
        $waitlisted = ($isTeamer || $adminMode) ? false : ($adventure->shouldWaitlist() || $ageViolation);
        // Event-Einstellung „Anmeldungen bestätigen“: sonst sofort bestätigt (alle Rollen).
        // Admin-Anmeldung: immer sofort bestätigt.
        $confirmed = $adminMode || ! $adventure->requires_confirmation;
        $caretaker = $adminMode && ! empty($data['caretaker_user_id']) ? User::find($data['caretaker_user_id']) : null;

        $booking = $adventure->bookings()->create([
            'player_id' => $data['player_id'],
            'hero_id' => $player?->active_hero_id,
            // Admin-Anmeldung: gewählter Betreuer statt des Admins (Issue #8).
            'booked_by_user_id' => $adminMode ? $caretaker?->id : $request->user()->id,
            'event_role_id' => $data['event_role_id'],
            'agb' => true,
            'fotoerlaubnis' => $request->boolean('fotoerlaubnis'),
            'vegetarier' => $request->boolean('vegetarier'),
            'leih_tunika' => $request->boolean('leih_tunika'),
            'leih_waffe' => $request->boolean('leih_waffe'),
            'nsc' => $request->boolean('nsc'),
            'allergien' => $data['allergien'] ?? null,
            'medikamente' => $data['medikamente'] ?? null,
            'health_data_consent_at' => $request->boolean('health_data_consent') ? now() : null,
            'erreichbarkeit' => $data['erreichbarkeit'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'],
            'ermaessigung' => $request->boolean('ermaessigung'),
            'waitlisted' => $waitlisted,
            // Mit „Anmeldungen bestätigen“ bleibt die Anmeldung offen bis zur Bestätigung.
            'approved_at' => $confirmed ? now() : null,
            'status' => $confirmed ? 'bestaetigt' : 'offen',
        ]);

        $booking->recordRequirementConsents($adventure->activeRequirements());

        // Wartelisten-Modus dauerhaft aktivieren, sobald die Kapazitätsgrenze erreicht ist.
        // Altersverletzungen sind Ausnahmen und aktivieren den Modus nicht.
        if ($booking->waitlisted && ! $ageViolation && ! $adventure->waitlist_mode) {
            $adventure->update(['waitlist_mode' => true]);
        }

        // Admin-Anmeldung: Bestätigung an den gewählten Betreuer, ersatzweise an die Spieler-E-Mail.
        $recipientEmail = $adminMode
            ? ($caretaker?->email ?: $player?->email)
            : ($player?->email ?: $request->user()->email);
        if ($recipientEmail) {
            // NOTI-02b: Warteliste → kein Beitrag, kein QR-Code.
            // Sonst je nach Rolle/Status: bestätigt (mit Bankdaten) oder Eingang (Bestätigung folgt).
            $notification = (! $isTeamer && $booking->waitlisted)
                ? new BookingWaitlisted($booking, $ageViolation)
                : $booking->confirmationNotification();
            Notification::route('mail', $recipientEmail)->notify($notification);
        }

        // M-3: Admin-Buchung für fremden Spieler protokollieren.
        if (Gate::allows('book-any-player')) {
            AuditLogger::log('booking.created', $booking, array_filter([
                'adventure' => $adventure->name,
                'waitlisted' => $booking->waitlisted,
                'event_role_id' => $booking->event_role_id,
                'admin_booking' => $adminMode ?: null,
                'caretaker_user_id' => $caretaker?->id,
                'overbooked' => $overbooked ?: null,
            ], fn ($v) => $v !== null));
        }

        if ($adminMode) {
            $message = ($player?->full_name ?? 'Spieler').' wurde angemeldet und bestätigt.';
            if ($overbooked) {
                $message .= ' Hinweis: Das Abenteuer ist damit überbelegt.';
            }
            if ($ageViolation) {
                $message .= " Hinweis: Alter außerhalb des Limits ({$adventure->age_range_label}).";
            }
            $message .= $recipientEmail
                ? ' Bestätigung gesendet an '.$recipientEmail.'.'
                : ' Keine Bestätigungsmail – weder Betreuer noch Spieler haben eine E-Mail-Adresse.';
        } elseif ($isTeamer) {
            $message = $confirmed
                ? 'Teamer-Anmeldung gespeichert und bestätigt.'
                : 'Teamer-Anmeldung eingegangen – Bestätigung durch die Projektleitung steht aus.';
        } elseif (! $booking->waitlisted) {
            $message = $confirmed
                ? 'Anmeldung gespeichert.'
                : 'Anmeldung eingegangen – sie wird noch von der Projektleitung bestätigt. Die Zahlungsinformationen folgen mit der Bestätigung.';
        } elseif ($ageViolation) {
            $age = $player?->dayofbirth?->age;
            $message = "Anmeldung erfolgt – Spieler ({$age} Jahre) liegt außerhalb des Alterslimits ({$adventure->age_range_label}), daher auf der Warteliste zur manuellen Prüfung.";
        } else {
            $message = 'Anmeldung erfolgt – das Abenteuer ist voll, daher auf der Warteliste.';
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Gast-Anmeldeformular als Modal-Unteransicht (ADV-21).
     */
    public function createGuest(Adventure $adventure): View
    {
        return view('bookings._create_guest', [
            'adventure' => $adventure,
            'roles' => EventRole::forParticipant()->orderBy('id')->get(),
        ]);
    }

    /**
     * Gast (ohne hinterlegten Spieler) zu einem Abenteuer anmelden (ADV-21).
     * Gäste sammeln keine EP. Mehrere Gäste je Nutzer/Event möglich.
     */
    public function storeGuest(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:100'],
            'guest_lastname' => ['required', 'string', 'max:100'],
            'guest_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'guest_place' => ['nullable', 'string', 'max:100'],
            'event_role_id' => ['required', Rule::in(EventRole::forParticipant()->pluck('id'))],
            'agb' => ['accepted'],
            'fotoerlaubnis' => ['boolean'],
            'vegetarier' => ['boolean'],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'nsc' => ['boolean'],
            'allergien' => ['nullable', 'string'],
            'erreichbarkeit' => ['nullable', 'string'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
            'ermaessigung' => ['boolean'],
            // Zusätzliche Anforderungen des Auftraggebers sind Pflicht (Issue #14).
        ] + $adventure->requirementRules(), $adventure->requirementMessages());

        if (! $adventure->registrationOpen() && ! Gate::allows('book-any-player')) {
            return $this->fail($request, 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
        }

        $isTeamer = EventRole::find($data['event_role_id'])?->is_teamer_like ?? false;

        $booking = $adventure->bookings()->create([
            'player_id' => null,
            'booked_by_user_id' => $request->user()->id,
            'guest_name' => $data['guest_name'],
            'guest_lastname' => $data['guest_lastname'],
            'guest_age' => $data['guest_age'] ?? null,
            'guest_place' => $data['guest_place'] ?? null,
            'event_role_id' => $data['event_role_id'],
            'agb' => true,
            'fotoerlaubnis' => $request->boolean('fotoerlaubnis'),
            'vegetarier' => $request->boolean('vegetarier'),
            'leih_tunika' => $request->boolean('leih_tunika'),
            'leih_waffe' => $request->boolean('leih_waffe'),
            'nsc' => $request->boolean('nsc'),
            'allergien' => $data['allergien'] ?? null,
            'erreichbarkeit' => $data['erreichbarkeit'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'],
            'ermaessigung' => $request->boolean('ermaessigung'),
            'waitlisted' => $isTeamer ? false : $adventure->isFull(),
            // Event-Einstellung „Anmeldungen bestätigen“ (gilt für alle Rollen).
            'approved_at' => $adventure->requires_confirmation ? null : now(),
            'status' => $adventure->requires_confirmation ? 'offen' : 'bestaetigt',
        ]);

        $booking->recordRequirementConsents($adventure->activeRequirements());

        $message = ($isTeamer ? 'Gast-Teamer angemeldet.' : 'Gast angemeldet.')
            .(! $isTeamer && $adventure->isFull() ? ' (Warteliste – Event voll.)' : '')
            .($adventure->requires_confirmation ? ' Die Anmeldung ist noch offen und muss bestätigt werden.' : '');

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Bearbeitungsformular einer Anmeldung im Modal (BOOK-04).
     */
    public function edit(Adventure $adventure, Booking $booking): View
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->load('player.users');

        $userPhone = $booking->player?->users->first()?->phone;

        return view('bookings._edit', [
            'adventure' => $adventure,
            'booking' => $booking,
            'roles' => EventRole::forParticipant()->orderBy('id')->get(),
            'userPhone' => $userPhone,
        ]);
    }

    /**
     * Anmeldedetails (Rolle, Flags, Allergien, …) nachträglich ändern (BOOK-04).
     */
    public function update(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        // Nur eigene Anmeldungen bearbeiten – außer man hat Verwaltungsberechtigung (analog destroy).
        if (! Gate::allows('adventure.modify') && ! $this->ownsBooking($request->user(), $booking)) {
            abort(403);
        }

        $canManage = Gate::allows('adventure.modify');

        $data = $request->validate([
            'event_role_id' => ['required', Rule::in(EventRole::forParticipant()->pluck('id'))],
            'fotoerlaubnis' => ['boolean'],
            'vegetarier' => ['boolean'],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'nsc' => ['boolean'],
            'allergien' => ['nullable', 'string'],
            'medikamente' => ['nullable', 'string'],
            'health_data_consent' => [
                Rule::requiredIf(fn () => filled($request->allergien) || filled($request->medikamente)),
                'boolean',
            ],
            'erreichbarkeit' => ['nullable', 'string'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
            'ermaessigung' => $canManage ? ['boolean'] : ['prohibited'],
        ]);

        $updateData = [
            'event_role_id' => $data['event_role_id'],
            'fotoerlaubnis' => $request->boolean('fotoerlaubnis'),
            'vegetarier' => $request->boolean('vegetarier'),
            'leih_tunika' => $request->boolean('leih_tunika'),
            'leih_waffe' => $request->boolean('leih_waffe'),
            'nsc' => $request->boolean('nsc'),
            'allergien' => $data['allergien'] ?? null,
            'medikamente' => $data['medikamente'] ?? null,
            'health_data_consent_at' => $request->boolean('health_data_consent') ? ($booking->health_data_consent_at ?? now()) : null,
            'erreichbarkeit' => $data['erreichbarkeit'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'],
        ];

        if ($canManage) {
            $updateData['ermaessigung'] = $request->boolean('ermaessigung');
        }

        $booking->update($updateData);

        // M-3: Buchungsänderung protokollieren.
        AuditLogger::log('booking.updated', $booking, array_filter([
            'adventure' => $adventure->name,
            'ermaessigung' => $updateData['ermaessigung'] ?? null,
        ], fn ($v) => $v !== null));

        $message = 'Anmeldung aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Wartelisten-Buchung: von Warteliste auf regulären Platz hochstufen und
     * Bestätigungs-Mail mit Bankdaten senden (BOOK-05).
     * Reguläre Buchung: Bestätigungs-Mail erneut senden.
     */
    public function resendConfirmation(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $isAdmin = Gate::allows('approve-bookings');

        // Eigene Buchung: nur erlaubt wenn nicht auf der Warteliste (Wartelisten-Promotion ist Bürokrat-Aktion).
        if (! $isAdmin) {
            abort_unless($this->ownsBooking($request->user(), $booking), 403);
            abort_if($booking->waitlisted, 403);
        }

        if ($booking->is_guest) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Für Gäste kann keine Bestätigungsmail gesendet werden.'], 422)
                : back()->with('error', 'Für Gäste kann keine Bestätigungsmail gesendet werden.');
        }

        $booking->loadMissing(['player.users']);
        $recipientEmail = $booking->player?->email ?: $booking->player?->users()->first()?->email;

        if (! $recipientEmail) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Kein E-Mail-Empfänger für diesen Spieler gefunden.'], 422)
                : back()->with('error', 'Kein E-Mail-Empfänger für diesen Spieler gefunden.');
        }

        $booking->loadMissing(['role']);
        $isTeamer = $booking->role?->is_teamer_like ?? false;

        if ($booking->waitlisted) {
            // Von Warteliste auf regulären Platz hochstufen.
            $booking->update(['waitlisted' => false]);
            // Offene Anmeldung (Event mit „Anmeldungen bestätigen“): noch keine Bankdaten.
            Notification::route('mail', $recipientEmail)->notify($booking->confirmationNotification());
            // M-3: Wartelisten-Promotion durch Admin protokollieren.
            AuditLogger::log('booking.waitlist_promoted', $booking, ['adventure' => $adventure->name]);
            $message = ($booking->player?->full_name ?? 'Spieler').' von der Warteliste auf einen regulären Platz gesetzt.'
                .($booking->status === 'offen' ? ' Die Anmeldung ist noch offen und muss bestätigt werden.' : '')
                .' Mail gesendet an '.$recipientEmail.'.';
        } else {
            // Mail passend zum Status erneut senden (bestätigt: mit Bankdaten, offen: Eingang).
            Notification::route('mail', $recipientEmail)->notify($booking->confirmationNotification());
            $message = ($booking->status === 'offen'
                    ? ($isTeamer ? 'Teamer-Eingangsbestätigung' : 'Eingangsbestätigung')
                    : ($isTeamer ? 'Teamer-Bestätigung' : 'Anmeldebestätigung'))
                .' erneut gesendet an '.$recipientEmail.'.';
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Offene Anmeldung bestätigen (Event-Einstellung „Anmeldungen bestätigen“), alle Rollen.
     * Teilnehmer erhalten jetzt die Zahlungsinformationen, Teamer die Teamer-Bestätigung.
     */
    public function confirm(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        if ($booking->status !== 'offen') {
            return $this->fail($request, 'Nur offene Anmeldungen können bestätigt werden.');
        }

        $booking->update(['status' => 'bestaetigt', 'approved_at' => now()]);
        $booking->loadMissing(['role', 'player.users', 'bookedBy']);

        AuditLogger::log('booking.confirmed', $booking, ['adventure' => $adventure->name]);

        $name = $booking->participant_name ?: 'Teilnehmer';
        $isTeamer = (bool) $booking->role?->is_teamer_like;
        $recipientEmail = $booking->is_guest ? null : $booking->notificationEmail();

        // Warteliste: Zahlungsinformationen erst beim Nachrücken.
        if ($recipientEmail && ($isTeamer || ! $booking->waitlisted)) {
            Notification::route('mail', $recipientEmail)->notify($booking->confirmationNotification());
            $message = "{$name} wurde bestätigt. Bestätigungsmail gesendet an {$recipientEmail}.";
        } elseif ($booking->waitlisted) {
            $message = "{$name} wurde bestätigt (steht noch auf der Warteliste – Zahlungsinformationen folgen beim Nachrücken).";
        } else {
            $message = "{$name} wurde bestätigt. (Kein E-Mail-Empfänger gefunden.)";
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Anmeldung ablehnen bzw. Ablehnung zurücknehmen (ADV-18). Toggle
     * abgelehnt/offen; entfernt eine etwaige Bestätigung.
     */
    public function reject(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $reject = $booking->status !== 'abgelehnt';
        // Rücknahme: je nach Event-Einstellung „Anmeldungen bestätigen“ wieder offen oder bestätigt.
        $restored = $adventure->requires_confirmation ? 'offen' : 'bestaetigt';
        $booking->update([
            'status' => $reject ? 'abgelehnt' : $restored,
            'approved_at' => (! $reject && $restored === 'bestaetigt') ? now() : null,
        ]);

        // M-3: Ablehnung/Rücknahme protokollieren.
        AuditLogger::log(
            $reject ? 'booking.rejected' : 'booking.rejection_revoked',
            $booking,
            ['adventure' => $adventure->name]
        );

        $message = $reject
            ? 'Anmeldung abgelehnt.'
            : 'Ablehnung zurückgenommen – Status jetzt „'.Booking::STATUS_LABELS[$restored].'“.';

        // Rücknahme: Familie/Teamer erfährt, dass die Ablehnung aufgehoben ist
        // (bestätigt → Bestätigung, offen → Eingangsbestätigung; Warteliste: keine Zahlungsmail).
        if (! $reject && ! $booking->is_guest) {
            $booking->loadMissing(['role', 'player.users', 'bookedBy']);
            $isTeamer = (bool) $booking->role?->is_teamer_like;
            $recipientEmail = $booking->notificationEmail();
            if ($recipientEmail && ($isTeamer || ! $booking->waitlisted)) {
                Notification::route('mail', $recipientEmail)->notify($booking->confirmationNotification());
                $message .= ' Mail gesendet an '.$recipientEmail.'.';
            }
        }

        // NOTI-10: Ablehnungs-Mail + Portal an den Spieler (nur beim Ablehnen, nicht beim Zurücknehmen).
        if ($reject && $booking->player?->notificationEnabled('notify_booking_rejected')) {
            $user = $booking->player->users()->first();
            if ($user) {
                $user->notify(new BookingRejected($booking));
            } elseif ($booking->player->email) {
                Notification::route('mail', $booking->player->email)->notify(new BookingRejected($booking));
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Bestätigte Buchung auf die Warteliste verschieben und Teilnehmer/Betreuer benachrichtigen.
     */
    public function moveToWaitlist(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        if ($booking->waitlisted) {
            $msg = 'Buchung ist bereits auf der Warteliste.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $booking->update(['waitlisted' => true]);

        // M-3: Verschiebung auf Warteliste protokollieren.
        AuditLogger::log('booking.moved_to_waitlist', $booking, ['adventure' => $adventure->name]);

        // E-Mail an Spieler-User oder Betreuer
        $booking->loadMissing(['player.users']);
        $user = $booking->player?->users()->first();
        $email = $booking->player?->email;

        if ($user) {
            $user->notify(new BookingMovedToWaitlist($booking));
        } elseif ($email) {
            Notification::route('mail', $email)->notify(new BookingMovedToWaitlist($booking));
        }

        $name = $booking->player?->full_name ?? 'Spieler';
        $message = "{$name} wurde auf die Warteliste verschoben.";
        if (! $user && ! $email) {
            $message .= ' (Kein E-Mail-Empfänger gefunden, keine Mail versendet.)';
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Teilnahmebeitrag-Status einer Anmeldung umschalten (BOOK-06).
     */
    public function togglePaid(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $wasPaid = $booking->paid;
        $booking->update(['paid' => ! $booking->paid]);

        // M-3: Zahlungsstatus-Änderung protokollieren.
        AuditLogger::log('booking.paid_toggled', $booking, [
            'adventure' => $adventure->name,
            'paid' => $booking->paid,
        ]);

        $message = $booking->paid ? 'Als bezahlt markiert.' : 'Als offen markiert.';

        // NOTI-10: Zahlungsbestätigung + Portal an den Spieler (nur beim Setzen auf bezahlt).
        if (! $wasPaid && $booking->paid && $booking->player?->notificationEnabled('notify_payment_confirmed')) {
            $user = $booking->player->users()->first();
            if ($user) {
                $user->notify(new PaymentConfirmed($booking));
            } elseif ($booking->player->email) {
                Notification::route('mail', $booking->player->email)->notify(new PaymentConfirmed($booking));
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Issue #5: Datum des letzten Bezahlungsabgleichs setzen („Heute abgeglichen“),
     * frei wählen oder leeren. Teilnehmer sehen das Datum bei offenen Beiträgen.
     */
    public function updatePaymentsReconciled(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'payments_reconciled_at' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'payments_reconciled_at.before_or_equal' => 'Der Abgleich kann nicht in der Zukunft liegen.',
        ]);

        $date = $request->boolean('today') ? now()->toDateString() : ($data['payments_reconciled_at'] ?? null);
        $adventure->update(['payments_reconciled_at' => $date]);

        AuditLogger::log('adventure.payments_reconciled', $adventure, [
            'adventure' => $adventure->name,
            'payments_reconciled_at' => $date,
        ]);

        $message = $date
            ? 'Letzter Abgleich: '.$adventure->payments_reconciled_at->format('d.m.Y').'.'
            : 'Datum des letzten Abgleichs entfernt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Eine Anmeldung stornieren.
     */
    public function destroy(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        // Nur eigene Anmeldungen stornieren – außer man darf alle sehen/verwalten.
        if (! Gate::allows('view-all-bookings') && ! $this->ownsBooking($request->user(), $booking)) {
            abort(403);
        }

        // Bereits stornierte Anmeldungen können nicht erneut storniert werden.
        if ($booking->status === 'storniert') {
            $msg = 'Diese Anmeldung ist bereits storniert.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $participant = $booking->participant_name;
        $cancelUser = $booking->player?->users()->first();
        $cancelEmail = $booking->player?->email;
        $notifyCancel = $booking->player?->notificationEnabled('notify_booking_cancelled') ?? false;

        // M-3: Stornierung protokollieren.
        AuditLogger::log('booking.cancelled', $booking, ['adventure' => $adventure->name]);

        // Anmeldung auf "storniert" setzen – Datensatz bleibt erhalten (Nachweis bei bezahlten Buchungen).
        $wasRegular = ! $booking->waitlisted;
        $booking->update(['status' => 'storniert']);

        // Buchungsstatistiken nach der Stornierung berechnen.
        $confirmedCount = $adventure->bookings()
            ->where('waitlisted', false)
            ->where('status', '!=', 'storniert')
            ->count();
        $waitlistCount = $adventure->bookings()
            ->where('waitlisted', true)
            ->where('status', '!=', 'storniert')
            ->count();
        $cancelledByName = trim(($request->user()->name ?? '').' '.($request->user()->lastname ?? '')) ?: 'Unbekannt';

        // NOTI-10: Stornierungsbestätigung + Portal an den Teilnehmer selbst.
        if ($notifyCancel) {
            if ($cancelUser) {
                $cancelUser->notify(new BookingCancelledParticipant($adventure, $participant));
            } elseif ($cancelEmail) {
                Notification::route('mail', $cancelEmail)->notify(new BookingCancelledParticipant($adventure, $participant));
            }
        }

        // ADV-21: Nur den Projektleiter des Events über die Stornierung informieren.
        $adventure->loadMissing('eventleader');
        $leaders = $adventure->eventleader
            ? collect([$adventure->eventleader])
            : collect();
        if ($leaders->isNotEmpty()) {
            Notification::send($leaders, new BookingCancelled(
                $adventure,
                $participant,
                $cancelledByName,
                $confirmedCount,
                $adventure->max_player,
                $waitlistCount,
            ));
        }

        // Automatisches Nachrücken nur wenn Wartelisten-Modus deaktiviert ist.
        $promoted = null;
        if ($wasRegular && ! $adventure->waitlist_mode) {
            $promoted = $adventure->bookings()
                ->where('waitlisted', true)
                ->orderBy('created_at')
                ->orderBy('id')
                ->first();

            if ($promoted) {
                $promoted->update(['waitlisted' => false]);
                // NOTI-03: Benachrichtigung an den nachgerückten Spieler (mit Bankdaten).
                $promotedEmail = $promoted->player?->email
                    ?: $promoted->player?->users()->first()?->email;
                if ($promotedEmail && $promoted->player?->notificationEnabled('notify_waitlist_promoted')) {
                    Notification::route('mail', $promotedEmail)->notify(new WaitlistPromoted($promoted));
                }
            }
        }

        $message = 'Anmeldung wurde storniert.';
        if ($promoted) {
            $name = $promoted->player?->full_name ?? 'Ein Wartelistenplatz';
            $message .= " {$name} ist von der Warteliste nachgerückt.";
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Stornierte Anmeldung wiederherstellen (nur Projektleiter/Admin).
     * Setzt Status zurück auf „offen" und gibt den regulären Platz zurück.
     */
    public function reinstate(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        if ($booking->status !== 'storniert') {
            $msg = 'Diese Anmeldung ist nicht storniert.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $booking->update(['status' => 'bestaetigt', 'waitlisted' => false]);

        AuditLogger::log('booking.reinstated', $booking, ['adventure' => $adventure->name]);

        $msg = 'Anmeldung wurde wiederhergestellt und bestätigt.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    /**
     * Fotoerlaubnis widerrufen oder erneut erteilen (N-2 / Art. 7 DSGVO).
     * Zugänglich für den buchenden Erziehungsberechtigten und für adventure.modify.
     */
    public function toggleFotoerlaubnis(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        if (! Gate::allows('adventure.modify') && ! $this->ownsBooking($request->user(), $booking)) {
            abort(403);
        }

        $booking->update(['fotoerlaubnis' => ! $booking->fotoerlaubnis]);

        $action = $booking->fotoerlaubnis ? 'booking.fotoerlaubnis_granted' : 'booking.fotoerlaubnis_revoked';
        AuditLogger::log($action, $booking, ['adventure' => $adventure->name]);

        // Bei Widerruf: Projektleitung + Portal-Kontakt benachrichtigen (N-2 / Art. 7 DSGVO).
        if (! $booking->fotoerlaubnis) {
            $notification = new FotoerlaubnisRevoked($booking, $adventure);

            // Alle Nutzer mit Projektleiter-Rolle (id=30) + ggf. Event-Leitung.
            $leaders = User::whereHas('roles', fn ($q) => $q->where('roles.id', 30))->get();
            if ($adventure->eventleader_id && ! $leaders->contains('id', $adventure->eventleader_id)) {
                $adventure->loadMissing('eventleader');
                if ($adventure->eventleader) {
                    $leaders->push($adventure->eventleader);
                }
            }
            if ($leaders->isNotEmpty()) {
                Notification::send($leaders, $notification);
            }

            // Portal-Kontakt-E-Mail aus den Einstellungen.
            $contactEmail = Setting::get('contact_email');
            if ($contactEmail) {
                Notification::route('mail', $contactEmail)->notify($notification);
            }
        }

        $message = $booking->fotoerlaubnis
            ? 'Fotoerlaubnis erteilt.'
            : 'Fotoerlaubnis widerrufen. Projektleitung und Kontakt wurden benachrichtigt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Fachlicher Fehler: bei AJAX als 422-JSON (Toast), sonst zurück mit Flash.
     */
    private function fail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('error', $message);
    }

    /**
     * Gehört die Anmeldung dem Nutzer (selbst angemeldet oder eigener Spieler)?
     */
    /**
     * Nutzer beantragt die Rücknahme einer stornierten Anmeldung.
     * Sendet eine E-Mail mit Genehmigen-/Ablehnen-Links an die Projektleitung.
     */
    public function requestReinstate(Request $request, Adventure $adventure, Booking $booking): JsonResponse|RedirectResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        if (! $this->ownsBooking($request->user(), $booking)) {
            abort(403);
        }

        if ($booking->status !== 'storniert') {
            $msg = 'Diese Anmeldung ist nicht storniert.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $participant = $booking->participant_name;
        $requesterName = trim(($request->user()->name ?? '').' '.($request->user()->lastname ?? '')) ?: 'Unbekannt';

        // Nur den Projektleiter der Veranstaltung benachrichtigen.
        $adventure->loadMissing('eventleader');
        $leaders = $adventure->eventleader
            ? collect([$adventure->eventleader])
            : collect();

        if ($leaders->isNotEmpty()) {
            Notification::send($leaders, new BookingReinstateRequested(
                $adventure,
                $booking,
                $participant,
                $requesterName,
                $validated['message'],
            ));
        }

        $booking->update(['reinstate_requested' => true]);
        AuditLogger::log('booking.reinstate_requested', $booking, ['adventure' => $adventure->name]);

        $msg = 'Deine Anfrage wurde an die Projektleitung gesendet.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg])
            : back()->with('status', $msg);
    }

    /**
     * Projektleiter genehmigt die Rücknahme (signierter Link aus E-Mail).
     */
    public function approveReinstate(Request $request, Adventure $adventure, Booking $booking): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($booking->adventure_id === $adventure->id, 404);

        if ($booking->status !== 'storniert') {
            return redirect()->route('adventures.manage-index')
                ->with('status', 'Diese Anmeldung ist nicht storniert – keine Änderung nötig.');
        }

        $booking->update(['status' => 'bestaetigt', 'waitlisted' => false, 'reinstate_requested' => false]);
        AuditLogger::log('booking.reinstated', $booking, ['adventure' => $adventure->name]);

        // Nutzer über Genehmigung informieren.
        $cancelUser = $booking->player?->users()->first();
        $cancelEmail = $booking->player?->email;
        if ($cancelUser) {
            $cancelUser->notify(new BookingReinstateResult($adventure, $booking->participant_name, true));
        } elseif ($cancelEmail) {
            Notification::route('mail', $cancelEmail)
                ->notify(new BookingReinstateResult($adventure, $booking->participant_name, true));
        }

        return redirect()->route('adventures.manage-index')
            ->with('status', 'Stornierung von '.$booking->participant_name.' wurde rückgängig gemacht.');
    }

    /**
     * Projektleiter lehnt die Rücknahme ab (signierter Link aus E-Mail).
     */
    public function rejectReinstate(Request $request, Adventure $adventure, Booking $booking): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->update(['reinstate_requested' => false]);
        AuditLogger::log('booking.reinstate_rejected', $booking, ['adventure' => $adventure->name]);

        // Nutzer über Ablehnung informieren.
        $cancelUser = $booking->player?->users()->first();
        $cancelEmail = $booking->player?->email;
        if ($cancelUser) {
            $cancelUser->notify(new BookingReinstateResult($adventure, $booking->participant_name, false));
        } elseif ($cancelEmail) {
            Notification::route('mail', $cancelEmail)
                ->notify(new BookingReinstateResult($adventure, $booking->participant_name, false));
        }

        return redirect()->route('adventures.manage-index')
            ->with('status', 'Rücknahme-Anfrage von '.$booking->participant_name.' wurde abgelehnt.');
    }

    private function ownsBooking(User $user, Booking $booking): bool
    {
        return $booking->booked_by_user_id === $user->id
            || ($booking->player_id !== null && $user->players()->where('players.id', $booking->player_id)->exists());
    }
}
