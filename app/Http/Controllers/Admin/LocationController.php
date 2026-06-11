<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der Veranstaltungsorte (ADV-08): Titel, GPS, PLZ, Stadt, Adresse,
 * Bild. Orte sind im Event-Formular wählbar.
 */
class LocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::withCount('adventures')->orderBy('titel')->get();

        return view('admin.locations.index', compact('locations'));
    }

    public function create(Request $request): View
    {
        $data = ['location' => new Location];

        return $request->expectsJson()
            ? view('admin.locations._form', $data)
            : view('admin.locations.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        Location::create($this->validateLocation($request));

        return $this->respond($request, 'Ort wurde angelegt.');
    }

    public function edit(Location $location, Request $request): View
    {
        $data = ['location' => $location];

        return $request->expectsJson()
            ? view('admin.locations._form', $data)
            : view('admin.locations.edit', $data);
    }

    public function update(Request $request, Location $location): RedirectResponse|JsonResponse
    {
        $location->update($this->validateLocation($request));

        return $this->respond($request, 'Ort wurde aktualisiert.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse|JsonResponse
    {
        // Bei Löschung verlieren Events ihren Ort (FK nullOnDelete) – kein Verlust von Events.
        $location->delete();

        return $this->respond($request, 'Ort wurde gelöscht.');
    }

    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'titel' => ['required', 'string', 'max:100'],
            'gps' => ['nullable', 'string', 'max:50'],
            'plz' => ['nullable', 'string', 'max:6'],
            'city' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:150'],
            'image' => ['nullable', 'string', 'max:2000000'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.locations.index')->with('status', $message);
    }
}
