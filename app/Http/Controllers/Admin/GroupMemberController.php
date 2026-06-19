<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Hero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gruppenmitglieder verwalten (GRP-03): Helden einer Gruppe zuordnen / entfernen.
 * Berechtigung: groups.manage (Admin, Bürokrat, Spielleiter).
 */
class GroupMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'can:groups.manage']);
    }

    public function index(Group $group): View
    {
        $group->load('heroes');

        $memberIds = $group->heroes->pluck('id');
        $available = Hero::orderBy('character_name')
            ->whereNotIn('id', $memberIds)
            ->get(['id', 'character_name']);

        return view('admin.groups._members', compact('group', 'available'));
    }

    public function store(Request $request, Group $group): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'hero_id' => ['required', 'exists:heroes,id'],
            'role'    => ['nullable', 'string', 'in:Anführer,Mitglied'],
        ]);

        if ($group->heroes()->whereKey($data['hero_id'])->exists()) {
            return $this->fail($request, 'Held ist bereits Mitglied dieser Gruppe.');
        }

        $group->heroes()->attach($data['hero_id'], [
            'role'      => $data['role'] ?? null,
            'joined_at' => now(),
        ]);

        return $this->respond($request, 'Held wurde hinzugefügt.');
    }

    public function destroy(Request $request, Group $group, Hero $hero): RedirectResponse|JsonResponse
    {
        if (! $group->heroes()->whereKey($hero->id)->exists()) {
            return $this->fail($request, 'Held ist kein Mitglied dieser Gruppe.');
        }

        $group->heroes()->detach($hero->id);

        return $this->respond($request, 'Held wurde entfernt.');
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : redirect()->route('admin.groups.index')->with('status', $message);
    }

    private function fail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->withErrors(['hero_id' => $message]);
    }
}
