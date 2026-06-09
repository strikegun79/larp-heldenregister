<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Buchen / Buchung ändern / Abmelden: Event buchen (+Admin).
        $this->middleware('can:book-abenteuer');
    }

    /**
     * Einen Spieler zu einem Abenteuer anmelden.
     */
    public function store(Request $request, Adventure $adventure): RedirectResponse
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

        if (! $adventure->registrationOpen()) {
            return back()->with('error', 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
        }

        $alreadyBooked = $adventure->bookings()
            ->where('player_id', $data['player_id'])
            ->exists();

        if ($alreadyBooked) {
            return back()->with('error', 'Dieser Spieler ist bereits angemeldet.');
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

        return back()->with('status', $message);
    }

    /**
     * Eine Anmeldung stornieren.
     */
    public function destroy(Adventure $adventure, Booking $booking): RedirectResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->delete();

        return back()->with('status', 'Anmeldung wurde storniert.');
    }
}
