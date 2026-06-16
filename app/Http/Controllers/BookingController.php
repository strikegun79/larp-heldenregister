<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\Player;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingReceived;
use App\Notifications\WaitlistPromoted;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

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
        // Anmeldung bestätigen/freigeben (BOOK-05) bzw. ablehnen (ADV-18).
        $this->middleware('can:approve-bookings')->only(['approve', 'reject']);
        // Bezahlt-Status pflegen (BOOK-06).
        $this->middleware('can:manage-payments')->only('togglePaid');
    }

    /**
     * Anmeldeformular als Modal-Unteransicht (ADV-15). Spielerliste auf
     * eigene/betreute begrenzt (BOOK-10).
     */
    public function create(Request $request, Adventure $adventure): View
    {
        // Bereits angemeldete Spieler nicht mehr zur Auswahl anbieten (ADV-13).
        $bookedPlayerIds = $adventure->bookings()->pluck('player_id');

        $players = Gate::allows('book-any-player')
            ? Player::whereNotIn('id', $bookedPlayerIds)->orderBy('name')->get()
            : $request->user()->players()->whereNotIn('players.id', $bookedPlayerIds)->orderBy('name')->get();

        return view('bookings._create', [
            'adventure' => $adventure,
            'players' => $players,
            'roles' => EventRole::orderBy('id')->get(),
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
            'event_role_id' => ['required', 'exists:event_roles,id'],
            'agb' => ['accepted'],
            'fotoerlaubnis' => ['boolean'],
            'vegetarier' => ['boolean'],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'nsc' => ['boolean'],
            'allergien' => ['nullable', 'string'],
            'medikamente' => ['nullable', 'string'],
            'erreichbarkeit' => ['nullable', 'string'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
        ]);

        // Ohne book-any-player nur eigene/betreute Spieler buchen (BOOK-10).
        if (! Gate::allows('book-any-player')
            && ! $request->user()->players()->where('players.id', $data['player_id'])->exists()) {
            return $this->fail($request, 'Für diesen Spieler darfst du keine Buchung anlegen.');
        }

        if (! $adventure->registrationOpen()) {
            return $this->fail($request, 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
        }

        if ($adventure->bookings()->where('player_id', $data['player_id'])->exists()) {
            return $this->fail($request, 'Dieser Spieler ist bereits angemeldet.');
        }

        // Der teilnehmende Held ist automatisch der aktive Held des Spielers (HERO-21).
        $player = Player::find($data['player_id']);

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
            'erreichbarkeit' => $data['erreichbarkeit'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'],
            // Volles Event -> automatisch auf die Warteliste.
            'waitlisted' => $adventure->isFull(),
        ]);

        // NOTI-02: Bestätigung an den Spieler (sofern E-Mail hinterlegt).
        if ($player?->email) {
            Notification::route('mail', $player->email)->notify(new BookingReceived($booking));
        }

        $message = $adventure->isFull()
            ? 'Anmeldung erfolgt – das Abenteuer ist voll, daher auf der Warteliste.'
            : 'Anmeldung gespeichert.';

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
            'roles' => EventRole::orderBy('id')->get(),
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
            'event_role_id' => ['required', 'exists:event_roles,id'],
            'agb' => ['accepted'],
            'fotoerlaubnis' => ['boolean'],
            'vegetarier' => ['boolean'],
            'allergien' => ['nullable', 'string'],
            'erreichbarkeit' => ['nullable', 'string'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
        ]);

        if (! $adventure->registrationOpen()) {
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
            'waitlisted' => $adventure->isFull(),
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

        $booking->load('player');

        return view('bookings._edit', [
            'adventure' => $adventure,
            'booking' => $booking,
            'roles' => EventRole::orderBy('id')->get(),
        ]);
    }

    /**
     * Anmeldedetails (Rolle, Flags, Allergien, …) nachträglich ändern (BOOK-04).
     */
    public function update(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $data = $request->validate([
            'event_role_id' => ['required', 'exists:event_roles,id'],
            'fotoerlaubnis' => ['boolean'],
            'vegetarier' => ['boolean'],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'nsc' => ['boolean'],
            'allergien' => ['nullable', 'string'],
            'medikamente' => ['nullable', 'string'],
            'erreichbarkeit' => ['nullable', 'string'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
        ]);

        $booking->update([
            'event_role_id' => $data['event_role_id'],
            'fotoerlaubnis' => $request->boolean('fotoerlaubnis'),
            'vegetarier' => $request->boolean('vegetarier'),
            'leih_tunika' => $request->boolean('leih_tunika'),
            'leih_waffe' => $request->boolean('leih_waffe'),
            'nsc' => $request->boolean('nsc'),
            'allergien' => $data['allergien'] ?? null,
            'medikamente' => $data['medikamente'] ?? null,
            'erreichbarkeit' => $data['erreichbarkeit'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'],
        ]);

        $message = 'Anmeldung aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Anmeldung bestätigen bzw. Bestätigung zurücknehmen (BOOK-05).
     * Setzt/leert `approved_at` (Toggle).
     */
    public function approve(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        // Toggle bestätigt/offen; Status (ADV-18) und approved_at synchron halten.
        $confirm = ! $booking->approved_at;
        $booking->update([
            'approved_at' => $confirm ? now() : null,
            'status' => $confirm ? 'bestaetigt' : 'offen',
        ]);

        $message = $confirm ? 'Anmeldung bestätigt.' : 'Bestätigung zurückgenommen.';

        // NOTI-02: optionaler Versand einer Bestätigungs-Mail an den Spieler.

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

        $message = $reject ? 'Anmeldung abgelehnt.' : 'Ablehnung zurückgenommen.';

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

        $booking->update(['paid' => ! $booking->paid]);

        $message = $booking->paid ? 'Als bezahlt markiert.' : 'Als offen markiert.';

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

        // Wird ein regulärer Platz frei, rückt die älteste Wartelisten-Buchung nach (BOOK-07).
        $wasRegular = ! $booking->waitlisted;
        $booking->delete();

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
                // NOTI-03: Benachrichtigung an den nachgerückten Spieler.
                if ($promoted->player?->email) {
                    Notification::route('mail', $promoted->player->email)->notify(new WaitlistPromoted($promoted));
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
