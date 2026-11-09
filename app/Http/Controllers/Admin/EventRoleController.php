<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der Teilnahme-Rollen (ADV-10): Spieler, NSC, Teamer A–C …
 * Verwendung im Buchungsformular. Hard-Delete nur ohne referenzierende Buchungen.
 */
class EventRoleController extends Controller
{
    public function index(): View
    {
        $roles = EventRole::withCount('bookings')->orderBy('id')->get();

        return view('admin.event_roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        $data = ['role' => new EventRole];

        return $request->expectsJson()
            ? view('admin.event_roles._form', $data)
            : view('admin.event_roles.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateRole($request);
        $data['id'] = (int) EventRole::max('id') + 1;

        EventRole::create($data);

        return $this->respond($request, 'Rolle wurde angelegt.');
    }

    public function edit(EventRole $eventRole, Request $request): View
    {
        $data = ['role' => $eventRole];

        return $request->expectsJson()
            ? view('admin.event_roles._form', $data)
            : view('admin.event_roles.edit', $data);
    }

    public function update(Request $request, EventRole $eventRole): RedirectResponse|JsonResponse
    {
        $eventRole->update($this->validateRole($request));

        return $this->respond($request, 'Rolle wurde aktualisiert.');
    }

    public function destroy(Request $request, EventRole $eventRole): RedirectResponse|JsonResponse
    {
        if ($eventRole->bookings()->exists()) {
            $message = 'Rolle wird noch von Anmeldungen verwendet und kann nicht gelöscht werden.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $eventRole->delete();

        return $this->respond($request, 'Rolle wurde gelöscht.');
    }

    private function validateRole(Request $request): array
    {
        return $request->validate([
            'description' => ['required', 'string', 'max:50'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.event-roles.index')->with('status', $message);
    }
}
