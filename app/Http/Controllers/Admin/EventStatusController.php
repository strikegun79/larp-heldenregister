<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der Event-Status-Lookups (ADM-07).
 * Kein Create/Store: IDs sind Systemkonstanten aus dem Legacy-Workflow (TRANSITIONS).
 */
class EventStatusController extends Controller
{
    public function index(): View
    {
        $statuses = EventStatus::withCount('adventures')->orderBy('id')->get();

        return view('admin.event_statuses.index', compact('statuses'));
    }

    public function edit(EventStatus $eventStatus, Request $request): View
    {
        $data = ['status' => $eventStatus];

        return $request->expectsJson()
            ? view('admin.event_statuses._form', $data)
            : view('admin.event_statuses.edit', $data);
    }

    public function update(Request $request, EventStatus $eventStatus): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $eventStatus->update($validated);

        return $this->respond($request, 'Status wurde aktualisiert.');
    }

    public function destroy(Request $request, EventStatus $eventStatus): RedirectResponse|JsonResponse
    {
        if ($eventStatus->adventures()->exists()) {
            $msg = 'Status kann nicht gelöscht werden – er ist noch Veranstaltungen zugeordnet.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : redirect()->route('admin.event-statuses.index')->with('error', $msg);
        }

        $eventStatus->delete();

        return $this->respond($request, 'Status wurde gelöscht.');
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.event-statuses.index')->with('status', $message);
    }
}
