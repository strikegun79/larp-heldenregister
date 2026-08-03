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
                'players' => Player::count(),
                'heroes' => Hero::count(),
                'upcoming_events' => Adventure::whereNotNull('start_at')
                    ->where('start_at', '>=', now())
                    ->where('event_status_id', '!=', EventStatus::CANCELLED)
                    ->count(),
                'open_bookings' => Booking::where('status', 'offen')->count(),
            ];
        }

        $nextAdventure = Adventure::whereNotNull('start_at')
            ->where('start_at', '>=', now())
            ->where('event_status_id', '!=', EventStatus::CANCELLED)
            ->orderBy('start_at')
            ->with('location')
            ->first();

        $activePlayer = $request->user()
            ->players()
            ->withPivot('self')
            ->whereNotNull('active_hero_id')
            ->with('activeHero.classes')
            ->first();

        $activeHero = $activePlayer?->activeHero;
        $activeHeroIsOwn = (bool) ($activePlayer?->pivot?->self);

        $alreadyBooked = false;
        if ($nextAdventure) {
            $playerIds = $request->user()->players()->pluck('players.id');
            $alreadyBooked = $nextAdventure->bookings()
                ->whereIn('player_id', $playerIds)
                ->exists();
        }

        $hasPlayers = $request->user()->players()->exists();

        return view('dashboard', compact('metrics', 'nextAdventure', 'activeHero', 'activePlayer', 'activeHeroIsOwn', 'alreadyBooked', 'hasPlayers'));
    }
}
