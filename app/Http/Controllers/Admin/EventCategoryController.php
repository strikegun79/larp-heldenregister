<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der Event-Kategorien (ADV-09). Soft-Delete: gelöschte Kategorien
 * verschwinden aus der Auswahl, bleiben aber referenzierbar (bestehende Events).
 */
class EventCategoryController extends Controller
{
    public function index(): View
    {
        $categories = EventCategory::withCount('adventures')->orderBy('name')->get();

        return view('admin.event_categories.index', compact('categories'));
    }

    public function create(Request $request): View
    {
        $data = ['category' => new EventCategory];

        return $request->expectsJson()
            ? view('admin.event_categories._form', $data)
            : view('admin.event_categories.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateCategory($request);
        $data['id'] = (int) EventCategory::withTrashed()->max('id') + 1;

        EventCategory::create($data);

        return $this->respond($request, 'Kategorie wurde angelegt.');
    }

    public function edit(EventCategory $eventCategory, Request $request): View
    {
        $data = ['category' => $eventCategory];

        return $request->expectsJson()
            ? view('admin.event_categories._form', $data)
            : view('admin.event_categories.edit', $data);
    }

    public function update(Request $request, EventCategory $eventCategory): RedirectResponse|JsonResponse
    {
        $eventCategory->update($this->validateCategory($request));

        return $this->respond($request, 'Kategorie wurde aktualisiert.');
    }

    public function destroy(Request $request, EventCategory $eventCategory): RedirectResponse|JsonResponse
    {
        $eventCategory->delete(); // Soft-Delete

        return $this->respond($request, 'Kategorie wurde gelöscht.');
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.event-categories.index')->with('status', $message);
    }
}
