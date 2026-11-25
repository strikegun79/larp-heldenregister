<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Adventure;
use App\Models\Player;
use App\Models\TalkLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Talk-Protokoll: Anfragen an Nextcloud mit Ergebnis (talk.approve, also Bürokrat und Admin). */
class TalkLogController extends Controller
{
    public function index(Request $request): View
    {
        $errorsOnly = $request->boolean('errors');
        $adventure = $request->filled('adventure') ? Adventure::find($request->integer('adventure')) : null;
        $player = $request->filled('player') ? Player::withTrashed()->find($request->integer('player')) : null;

        $logs = TalkLog::with(['adventure:id,name', 'player:id,name,lastname', 'user:id,name,lastname'])
            ->when($errorsOnly, fn ($q) => $q->where('success', false))
            ->when($adventure, fn ($q) => $q->where('adventure_id', $adventure->id))
            ->when($player, fn ($q) => $q->where('player_id', $player->id))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.talk.logs', compact('logs', 'errorsOnly', 'adventure', 'player'));
    }
}
