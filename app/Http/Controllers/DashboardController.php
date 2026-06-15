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

        return view('dashboard', compact('metrics'));
    }
}
