<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Hero;
use App\Services\AuditLogger;
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
        // Check-in, Abmelden und EP-Vergabe: Admin/Projektleitung/Bürokrat (ADV-14).
        $this->middleware('can:manage-checkin');
    }

    /**
     * Check-in ist erst ab Status „Anmeldung geschlossen" (≥ 40) erlaubt (ADV-14).
     */
    private function denyIfCheckinClosed(Request $request, Adventure $adventure): RedirectResponse|JsonResponse|null
    {
        if ($adventure->checkinAllowed()) {
            return null;
        }

        $message = 'Check-in ist erst ab Status „Anmeldung geschlossen" möglich.';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('error', $message);
    }

    public function update(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        if ($deny = $this->denyIfCheckinClosed($request, $adventure)) {
            return $deny;
        }

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

        if ($deny = $this->denyIfCheckinClosed($request, $adventure)) {
            return $deny;
        }

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
     * Mehrfach-Abmeldung (Issue #11): mehrere Teilnehmer mit demselben Grund
     * abmelden. Bereits Abgemeldete werden übersprungen.
     */
    public function bulkDeregister(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer'],
            'absence_reason' => ['required', Rule::in(array_keys(Booking::ABSENCE_REASONS))],
        ]);

        // Nur Anmeldungen dieses Events mit Spieler (wie in der Check-in-Liste).
        $bookings = $adventure->bookings()
            ->whereIn('id', $data['booking_ids'])
            ->whereNotNull('player_id')
            ->get();

        if ($bookings->isEmpty()) {
            $msg = 'Keine gültigen Teilnehmer ausgewählt.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $toDeregister = $bookings->where('status', '!=', 'abgemeldet');
        $skipped = $bookings->count() - $toDeregister->count();

        DB::transaction(function () use ($adventure, $toDeregister, $data) {
            foreach ($toDeregister as $booking) {
                $booking->update([
                    'status' => 'abgemeldet',
                    'absence_reason' => $data['absence_reason'],
                    'approved_at' => null,
                ]);
            }
            // Abgemeldete gelten nicht als anwesend.
            $adventure->visits()->whereIn('player_id', $toDeregister->pluck('player_id'))->delete();
        });

        $message = $toDeregister->count().' Teilnehmer abgemeldet ('.Booking::ABSENCE_REASONS[$data['absence_reason']].').';
        if ($skipped > 0) {
            $message .= " {$skipped} bereits abgemeldet (übersprungen).";
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Abmeldung zurücknehmen: Status wieder „bestätigt“, Abmeldegrund entfernen.
     * Der Check-in wird nicht wiederhergestellt (bei Anwesenheit normal einchecken).
     * Keine Mail – das Abmelden ist ein interner Check-in-Vorgang.
     */
    public function undoDeregister(Request $request, Adventure $adventure, Booking $booking): RedirectResponse|JsonResponse
    {
        abort_unless($booking->adventure_id === $adventure->id, 404);

        if ($booking->status !== 'abgemeldet') {
            $msg = 'Diese Anmeldung ist nicht abgemeldet.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $reason = $booking->absence_reason_label;
        $booking->update([
            'status' => 'bestaetigt',
            'absence_reason' => null,
            'approved_at' => now(),
        ]);

        AuditLogger::log('booking.deregistration_revoked', $booking, array_filter([
            'adventure' => $adventure->name,
            'absence_reason' => $reason,
        ]));

        $message = 'Abmeldung von '.$booking->participant_name.' zurückgenommen – Status wieder „bestätigt“.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Vergibt EP an den aktiven Helden aller Anwesenden (BOOK-09):
     * loot_ep_day × Eventtage, Typ 50 „Abenteuer bestritten", mit adventure_id.
     * Idempotent (je Spieler & Abenteuer nur einmal); Anwesende ohne aktiven
     * Helden werden übersprungen.
     */
    public function awardEp(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        if ($deny = $this->denyIfCheckinClosed($request, $adventure)) {
            return $deny;
        }

        $days = $this->eventDays($adventure);
        $ep = $adventure->loot_ep_day * $days;

        $adventure->load('visits.player.activeHero');
        $awarded = 0;
        $skipped = 0;

        DB::transaction(function () use ($adventure, $ep, &$awarded, &$skipped) {
            // Idempotent je Spieler & Abenteuer – auch wenn der aktive Held inzwischen gewechselt wurde.
            $awardedPlayers = $adventure->epAwardedPlayerIds()->flip();

            foreach ($adventure->visits as $visit) {
                if ($awardedPlayers->has($visit->player_id)) {
                    continue;
                }

                $hero = $visit->player?->activeHero;

                if (! $hero) {
                    $skipped++;

                    continue;
                }

                $hero->epTransactions()->create([
                    'adventure_id' => $adventure->id,
                    'ep_transaction_type_id' => Hero::ADVENTURE_EP_TYPE,
                    'ep_count' => $ep,
                    'transacted_at' => $adventure->end_at ?? now(),
                ]);
                $awardedPlayers->put($visit->player_id, true);
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
