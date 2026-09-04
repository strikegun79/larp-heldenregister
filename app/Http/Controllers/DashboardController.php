<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Hero;
use App\Models\Player;
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
                    ->with('player')])
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

        $activePlayers = $request->user()
            ->players()
            ->withPivot('self')
            ->whereNotNull('active_hero_id')
            ->with('activeHero.classes')
            ->get();

        $hasPlayers = $request->user()->players()->exists();

        $profileComplete = $request->user()->hasCompleteAddress();

        $hasBookings = $playerIds->isNotEmpty() && Booking::whereIn('player_id', $playerIds)->exists();

        $showOnboarding = ! $profileComplete || ! $hasPlayers || ! $hasBookings;

        return view('dashboard', compact('metrics', 'nextAdventure', 'upcomingBookedAdventures', 'activePlayers', 'hasPlayers', 'profileComplete', 'hasBookings', 'showOnboarding'));
    }
}
