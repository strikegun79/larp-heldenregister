<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Buchen: adventure.book; Stornieren/Abmelden: adventure.cancel.
        $this->middleware('can:adventure.book')->only(['create', 'store']);
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
        $players = Gate::allows('book-any-player')
            ? Player::orderBy('name')->get()
            : $request->user()->players()->orderBy('name')->get();

        return view('bookings._create', [
            'adventure' => $adventure,
            'players' => $players,
            'roles' => EventRole::orderBy('id')->get(),
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

        $adventure->bookings()->create([
            'player_id' => $data['player_id'],
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
            // Volles Event -> automatisch auf die Warteliste.
            'waitlisted' => $adventure->isFull(),
        ]);

        $message = $adventure->isFull()
            ? 'Anmeldung erfolgt – das Abenteuer ist voll, daher auf der Warteliste.'
            : 'Anmeldung gespeichert.';

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

        // Wird ein regulärer Platz frei, rückt die älteste Wartelisten-Buchung nach (BOOK-07).
        $wasRegular = ! $booking->waitlisted;
        $booking->delete();

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
}
