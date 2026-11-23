<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlotCharacter;
use App\Services\AuditLogger;
use App\Support\GalleryImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Issue #30: Verwaltung der bekannten Plot-Charaktere (Plot-Schreiber,
 * Projektleitung, Bürokrat, Admin). Sichtbare Plot-Charaktere erscheinen
 * im öffentlichen Heldenverzeichnis (/h).
 */
class PlotCharacterController extends Controller
{
    /**
     * Liste aller Plot-Charaktere mit optionaler Namenssuche.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $characters = PlotCharacter::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%');
            }))
            ->orderBy('name')
            ->get();

        return view('admin.plot_characters.index', compact('characters', 'search'));
    }

    /**
     * Formular für einen neuen Plot-Charakter (Inspektor/Modal oder Vollseite).
     */
    public function create(Request $request): View
    {
        $data = ['character' => new PlotCharacter(['public_visible' => true])];

        return $request->expectsJson()
            ? view('admin.plot_characters._form', $data)
            : view('admin.plot_characters.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateCharacter($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = GalleryImageStorage::store($request->file('image'), 'plot-characters');
        }

        $character = PlotCharacter::create($data);
        AuditLogger::log('plot_character.created', $character);

        return $this->respond($request, 'Plot-Charakter „'.$character->name.'“ wurde angelegt.');
    }

    public function edit(PlotCharacter $plotCharacter, Request $request): View
    {
        $data = ['character' => $plotCharacter];

        return $request->expectsJson()
            ? view('admin.plot_characters._form', $data)
            : view('admin.plot_characters.edit', $data);
    }

    public function update(Request $request, PlotCharacter $plotCharacter): RedirectResponse|JsonResponse
    {
        $data = $this->validateCharacter($request);

        // Neues Bild ersetzt das alte; „Bild entfernen“ löscht es ersatzlos.
        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $this->deleteImage($plotCharacter);
            $data['image_path'] = $request->hasFile('image')
                ? GalleryImageStorage::store($request->file('image'), 'plot-characters')
                : null;
        }

        $plotCharacter->update($data);
        AuditLogger::log('plot_character.updated', $plotCharacter, $plotCharacter->getChanges() ?: null);

        return $this->respond($request, 'Plot-Charakter „'.$plotCharacter->name.'“ wurde aktualisiert.');
    }

    public function destroy(Request $request, PlotCharacter $plotCharacter): RedirectResponse|JsonResponse
    {
        AuditLogger::log('plot_character.deleted', $plotCharacter);
        $this->deleteImage($plotCharacter);
        $plotCharacter->delete();

        return $this->respond($request, 'Plot-Charakter „'.$plotCharacter->name.'“ wurde gelöscht.');
    }

    /**
     * Gemeinsame Validierung (Bild separat, da Datei-Upload).
     */
    private function validateCharacter(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'public_visible' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['boolean'],
        ]);

        $data['public_visible'] = $request->boolean('public_visible');
        unset($data['image'], $data['remove_image']);

        return $data;
    }

    private function deleteImage(PlotCharacter $character): void
    {
        if ($character->image_path) {
            Storage::disk('public')->delete($character->image_path);
        }
    }

    /**
     * AJAX -> JSON mit Seitenreload, sonst Redirect mit Flash.
     */
    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.plot-characters.index')->with('status', $message);
    }
}
