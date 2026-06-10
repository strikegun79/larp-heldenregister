<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teilnahme-Erfassung („Check-in") je Abenteuer (BOOK-08): hakt aus den
 * Anmeldungen die tatsächlich Anwesenden ab und pflegt `event_visits`.
 */
class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-attendance');
    }

    public function update(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $request->validate([
            'present' => ['array'],
            'present.*' => ['integer'],
        ]);

        // Nur tatsächlich gebuchte Spieler dürfen als anwesend gelten.
        $bookedPlayerIds = $adventure->bookings()->pluck('player_id');
        $present = collect($request->input('present', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $bookedPlayerIds->contains($id))
            ->unique()
            ->values();

        DB::transaction(function () use ($adventure, $present) {
            // Nicht (mehr) anwesende entfernen.
            $adventure->visits()->whereNotIn('player_id', $present)->delete();
            // Neue Anwesende anlegen (idempotent).
            $existing = $adventure->visits()->pluck('player_id');
            foreach ($present as $playerId) {
                if (! $existing->contains($playerId)) {
                    $adventure->visits()->create(['player_id' => $playerId]);
                }
            }
        });

        $message = 'Teilnahme gespeichert ('.$present->count().' anwesend).';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }
}
