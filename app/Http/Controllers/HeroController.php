<?php

namespace App\Http\Controllers;

use App\Models\EpTransactionType;
use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HeroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:heldenregister.view')->only(['index', 'show']);
        $this->middleware('can:heldenregister.edit')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Liste aller Helden (Heldenregister).
     */
    public function index(): View
    {
        $heroes = Hero::with(['player', 'classes', 'epTransactions.type'])
            ->orderBy('character_name')
            ->paginate(20);

        return view('heroes.index', compact('heroes'));
    }

    /**
     * Formular für einen neuen Helden.
     */
    public function create(): View
    {
        return view('heroes.create', [
            'hero' => new Hero,
            'players' => Player::orderBy('name')->get(),
            'classes' => HeroClass::where('disabled', false)->orderBy('name')->get(),
        ]);
    }

    /**
     * Neuen Helden speichern.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateHero($request);

        $hero = Hero::create($data);
        $hero->classes()->sync($request->input('classes', []));

        return redirect()
            ->route('heroes.show', $hero)
            ->with('status', 'Held wurde angelegt.');
    }

    /**
     * Detailansicht eines Helden inkl. EP-Saldo und Fertigkeiten.
     */
    public function show(Hero $hero, Request $request): View
    {
        $hero->load([
            'player.bookings.adventure',
            'classes.skills',
            'skills',
            'epTransactions.type',
        ]);

        $data = [
            'hero' => $hero,
            'epTypes' => EpTransactionType::orderBy('id')->get(),
        ];

        // Per AJAX (aus der Liste) nur den Modal-Inhalt liefern.
        if ($request->ajax()) {
            return view('heroes._detail', $data);
        }

        return view('heroes.show', $data);
    }

    /**
     * Formular zum Bearbeiten.
     */
    public function edit(Hero $hero, Request $request): View
    {
        $data = [
            'hero' => $hero,
            'players' => Player::orderBy('name')->get(),
            'classes' => HeroClass::where('disabled', false)->orderBy('name')->get(),
        ];

        if ($request->ajax()) {
            return view('heroes._edit_modal', $data);
        }

        return view('heroes.edit', $data);
    }

    /**
     * Helden aktualisieren.
     */
    public function update(Request $request, Hero $hero): RedirectResponse|JsonResponse
    {
        $data = $this->validateHero($request);

        $hero->update($data);
        $hero->classes()->sync($request->input('classes', []));

        $message = 'Held wurde aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('heroes.show', $hero)->with('status', $message);
    }

    /**
     * Helden löschen.
     */
    public function destroy(Hero $hero): RedirectResponse
    {
        $hero->delete();

        return redirect()
            ->route('heroes.index')
            ->with('status', 'Held wurde gelöscht.');
    }

    /**
     * Validierungsregeln für Anlegen und Bearbeiten.
     *
     * @return array<string, mixed>
     */
    private function validateHero(Request $request): array
    {
        $validated = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'character_name' => ['nullable', 'string', 'max:150'],
            'born' => ['nullable', 'date'],
            'died' => ['nullable', 'date', 'after_or_equal:born'],
            'homeplace' => ['nullable', 'string', 'max:150'],
            'active' => ['boolean'],
            'classes' => ['array'],
            'classes.*' => ['exists:hero_classes,id'],
        ]);

        // Checkbox liefert nichts, wenn nicht gesetzt.
        $validated['active'] = $request->boolean('active');

        // 'classes' wird separat über die Pivot-Relation gespeichert.
        unset($validated['classes']);

        return $validated;
    }
}
