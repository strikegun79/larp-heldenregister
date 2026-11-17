<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Services\AuditLogger;
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
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:200000'],
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
     * Mehrfach-Check-in (Issue #11): eine Unterschrift (z. B. eines Elternteils)
     * für mehrere Anmeldungen. Bereits Eingecheckte werden übersprungen, damit
     * ihre vorhandene Unterschrift nicht überschrieben wird.
     */
    public function bulkUpdate(Request $request, Adventure $adventure): JsonResponse|RedirectResponse
    {
        if (! $adventure->checkinAllowed()) {
            $message = 'Check-in ist erst ab Status „Anmeldung geschlossen" möglich.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $data = $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer'],
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:200000'],
        ]);

        // Nur Anmeldungen dieses Events mit Spieler (Gäste haben keinen Check-in).
        $bookings = $adventure->bookings()
            ->whereIn('id', $data['booking_ids'])
            ->whereNotNull('player_id')
            ->get();

        if ($bookings->isEmpty()) {
            $message = 'Keine gültigen Teilnehmer ausgewählt.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $checkedIn = 0;
        $skipped = 0;

        DB::transaction(function () use ($adventure, $bookings, $data, &$checkedIn, &$skipped) {
            $present = $adventure->visits()->pluck('player_id')->flip();

            foreach ($bookings as $booking) {
                if ($present->has($booking->player_id)) {
                    $skipped++;

                    continue;
                }

                $booking->update(['signature' => $data['signature']]);
                $adventure->visits()->create(['player_id' => $booking->player_id]);
                $present->put($booking->player_id, true);
                $checkedIn++;
            }
        });

        $message = "{$checkedIn} Teilnehmer eingecheckt.";
        if ($skipped > 0) {
            $message .= " {$skipped} bereits anwesend (übersprungen).";
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Unterschrift entfernen (DSGVO Art. 17 – biometrische Daten auf Anfrage löschen).
     */
    public function destroy(Request $request, Adventure $adventure, Booking $booking): JsonResponse|RedirectResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $booking->update(['signature' => null]);

        AuditLogger::log('booking.signature_deleted', $booking, ['adventure' => $adventure->name]);

        $message = 'Unterschrift entfernt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }
}
