<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\EventCategory;
use App\Models\EventClient;
use App\Models\EventRole;
use App\Models\EventStatus;
use App\Models\Location;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdventureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Abenteuer ansehen: events.view ODER adventure.book (siehe adventure.access).
        $this->middleware('can:adventure.access')->only(['index', 'show']);
        // Events anlegen/bearbeiten: events.edit (Admin, Bürokrat, Projektleitung).
        $this->middleware('can:events.edit')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Liste aller Abenteuer.
     */
    public function index(): View
    {
        $adventures = Adventure::with(['location', 'status', 'category'])
            ->withCount('confirmedBookings')
            ->orderByDesc('start_at')
            ->paginate(20);

        return view('adventures.index', compact('adventures'));
    }

    public function create(): View
    {
        return view('adventures.create', $this->formData(new Adventure([
            'event_status_id' => 20,
            'event_client_id' => 1,
            'event_category_id' => 0,
            'max_player' => 10,
            'fee' => 12,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $adventure = Adventure::create($this->validateAdventure($request));

        return redirect()
            ->route('adventures.show', $adventure)
            ->with('status', 'Abenteuer wurde angelegt.');
    }

    public function show(Adventure $adventure): View
    {
        $adventure->load(['location', 'status', 'category', 'client', 'bookings.player', 'bookings.role']);

        $data = [
            'adventure' => $adventure,
            'players' => Player::orderBy('name')->get(),
            'roles' => EventRole::orderBy('id')->get(),
        ];

        if (request()->ajax()) {
            return view('adventures._detail', $data);
        }

        return view('adventures.show', $data);
    }

    public function edit(Adventure $adventure): View
    {
        $data = $this->formData($adventure);

        if (request()->ajax()) {
            return view('adventures._edit_modal', $data);
        }

        return view('adventures.edit', $data);
    }

    public function update(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $adventure->update($this->validateAdventure($request));

        $message = 'Abenteuer wurde aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('adventures.show', $adventure)->with('status', $message);
    }

    public function destroy(Adventure $adventure): RedirectResponse
    {
        $adventure->delete();

        return redirect()
            ->route('adventures.index')
            ->with('status', 'Abenteuer wurde gelöscht.');
    }

    /**
     * Gemeinsame Auswahllisten für die Formulare.
     *
     * @return array<string, mixed>
     */
    private function formData(Adventure $adventure): array
    {
        return [
            'adventure' => $adventure,
            'locations' => Location::orderBy('titel')->get(),
            'statuses' => EventStatus::orderBy('id')->get(),
            'categories' => EventCategory::orderBy('name')->get(),
            'clients' => EventClient::orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAdventure(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'loot_ep_day' => ['integer', 'min:0'],
            'event_status_id' => ['required', 'exists:event_statuses,id'],
            'event_client_id' => ['required', 'exists:event_clients,id'],
            'event_category_id' => ['required', 'exists:event_categories,id'],
            'max_player' => ['required', 'integer', 'min:1'],
            'waitlist' => ['integer', 'min:0'],
            'fee' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
