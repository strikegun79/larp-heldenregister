<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der Auftraggeber (ADV-09). Hard-Delete nur, wenn der Auftraggeber
 * von keinem Event referenziert wird (FK RESTRICT).
 */
class EventClientController extends Controller
{
    public function index(): View
    {
        $clients = EventClient::withCount('adventures')->orderBy('name')->get();

        return view('admin.event_clients.index', compact('clients'));
    }

    public function create(Request $request): View
    {
        $data = ['client' => new EventClient];

        return $request->expectsJson()
            ? view('admin.event_clients._form', $data)
            : view('admin.event_clients.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateClient($request);
        $data['id'] = (int) EventClient::max('id') + 1;

        EventClient::create($data);

        return $this->respond($request, 'Auftraggeber wurde angelegt.');
    }

    public function edit(EventClient $eventClient, Request $request): View
    {
        $data = ['client' => $eventClient];

        return $request->expectsJson()
            ? view('admin.event_clients._form', $data)
            : view('admin.event_clients.edit', $data);
    }

    public function update(Request $request, EventClient $eventClient): RedirectResponse|JsonResponse
    {
        $eventClient->update($this->validateClient($request));

        return $this->respond($request, 'Auftraggeber wurde aktualisiert.');
    }

    public function destroy(Request $request, EventClient $eventClient): RedirectResponse|JsonResponse
    {
        if ($eventClient->adventures()->exists()) {
            $message = 'Auftraggeber wird noch von Events verwendet und kann nicht gelöscht werden.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $eventClient->delete();

        return $this->respond($request, 'Auftraggeber wurde gelöscht.');
    }

    private function validateClient(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.event-clients.index')->with('status', $message);
    }
}
