<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\Player;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingWaitlisted;
use App\Notifications\BookingCancelledParticipant;
use App\Notifications\BookingReceived;
use App\Notifications\BookingMovedToWaitlist;
use App\Notifications\BookingRejected;
use App\Notifications\PaymentConfirmed;
use App\Notifications\WaitlistPromoted;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Buchen: adventure.book; Stornieren/Abmelden: adventure.cancel.
        $this->middleware('can:adventure.book')->only(['create', 'store', 'createGuest', 'storeGuest']);
        $this->middleware('can:adventure.cancel')->only('destroy');
        // Anmeldedetails nachträglich ändern (BOOK-04).
        $this->middleware('can:adventure.modify')->only(['edit', 'update']);
        // Anmeldebestätigung erneut senden (BOOK-05): Zugriff wird in der Methode geprüft.
        // Ablehnen (ADV-18): nur Bürokrat/Admin.
        $this->middleware('can:approve-bookings')->only(['reject']);
        // Bezahlt-Status pflegen (BOOK-06).
        $this->middleware('can:manage-payments')->only('togglePaid');
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
            'players'   => $players,
            'roles'     => $adminMode
                ? EventRole::orderBy('id')->get()
                : EventRole::whereNotIn('id', EventRole::TEAMER_ROLE_IDS)->orderBy('id')->get(),
            'adminMode' => $adminMode,
            'userPhone' => $request->user()->phone,
        ]);
    }

    /**
     * Einen Spieler zu einem Abenteuer anmelden.
     */
    public function store(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'event_role_id' => Gate::allows('book-any-player')
                ? ['required', 'exists:event_roles,id']
                : ['required', 'exists:event_roles,id', 'not_in:'.implode(',', EventRole::TEAMER_ROLE_IDS)],
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
        ]);

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

        if (! $adventure->registrationOpen()) {
            return $this->fail($request, 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
        }

        if ($adventure->bookings()->where('player_id', $data['player_id'])->exists()) {
            return $this->fail($request, 'Dieser Spieler ist bereits angemeldet.');
        }

        // Der teilnehmende Held ist automatisch der aktive Held des Spielers (HERO-21).
        $player = Player::find($data['player_id']);

        $isTeamer     = in_array((int) $data['event_role_id'], EventRole::TEAMER_ROLE_IDS);
        $ageViolation = ! $isTeamer && $adventure->isOutsideAgeRange($player);
        $waitlisted   = $isTeamer ? false : ($adventure->shouldWaitlist() || $ageViolation);

        $booking = $adventure->bookings()->create([
            'player_id' => $data['player_id'],
            'hero_id' => $player?->active_hero_id,
            'booked_by_user_id' => $request->user()->id,
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
            // Keine manuelle Bestätigung mehr nötig – direkt bestätigt.
            'approved_at' => now(),
            'status' => 'bestaetigt',
        ]);

        // Wartelisten-Modus dauerhaft aktivieren, sobald die Kapazitätsgrenze erreicht ist.
        // Altersverletzungen sind Ausnahmen und aktivieren den Modus nicht.
        if ($booking->waitlisted && ! $ageViolation && ! $adventure->waitlist_mode) {
            $adventure->update(['waitlist_mode' => true]);
        }

        $recipientEmail = $player?->email ?: $request->user()->email;
        if ($recipientEmail) {
            // NOTI-02b: Warteliste → kein Beitrag, kein QR-Code.
            // NOTI-02:  Regulär  → Bestätigung mit Bankdaten.
            // Pflichtbenachrichtigung: wird immer gesendet.
            $notification = $booking->waitlisted
                ? new BookingWaitlisted($booking, $ageViolation)
                : new BookingReceived($booking);
            Notification::route('mail', $recipientEmail)->notify($notification);
        }

        // M-3: Admin-Buchung für fremden Spieler protokollieren.
        if (Gate::allows('book-any-player')) {
            AuditLogger::log('booking.created', $booking, [
                'adventure' => $adventure->name,
                'waitlisted' => $booking->waitlisted,
                'event_role_id' => $booking->event_role_id,
            ]);
        }

        if (! $booking->waitlisted) {
            $message = 'Anmeldung gespeichert.';
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
            'roles' => EventRole::whereNotIn('id', EventRole::TEAMER_ROLE_IDS)->orderBy('id')->get(),
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
            'event_role_id' => ['required', 'exists:event_roles,id', 'not_in:'.implode(',', EventRole::TEAMER_ROLE_IDS)],
            'agb' => ['accepted'],
            'fotoerlaubnis' => ['boolean'],
            'vegetarier' => ['boolean'],
            'allergien' => ['nullable', 'string'],
            'erreichbarkeit' => ['nullable', 'string'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
            'ermaessigung' => ['boolean'],
        ]);

        if (! $adventure->registrationOpen() && ! Gate::allows('book-any-player')) {
            return $this->fail($request, 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
        }

        $adventure->bookings()->create([
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
            'allergien' => $data['allergien'] ?? null,
            'erreichbarkeit' => $data['erreichbarkeit'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'],
            'ermaessigung' => $request->boolean('ermaessigung'),
            'waitlisted' => $adventure->isFull(),
            'approved_at' => now(),
            'status' => 'bestaetigt',
        ]);

        $message = 'Gast angemeldet.'.($adventure->isFull() ? ' (Warteliste – Event voll.)' : '');

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
            'roles' => EventRole::whereNotIn('id', EventRole::TEAMER_ROLE_IDS)->orderBy('id')->get(),
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
            'event_role_id' => ['required', 'exists:event_roles,id', 'not_in:'.implode(',', EventRole::TEAMER_ROLE_IDS)],
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

        if ($booking->waitlisted) {
            // Von Warteliste auf regulären Platz hochstufen.
            $booking->update(['waitlisted' => false]);
            Notification::route('mail', $recipientEmail)->notify(new BookingReceived($booking));
            // M-3: Wartelisten-Promotion durch Admin protokollieren.
            AuditLogger::log('booking.waitlist_promoted', $booking, ['adventure' => $adventure->name]);
            $message = ($booking->player?->full_name ?? 'Spieler').' von der Warteliste bestätigt. Bestätigungsmail gesendet an '.$recipientEmail.'.';
        } else {
            // Reguläre Buchung: Bestätigungs-Mail nochmals senden.
            Notification::route('mail', $recipientEmail)->notify(new BookingReceived($booking));
            $message = 'Anmeldebestätigung erneut gesendet an '.$recipientEmail.'.';
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
        $booking->update([
            'status' => $reject ? 'abgelehnt' : 'offen',
            'approved_at' => null,
        ]);

        // M-3: Ablehnung/Rücknahme protokollieren.
        AuditLogger::log(
            $reject ? 'booking.rejected' : 'booking.rejection_revoked',
            $booking,
            ['adventure' => $adventure->name]
        );

        $message = $reject ? 'Anmeldung abgelehnt.' : 'Ablehnung zurückgenommen.';

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
        $user  = $booking->player?->users()->first();
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
     * Eine Anmeldung stornieren.
     */
    public function destroy(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        // Nur eigene Anmeldungen stornieren – außer man darf alle sehen/verwalten.
        if (! Gate::allows('view-all-bookings') && ! $this->ownsBooking($request->user(), $booking)) {
            abort(403);
        }

        // Auch bezahlte Anmeldungen dürfen storniert werden (ADV-21) – kein Block.
        $participant = $booking->participant_name;

        // Für Stornierungsbenachrichtigung vorab laden, bevor das Modell gelöscht wird.
        $cancelUser  = $booking->player?->users()->first();
        $cancelEmail = $booking->player?->email;
        $notifyCancel = $booking->player?->notificationEnabled('notify_booking_cancelled') ?? false;

        // M-3: Stornierung protokollieren (vor delete, damit auditLabel noch greift).
        AuditLogger::log('booking.cancelled', $booking, ['adventure' => $adventure->name]);

        // Wird ein regulärer Platz frei, rückt die älteste Wartelisten-Buchung nach (BOOK-07).
        $wasRegular = ! $booking->waitlisted;
        $booking->delete();

        // NOTI-10: Stornierungsbestätigung + Portal an den Teilnehmer selbst.
        if ($notifyCancel) {
            if ($cancelUser) {
                $cancelUser->notify(new BookingCancelledParticipant($adventure));
            } elseif ($cancelEmail) {
                Notification::route('mail', $cancelEmail)->notify(new BookingCancelledParticipant($adventure));
            }
        }

        // ADV-21: Projektleitung über die Stornierung informieren.
        $leaders = User::whereHas('roles', fn ($q) => $q->where('roles.id', 30))->get();
        if ($adventure->eventleader_id && ! $leaders->contains('id', $adventure->eventleader_id)) {
            $adventure->loadMissing('eventleader');
            if ($adventure->eventleader) {
                $leaders->push($adventure->eventleader);
            }
        }
        if ($leaders->isNotEmpty()) {
            Notification::send($leaders, new BookingCancelled($adventure, $participant));
        }

        $promoted = null;
        if ($wasRegular) {
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
    private function ownsBooking(User $user, Booking $booking): bool
    {
        return $booking->booked_by_user_id === $user->id
            || ($booking->player_id !== null && $user->players()->where('players.id', $booking->player_id)->exists());
    }
}
