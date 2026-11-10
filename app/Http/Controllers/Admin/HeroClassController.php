<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Verwaltung der Helden-Klassen-Lookup (HERO-05): anlegen, umbenennen,
 * deaktivieren. Deaktivierte Klassen erscheinen nicht in der Helden-Auswahl
 * (siehe HeroController@create/@edit, dort `where('disabled', false)`).
 */
class HeroClassController extends Controller
{
    /**
     * Liste aller Klassen.
     */
    public function index(): View
    {
        $classes = HeroClass::withCount('heroes')->orderBy('name')->get();

        return view('admin.hero_classes.index', compact('classes'));
    }

    /**
     * Formular für eine neue Klasse (Modal).
     */
    public function create(Request $request): View
    {
        $data = ['class' => new HeroClass];

        return $request->expectsJson()
            ? view('admin.hero_classes._form', $data)
            : view('admin.hero_classes.create', $data);
    }

    /**
     * Neue Klasse speichern. Die ID wird (Legacy-konform) fortlaufend vergeben,
     * da `hero_classes.id` nicht auto-inkrementiert.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateClass($request);
        $data['id'] = (int) HeroClass::max('id') + 1;

        HeroClass::create($data);

        return $this->respond($request, 'Klasse "'.$data['name'].'" wurde angelegt.');
    }

    /**
     * Formular zum Bearbeiten einer Klasse (Modal).
     */
    public function edit(HeroClass $heroClass, Request $request): View
    {
        $data = ['class' => $heroClass];

        return $request->expectsJson()
            ? view('admin.hero_classes._form', $data)
            : view('admin.hero_classes.edit', $data);
    }

    /**
     * Klasse umbenennen / deaktivieren.
     */
    public function update(Request $request, HeroClass $heroClass): RedirectResponse|JsonResponse
    {
        $data = $this->validateClass($request, $heroClass);

        $heroClass->update($data);

        return $this->respond($request, 'Klasse "'.$heroClass->name.'" wurde aktualisiert.');
    }

    /**
     * Klassenband-Bild hochladen (162×600 px, PNG/JPG).
     * Separate Mini-Form mit data-refresh-modal (wie Skill-Icon).
     */
    public function storeRibbon(Request $request, HeroClass $heroClass): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ribbon' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
        ]);

        if ($heroClass->ribbon_image) {
            Storage::disk('public')->delete($heroClass->ribbon_image);
        }

        $path = $request->file('ribbon')->store('ribbons', 'public');
        $heroClass->update(['ribbon_image' => $path]);

        return $request->expectsJson()
            ? response()->json(['message' => 'Klassenband-Bild gespeichert.', 'refresh_modal' => true])
            : redirect()->route('admin.hero-classes.edit', $heroClass)->with('status', 'Klassenband-Bild gespeichert.');
    }

    /**
     * Klassenband-Bild löschen.
     */
    public function destroyRibbon(Request $request, HeroClass $heroClass): RedirectResponse|JsonResponse
    {
        if ($heroClass->ribbon_image) {
            Storage::disk('public')->delete($heroClass->ribbon_image);
            $heroClass->update(['ribbon_image' => null]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Klassenband-Bild gelöscht.', 'refresh_modal' => true])
            : redirect()->route('admin.hero-classes.edit', $heroClass)->with('status', 'Klassenband-Bild gelöscht.');
    }

    /**
     * Gemeinsame Validierung; `slug` ist eindeutig (außer der eigenen Zeile).
     */
    private function validateClass(Request $request, ?HeroClass $class = null): array
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50'],
            'slug'         => ['required', 'string', 'max:50', Rule::unique('hero_classes', 'slug')->ignore($class?->id)],
            'disabled'     => ['boolean'],
            'ep_cost'      => ['required', 'integer', 'min:0'],
            'ribbon_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $data['disabled'] = $request->boolean('disabled');

        return $data;
    }

    /**
     * AJAX -> JSON mit Seitenreload, sonst Redirect mit Flash.
     */
    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.hero-classes.index')->with('status', $message);
    }
}
