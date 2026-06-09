<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\View\View;

/**
 * Admin-Übersicht ALLER Spieler/Teilnehmer (Legacy: pages/admin/players.php),
 * im Gegensatz zu „Deine Spieler" (nur die eigenen).
 */
class PlayerController extends Controller
{
    public function index(): View
    {
        $players = Player::withTrashed()
            ->withCount('heroes')
            ->with('users')
            ->orderBy('name')
            ->paginate(30);

        return view('admin.players.index', compact('players'));
    }
}
