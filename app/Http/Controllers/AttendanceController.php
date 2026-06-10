<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Hero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Teilnahme-Erfassung („Check-in") je Abenteuer (BOOK-08): hakt aus den
 * Anmeldungen die tatsächlich Anwesenden ab und pflegt `event_visits`.
 */
class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Bulk-Check-in + EP-Vergabe: Spielleiter/Teamer (BOOK-08/09).
        $this->middleware('can:manage-attendance')->only(['update', 'awardEp']);
        // Einzel-Check-in/Abmelden (ADV-18/19): zusätzlich Projektleitung/Bürokrat.
        $this->middleware('can:manage-checkin')->only(['toggle', 'deregister']);
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

    /**
     * Check-in eines einzelnen Teilnehmers umschalten (ADV-18): legt einen
     * `event_visit` an bzw. entfernt ihn.
     */
    public function toggle(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $visit = $adventure->visits()->where('player_id', $booking->player_id)->first();

        if ($visit) {
            $visit->delete();
            $message = 'Check-in entfernt.';
        } else {
            $adventure->visits()->create(['player_id' => $booking->player_id]);
            $message = 'Eingecheckt.';
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Teilnehmer abmelden (ADV-18): Status „abgemeldet" mit Grund (krank,
     * nicht erschienen, unentschuldigt); etwaiger Check-in wird entfernt.
     */
    public function deregister(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        $data = $request->validate([
            'absence_reason' => ['required', Rule::in(array_keys(Booking::ABSENCE_REASONS))],
        ]);

        DB::transaction(function () use ($adventure, $booking, $data) {
            $booking->update([
                'status' => 'abgemeldet',
                'absence_reason' => $data['absence_reason'],
                'approved_at' => null,
            ]);
            // Abgemeldete gelten nicht als anwesend.
            $adventure->visits()->where('player_id', $booking->player_id)->delete();
        });

        $message = 'Teilnehmer abgemeldet ('.$booking->absence_reason_label.').';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Vergibt EP an den aktiven Helden aller Anwesenden (BOOK-09):
     * loot_ep_day × Eventtage, Typ 50 „Abenteuer bestritten", mit adventure_id.
     * Idempotent (je Held & Abenteuer nur einmal); Anwesende ohne aktiven
     * Helden werden übersprungen.
     */
    public function awardEp(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $days = $this->eventDays($adventure);
        $ep = $adventure->loot_ep_day * $days;

        $adventure->load('visits.player.activeHero');
        $awarded = 0;
        $skipped = 0;

        DB::transaction(function () use ($adventure, $ep, &$awarded, &$skipped) {
            foreach ($adventure->visits as $visit) {
                $hero = $visit->player?->activeHero;

                if (! $hero) {
                    $skipped++;

                    continue;
                }

                // Idempotent: je Held & Abenteuer höchstens eine Vergabe.
                $alreadyAwarded = $hero->epTransactions()
                    ->where('ep_transaction_type_id', Hero::ADVENTURE_EP_TYPE)
                    ->where('adventure_id', $adventure->id)
                    ->exists();

                if ($alreadyAwarded) {
                    continue;
                }

                $hero->epTransactions()->create([
                    'adventure_id' => $adventure->id,
                    'ep_transaction_type_id' => Hero::ADVENTURE_EP_TYPE,
                    'ep_count' => $ep,
                    'transacted_at' => $adventure->end_at ?? now(),
                ]);
                $awarded++;
            }
        });

        $message = "{$awarded} Held(en) je {$ep} EP gutgeschrieben.";
        if ($skipped > 0) {
            $message .= " {$skipped} ohne aktiven Helden übersprungen.";
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /** Anzahl Eventtage (mind. 1; inklusive Start- und Endtag). */
    private function eventDays(Adventure $adventure): int
    {
        if (! $adventure->start_at || ! $adventure->end_at) {
            return 1;
        }

        return max(1, $adventure->start_at->startOfDay()->diffInDays($adventure->end_at->startOfDay()) + 1);
    }
}
