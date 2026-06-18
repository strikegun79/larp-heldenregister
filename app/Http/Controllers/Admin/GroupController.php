<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gruppen-CRUD (GRP-02): Anlegen/Bearbeiten/Löschen von LARP-Gruppen.
 * Berechtigung: groups.manage (Admin, Bürokrat, Spielleiter).
 */
class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'can:groups.manage']);
    }

    public function index(): View
    {
        $groups = Group::withCount('heroes')->orderBy('name')->get();

        return view('admin.groups.index', compact('groups'));
    }

    public function create(Request $request): View
    {
        $data = ['group' => new Group];

        return $request->expectsJson()
            ? view('admin.groups._form', $data)
            : view('admin.groups.edit', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        Group::create($this->validated($request));

        return $this->respond($request, 'Gruppe wurde angelegt.');
    }

    public function edit(Group $group, Request $request): View
    {
        $data = ['group' => $group];

        return $request->expectsJson()
            ? view('admin.groups._form', $data)
            : view('admin.groups.edit', $data);
    }

    public function update(Request $request, Group $group): RedirectResponse|JsonResponse
    {
        $group->update($this->validated($request));

        return $this->respond($request, 'Gruppe wurde aktualisiert.');
    }

    public function destroy(Request $request, Group $group): RedirectResponse|JsonResponse
    {
        $count = $group->heroes()->count();
        $group->delete();

        $suffix = $count ? " ({$count} Held(en) ausgetreten)" : '';

        return $this->respond($request, "Gruppe gelöscht.{$suffix}");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.groups.index')->with('status', $message);
    }
}
