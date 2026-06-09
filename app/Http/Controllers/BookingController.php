<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Buchen: adventure.book; Stornieren/Abmelden: adventure.cancel.
        $this->middleware('can:adventure.book')->only('store');
        $this->middleware('can:adventure.cancel')->only('destroy');
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
     * Eine Anmeldung stornieren.
     */
    public function destroy(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->delete();

        $message = 'Anmeldung wurde storniert.';

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
