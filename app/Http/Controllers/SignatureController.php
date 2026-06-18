<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Unterschrift einer Anmeldung bei Teilnahme (ADV-17). Erfassung per
 * Tablet/Stift auf einem Canvas; gespeichert als base64-PNG.
 * Berechtigt: Projektleitung, Bürokrat, Admin (Gate take-signatures).
 */
class SignatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:take-signatures');
    }

    /**
     * Unterschriften-Pad als Modal-Unteransicht.
     */
    public function edit(Adventure $adventure, Booking $booking): View
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->load('player');

        return view('bookings._signature', compact('adventure', 'booking'));
    }

    /**
     * Unterschrift speichern (base64-PNG aus dem Canvas).
     */
    public function update(Request $request, Adventure $adventure, Booking $booking): JsonResponse|RedirectResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        // Unterschrift bestätigt den Check-in – erst ab Status ≥ 40 (ADV-14).
        if (! $adventure->checkinAllowed()) {
            $message = 'Check-in ist erst ab Status „Anmeldung geschlossen" möglich.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $data = $request->validate([
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:2000000'],
        ]);

        // Unterschrift = Check-in-Bestätigung (ADV-19): zugleich anwesend setzen.
        DB::transaction(function () use ($adventure, $booking, $data) {
            $booking->update(['signature' => $data['signature']]);
            if (! $adventure->visits()->where('player_id', $booking->player_id)->exists()) {
                $adventure->visits()->create(['player_id' => $booking->player_id]);
            }
        });

        $message = 'Unterschrift gespeichert, Check-in bestätigt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Unterschrift entfernen.
     */
    public function destroy(Request $request, Adventure $adventure, Booking $booking): JsonResponse|RedirectResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->update(['signature' => null]);

        $message = 'Unterschrift entfernt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }
}
