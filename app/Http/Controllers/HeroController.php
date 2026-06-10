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
        $this->middleware('can:heldenregister.edit')->only(['create', 'store', 'edit', 'update', 'destroy', 'toggleMissing']);
    }

    /**
     * Liste aller Helden (Heldenregister).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');     // active | inactive | missing | (null = alle)
        $classId = $request->query('class_id');
        $playerId = $request->query('player_id');
        $q = trim((string) $request->query('q'));

        $heroes = Hero::with(['player', 'classes', 'epTransactions.type'])
            ->when($status === 'missing', fn ($query) => $query->whereNotNull('died'))
            ->when($status === 'active', fn ($query) => $query->whereNull('died')->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->whereNull('died')->where('active', false))
            ->when($classId, fn ($query) => $query->whereHas('classes', fn ($c) => $c->whereKey($classId)))
            ->when($playerId, fn ($query) => $query->where('player_id', $playerId))
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('character_name', 'like', "%{$q}%")
                    ->orWhereHas('player', fn ($p) => $p
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('lastname', 'like', "%{$q}%"))
                    // auch erlernte Fertigkeiten durchsuchen (Helden, die den Skill besitzen)
                    ->orWhereHas('skills', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            }))
            ->orderBy('character_name')
            ->paginate(20)
            ->withQueryString();

        return view('heroes.index', [
            'heroes' => $heroes,
            'status' => $status,
            'classId' => $classId,
            'playerId' => $playerId,
            'q' => $q,
            'classes' => HeroClass::orderBy('name')->get(),
            'players' => Player::orderBy('name')->get(),
        ]);
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
            'epTransactions.adventure',
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
     * Schaltet den „Verschollen"-Status um: setzt `died` (Verschollen) und
     * deaktiviert den Helden bzw. macht ihn wieder aktiv (HERO-08).
     */
    public function toggleMissing(Request $request, Hero $hero): RedirectResponse|JsonResponse
    {
        if ($hero->died === null) {
            $hero->update(['died' => now()->toDateString(), 'active' => false]);
            $message = "{$hero->character_name} wurde als verschollen markiert.";
        } else {
            $hero->update(['died' => null, 'active' => true]);
            $message = "{$hero->character_name} ist wieder aufgetaucht.";
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
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
