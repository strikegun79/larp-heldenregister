<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Die Spieler des angemeldeten Benutzers (Legacy: „Deine Spieler").
     */
    public function index(Request $request): View
    {
        $players = $request->user()->players()
            ->with(['heroes.classes', 'activeHero'])
            ->orderBy('name')
            ->get();

        return view('players.index', compact('players'));
    }

    public function create(): View
    {
        return view('players.create', ['player' => new Player]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePlayer($request);

        $player = Player::create($data);
        // Spieler dem Benutzer zuordnen (Legacy: user2player, self-Flag).
        $request->user()->players()->attach($player->id, [
            'self' => $request->boolean('self'),
        ]);

        return redirect()
            ->route('players.show', $player)
            ->with('status', 'Spieler wurde angelegt.');
    }

    public function show(Player $player, Request $request): View
    {
        $this->authorize('view', $player);
        $player->load(['heroes.classes', 'heroes.epTransactions.type']);

        if ($request->ajax()) {
            return view('players._detail', compact('player'));
        }

        return view('players.show', compact('player'));
    }

    public function edit(Player $player): View
    {
        $this->authorize('update', $player);

        return view('players.edit', [
            'player' => $player,
            'self' => $player->users()->wherePivot('user_id', auth()->id())->wherePivot('self', true)->exists(),
        ]);
    }

    public function update(Request $request, Player $player): RedirectResponse
    {
        $this->authorize('update', $player);

        $player->update($this->validatePlayer($request));
        $player->users()->updateExistingPivot($request->user()->id, [
            'self' => $request->boolean('self'),
        ]);

        return redirect()
            ->route('players.show', $player)
            ->with('status', 'Spieler wurde aktualisiert.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);
        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('status', 'Spieler wurde gelöscht.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlayer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'dayofbirth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:weiblich,männlich,divers'],
        ]);
    }
}
