<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\EventRole;
use App\Models\Group;
use App\Models\Hero;
use App\Models\Player;
use App\Notifications\BookingReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * GRP-06: Gruppen-basierte Event-Buchung.
 * Meldet mehrere Gruppen-Mitglieder gesammelt zu einem Abenteuer an.
 * Kapazität und Warteliste werden je Einzelbuchung geprüft.
 */
class GroupBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:adventure.book');
    }

    /** Gruppen-Anmeldeformular als Modal-Unteransicht. */
    public function create(Request $request, Adventure $adventure): View
    {
        $bookedPlayerIds = $adventure->bookings()->pluck('player_id')->toArray();

        if (Gate::allows('book-any-player')) {
            // Admins sehen alle Gruppen mit noch nicht angemeldeten Mitgliedern.
            $groups = Group::with(['heroes' => function ($q) use ($bookedPlayerIds) {
                $q->whereNotIn('player_id', $bookedPlayerIds)
                    ->whereNotNull('player_id')
                    ->with('player');
            }])
                ->whereHas('heroes', fn ($q) => $q->whereNotIn('player_id', $bookedPlayerIds)->whereNotNull('player_id'))
                ->orderBy('name')
                ->get()
                ->filter(fn ($g) => $g->heroes->isNotEmpty())
                ->values();
        } else {
            // Normale Nutzer sehen nur Gruppen, in denen sie selbst Mitglied sind.
            // Buchbar sind nur eigene Spieler (BOOK-10).
            $userPlayerIds = $request->user()->players()->pluck('players.id');
            $userHeroIds = Hero::whereIn('player_id', $userPlayerIds)->pluck('id');

            $groups = Group::whereHas('heroes', fn ($q) => $q->whereIn('heroes.id', $userHeroIds))
                ->with(['heroes' => function ($q) use ($userPlayerIds, $bookedPlayerIds) {
                    $q->whereIn('player_id', $userPlayerIds)
                        ->whereNotIn('player_id', $bookedPlayerIds)
                        ->with('player');
                }])
                ->orderBy('name')
                ->get()
                ->filter(fn ($g) => $g->heroes->isNotEmpty())
                ->values();
        }

        return view('bookings._create_group', [
            'adventure' => $adventure,
            'groups' => $groups,
            'roles' => EventRole::whereNotIn('id', EventRole::TEAMER_ROLE_IDS)->orderBy('id')->get(),
            'userPhone' => $request->user()->phone,
        ]);
    }

    /** Erstellt Einzelbuchungen für alle ausgewählten Gruppen-Mitglieder. */
    public function store(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'player_ids' => ['required', 'array', 'min:1'],
            'player_ids.*' => ['required', 'integer', 'exists:players,id'],
            'event_role_id' => ['required', 'exists:event_roles,id', 'not_in:'.implode(',', EventRole::TEAMER_ROLE_IDS)],
            'agb' => ['accepted'],
            'kontakt_telefon' => ['required', 'string', 'max:100'],
        ]);

        if (! $adventure->registrationOpen()) {
            return $this->fail($request, 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
        }

        // Vollständige Adresse prüfen (ADV-24 / ORGA-01).
        if (! Gate::allows('book-any-player') && ! $request->user()->hasCompleteAddress()) {
            $missing = $request->user()->missingAddressFields();
            $url = route('profile.edit');
            $msg = 'Deine Kontaktdaten sind unvollständig ('.implode(', ', $missing).'). '
                ."Bitte ergänze sie in deinem <a href=\"{$url}\">Profil</a>.";

            return $this->fail($request, $msg);
        }

        $created = 0;
        $skipped = [];

        foreach ($data['player_ids'] as $playerId) {
            // Berechtigung: nur eigene/betreute Spieler (BOOK-10).
            if (! Gate::allows('book-any-player')
                && ! $request->user()->players()->where('players.id', $playerId)->exists()) {
                $skipped[] = "Spieler #{$playerId}: keine Berechtigung";
                continue;
            }

            // Bereits angemeldet?
            if ($adventure->bookings()->where('player_id', $playerId)->exists()) {
                $player = Player::find($playerId);
                $skipped[] = ($player?->full_name ?? "Spieler #{$playerId}").' ist bereits angemeldet';
                continue;
            }

            $player = Player::find($playerId);

            $booking = $adventure->bookings()->create([
                'player_id' => $playerId,
                'hero_id' => $player?->active_hero_id,
                'booked_by_user_id' => $request->user()->id,
                'event_role_id' => $data['event_role_id'],
                'agb' => true,
                'kontakt_telefon' => $data['kontakt_telefon'],
                // Kapazität nach jeder Buchung neu prüfen (Warteliste greift ab dem Moment, da das Event voll ist).
                'waitlisted' => $adventure->fresh()->isFull(),
            ]);

            if ($player?->email && $player->notificationEnabled('notify_booking_received')) {
                Notification::route('mail', $player->email)->notify(new BookingReceived($booking));
            }

            $created++;
        }

        if ($created === 0) {
            $detail = $skipped ? ' '.implode('; ', $skipped).'.' : '';

            return $this->fail($request, 'Keine Buchungen erstellt.'.$detail);
        }

        $msg = "{$created} Anmeldung(en) gespeichert.";
        if ($skipped) {
            $msg .= ' Übersprungen: '.implode('; ', $skipped).'.';
        }

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    private function fail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('error', $message);
    }
}
