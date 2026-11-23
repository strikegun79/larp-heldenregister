<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Hero;
use App\Models\Motd;
use App\Models\NewsletterSubscription;
use App\Models\Player;
use App\Models\TeamerSignup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Startseite. Für Admins zusätzlich Kennzahl-Karten (REP-06).
     * Für alle Nutzer: nächstes Abenteuer + aktiver Held (UI-43).
     */
    public function index(Request $request): View
    {
        $metrics = null;

        if ($request->user()->isAdmin()) {
            $metrics = [
                'players' => Player::where('active', true)->count(),
                'heroes' => Hero::whereNull('died')->count(),
                'upcoming_events' => Adventure::whereNotNull('start_at')
                    ->where('start_at', '>=', now())
                    ->where('event_status_id', '!=', EventStatus::CANCELLED)
                    ->count(),
                'open_bookings' => Booking::where('status', 'offen')->count(),
            ];
        }

        $playerIds = $request->user()->players()->pluck('players.id');

        // Bevorstehende Abenteuer, bei denen der User mindestens einen Spieler angemeldet hat
        $upcomingBookedAdventures = $playerIds->isNotEmpty()
            ? Adventure::whereNotNull('start_at')
                ->where('start_at', '>=', now())
                ->where('event_status_id', '!=', EventStatus::CANCELLED)
                ->whereHas('bookings', fn ($q) => $q->whereIn('player_id', $playerIds)
                    ->whereIn('status', ['bestaetigt', 'offen']))
                ->with(['location', 'bookings' => fn ($q) => $q->whereIn('player_id', $playerIds)
                    ->whereIn('status', ['bestaetigt', 'offen'])
                    ->with(['player', 'role'])])
                ->orderBy('start_at')
                ->get()
            : collect();

        // Fallback: nächstes offenes Abenteuer (wenn keine eigenen Buchungen vorhanden)
        $nextAdventure = $upcomingBookedAdventures->isEmpty()
            ? Adventure::whereNotNull('start_at')
                ->where('start_at', '>=', now())
                ->where('event_status_id', '!=', EventStatus::CANCELLED)
                ->orderBy('start_at')
                ->with('location')
                ->first()
            : null;

        // Plätze und Anmeldestatus für das Segment „Nächstes Abenteuer“ (einmal berechnet für mobil + Desktop).
        $nextAdventureSlots = $nextAdventure ? [
            'free' => $nextAdventure->freeSlots(),
            'max' => (int) $nextAdventure->max_player,
            'state' => $nextAdventure->registrationState(),
        ] : null;

        $activePlayers = $request->user()
            ->players()
            ->withPivot('self')
            ->whereNotNull('active_hero_id')
            // Teilnahmen des Spielers für den Fortschritts-Chip „X Abenteuer erlebt“.
            ->withCount('visits')
            // EP-Buchungen für ep_balance mitladen (sonst Lazy-Loading-Fehler);
            // Anzahl Fertigkeiten für den Fortschritts-Chip.
            ->with(['activeHero' => fn ($q) => $q->withCount('skills')->with(['classes', 'epTransactions'])])
            ->get();

        // Eigene Spieler, die noch gar keinen Helden haben (Platzhalter „Held wartet noch“).
        $playersAwaitingHero = $request->user()
            ->players()
            ->withPivot('self')
            ->whereNull('active_hero_id')
            ->whereDoesntHave('heroes')
            ->orderBy('players.name')
            ->get();

        // Issue #19: abrechenbare Teamer-Anmeldungen (bestätigt, Pauschale festgelegt).
        // Das Portal kennt den Abrechnungsstand nicht – daher nur Abenteuer der letzten 60 Tage und kommende.
        $teamerCompensations = TeamerSignup::where('user_id', $request->user()->id)
            ->whereNotNull('approved_at')
            ->whereNotNull('event_role_id')
            ->whereHas('adventure', fn ($q) => $q
                ->where('event_status_id', '!=', EventStatus::CANCELLED)
                ->where(fn ($q) => $q->where('end_at', '>=', now()->subDays(60)->startOfDay())
                    ->orWhere(fn ($q) => $q->whereNull('end_at')->where('start_at', '>=', now()->subDays(60)->startOfDay()))))
            ->with(['adventure.location', 'eventRole'])
            ->get()
            ->filter(fn (TeamerSignup $signup) => $signup->compensationBlocker() === null)
            ->sortBy(fn (TeamerSignup $signup) => $signup->adventure?->start_at)
            ->values();

        $hasPlayers = $request->user()->players()->exists();

        $profileComplete = $request->user()->hasCompleteAddress();

        $hasBookings = $playerIds->isNotEmpty() && Booking::whereIn('player_id', $playerIds)->exists();

        $showOnboarding = ! $profileComplete || ! $hasPlayers || ! $hasBookings;

        // Banner ausblenden sobald ein Abo existiert – auch wenn noch nicht bestätigt
        $showNewsletterHint = ! NewsletterSubscription::where('email', $request->user()->email)
            ->whereNull('unsubscribed_at')
            ->exists();

        // Startseiten-Nachricht (nur wenn aktiv und im Zeitraum).
        $motd = Motd::current();

        return view('dashboard', compact('motd', 'metrics', 'nextAdventure', 'nextAdventureSlots', 'upcomingBookedAdventures', 'activePlayers', 'playersAwaitingHero', 'hasPlayers', 'profileComplete', 'hasBookings', 'showOnboarding', 'showNewsletterHint', 'teamerCompensations'));
    }
}
