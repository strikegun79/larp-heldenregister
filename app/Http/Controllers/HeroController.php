<?php

namespace App\Http\Controllers;

use App\Models\EpTransaction;
use App\Models\EpTransactionType;
use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HeroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:heldenregister.view')->only(['index', 'epExport', 'sheetPdf']);
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
     * EP-Konto-Auszug eines Helden als CSV (REP-02): Datum, Art, Betrag, Saldo.
     */
    public function epExport(Hero $hero): StreamedResponse
    {
        $transactions = $hero->epTransactions()->with('type')
            ->orderBy('transacted_at')->orderBy('id')->get();

        $filename = 'ep-auszug-'.$hero->id.'.csv';

        return response()->streamDownload(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM für Excel
            fputcsv($out, ['Datum', 'Art', 'Betrag', 'Saldo'], ';');

            $balance = 0.0;
            foreach ($transactions as $tx) {
                /** @var EpTransaction $tx */
                $balance += $tx->signedAmount();
                fputcsv($out, [
                    optional($tx->transacted_at)->format('d.m.Y H:i'),
                    $tx->type?->description ?? '—',
                    number_format($tx->signedAmount(), 0, ',', ''),
                    number_format($balance, 0, ',', ''),
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Helden-Foto hochladen (HERO-22): erlaubt für heldenregister.edit ODER
     * den Spieler-Eigentümer (Teilnehmer, der diesen Helden besitzt).
     */
    public function uploadPhoto(Request $request, Hero $hero): RedirectResponse|JsonResponse
    {
        $this->authorizePhotoAccess($request, $hero);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:20480'],
        ]);

        if ($hero->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($hero->image);
        }
        $hero->update(['image' => \App\Support\AvatarStorage::storeSquare($request->file('image'), 'heroes')]);

        $message = 'Helden-Foto aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Helden-Foto löschen (HERO-22).
     */
    public function deletePhoto(Request $request, Hero $hero): RedirectResponse|JsonResponse
    {
        $this->authorizePhotoAccess($request, $hero);

        if ($hero->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($hero->image);
            $hero->update(['image' => null]);
        }

        $message = 'Helden-Foto gelöscht.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Foto-Berechtigung: heldenregister.edit (Bürokrat/Admin) ODER
     * der Nutzer ist Betreuer des Spielers, dem dieser Held gehört.
     */
    private function authorizePhotoAccess(Request $request, Hero $hero): void
    {
        if ($request->user()->can('heldenregister.edit')) {
            return;
        }

        $isOwner = $hero->player_id && \Illuminate\Support\Facades\DB::table('player_user')
            ->where('player_id', $hero->player_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_unless($isOwner, 403);
    }

    /**
     * Charakterbogen eines Helden als PDF (REP-05): Stammdaten, Klassen,
     * Fertigkeiten und EP im Vereins-Layout.
     */
    public function sheetPdf(Hero $hero): \Symfony\Component\HttpFoundation\Response
    {
        $hero->load(['player', 'classes', 'skills', 'epTransactions.type']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('heroes.sheet_pdf', ['hero' => $hero]);

        return $pdf->stream('charakterbogen-'.$hero->id.'.pdf');
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
        $this->handleImageUpload($request, $hero);

        return redirect()
            ->route('heroes.show', $hero)
            ->with('status', 'Held wurde angelegt.');
    }

    /**
     * Detailansicht eines Helden inkl. EP-Saldo und Fertigkeiten.
     */
    public function show(Hero $hero, Request $request): View
    {
        // Sichtbar für Heldenregister-Berechtigte ODER den Spieler-Eigentümer
        // (lesend); bearbeiten nur mit heldenregister.edit (PLAY-11).
        abort_unless(
            $request->user()->can('heldenregister.view') || ($hero->player && $request->user()->can('view', $hero->player)),
            403
        );

        $hero->load([
            'player.bookings.adventure',
            'player.users',
            'classes.skills',
            'skills.perlColor',
            'epTransactions.type',
            'epTransactions.adventure',
        ]);

        $data = [
            'hero' => $hero,
            'epTypes' => EpTransactionType::orderBy('id')->get(),
            // Aktive, noch nicht zugewiesene Klassen für die Klassenverwaltung (HERO-06).
            'availableClasses' => HeroClass::where('disabled', false)
                ->whereNotIn('id', $hero->classes->pluck('id'))
                ->orderBy('name')
                ->get(),
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
        $this->handleImageUpload($request, $hero);
        // Klassen werden nicht mehr über das Formular gesynct – Hinzufügen/Entfernen
        // läuft über HeroClassController mit EP-Verbuchung (HERO-06).

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
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'active' => ['boolean'],
            'classes' => ['array'],
            'classes.*' => ['exists:hero_classes,id'],
        ]);

        // Checkbox liefert nichts, wenn nicht gesetzt.
        $validated['active'] = $request->boolean('active');

        // 'classes' (Pivot) und 'image' (Datei-Upload) werden separat behandelt.
        unset($validated['classes'], $validated['image']);

        return $validated;
    }

    /**
     * Avatar-Upload verarbeiten (HERO-09): altes Bild ersetzen, Pfad speichern.
     */
    private function handleImageUpload(Request $request, Hero $hero): void
    {
        if ($request->hasFile('image')) {
            if ($hero->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($hero->image);
            }
            $hero->update(['image' => $request->file('image')->store('heroes', 'public')]);
        }
    }
}
