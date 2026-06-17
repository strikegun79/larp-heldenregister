<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Die Spieler des angemeldeten Benutzers (Legacy: „Deine Spieler").
     */
    public function index(Request $request): View
    {
        $q = trim($request->string('q'));

        $query = $request->user()->players()
            ->with(['heroes.classes', 'activeHero'])
            ->withCount('visits')
            ->orderBy('name');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('lastname', 'like', "%{$q}%");
            });
        }

        $players = $query->get();

        return view('players.index', compact('players', 'q'));
    }

    public function create(Request $request): View
    {
        $data = ['player' => new Player];

        // Für das Modal (PLAY-10) nur das Formular liefern.
        return $request->ajax()
            ? view('players._create_modal', $data)
            : view('players.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatePlayer($request);

        $player = Player::create($data);
        // Spieler dem Benutzer zuordnen (Legacy: user2player, self-Flag).
        $request->user()->players()->attach($player->id, ['self' => false]);
        $this->enforceSingleSelf($request->user(), $player, $request->boolean('self'));

        $message = 'Spieler wurde angelegt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('players.show', $player)->with('status', $message);
    }

    /**
     * Genau einen „self"-Spieler je Nutzer erzwingen (PLAY-05): wird ein Spieler
     * als „self" markiert, werden alle anderen self-Markierungen des Nutzers
     * zurückgesetzt.
     */
    private function enforceSingleSelf(User $user, Player $player, bool $self): void
    {
        if ($self) {
            DB::table($user->players()->getTable())
                ->where('user_id', $user->id)
                ->update(['self' => false]);
        }

        $user->players()->updateExistingPivot($player->id, ['self' => $self]);
    }

    /**
     * Avatar-Upload verarbeiten (PLAY-10): altes Bild ersetzen, Pfad speichern.
     */
    private function handleImageUpload(Request $request, Player $player): void
    {
        if ($request->hasFile('image')) {
            if ($player->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($player->image);
            }
            $player->update(['image' => \App\Support\AvatarStorage::storeSquare($request->file('image'), 'players')]);
        }
    }

    /** Avatar löschen und auf Standard-Bild zurücksetzen. */
    public function deleteAvatar(Request $request, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $player);

        if ($player->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($player->image);
            $player->update(['image' => null]);
        }

        $message = 'Avatar gelöscht.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Avatar separat über den Avatar-Tab hochladen (PLAY-11): nur Bild,
     * JPG/PNG bis 2 MB, zentriert auf 1:1 zugeschnitten.
     */
    public function uploadAvatar(Request $request, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $player);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:20480'],
        ]);

        $this->handleImageUpload($request, $player);

        $message = 'Avatar aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    public function show(Player $player, Request $request): View
    {
        $this->authorize('view', $player);
        $player->load(['heroes.classes', 'heroes.epTransactions.type', 'visits.adventure']);

        if ($request->ajax()) {
            return view('players._detail', compact('player'));
        }

        return view('players.show', compact('player'));
    }

    public function edit(Player $player, Request $request): View
    {
        $this->authorize('update', $player);

        $data = [
            'player' => $player,
            'self' => $player->users()->wherePivot('user_id', auth()->id())->wherePivot('self', true)->exists(),
        ];

        if ($request->ajax()) {
            return view('players._edit_modal', $data);
        }

        return view('players.edit', $data);
    }

    public function update(Request $request, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $player);

        $player->update($this->validatePlayer($request));
        $this->enforceSingleSelf($request->user(), $player, $request->boolean('self'));

        $message = 'Spieler wurde aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('players.show', $player)->with('status', $message);
    }

    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);
        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('status', 'Spieler wurde gelöscht.');
    }

    /**
     * Setzt den aktiven Helden des Spielers (Legacy: player.hero_active).
     * Es kann nur ein aktiver Held je Spieler gesetzt sein (HERO-07).
     */
    public function setActiveHero(Request $request, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $player);

        $data = $request->validate(['hero_id' => ['required', 'integer']]);

        // Der Held muss zu diesem Spieler gehören.
        abort_unless($player->heroes()->whereKey($data['hero_id'])->exists(), 422);

        // Es kann nur einen aktiven Helden geben (HERO-21): alle anderen
        // Helden des Spielers werden auf inaktiv gesetzt.
        DB::transaction(function () use ($player, $data) {
            $player->heroes()->update(['active' => false]);
            $player->heroes()->whereKey($data['hero_id'])->update(['active' => true]);
            $player->update(['active_hero_id' => $data['hero_id']]);
        });

        $message = 'Aktiver Held gesetzt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlayer(Request $request): array
    {
        $sameAsGuardian = filter_var($request->input('address_same_as_guardian', '1'), FILTER_VALIDATE_BOOLEAN);

        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            // Plausibles Geburtsdatum: nicht in der Zukunft, nicht vor 1900 (PLAY-07).
            'dayofbirth' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['nullable', 'in:weiblich,männlich,divers'],
            // Kinder-Anschrift (PLAY-14 / ORGA-01): nur Pflicht bei abweichender Anschrift.
            'address_same_as_guardian' => ['boolean'],
            'street' => [$sameAsGuardian ? 'nullable' : 'required', 'string', 'max:100'],
            'house_number' => [$sameAsGuardian ? 'nullable' : 'required', 'string', 'max:10'],
            'zip' => [$sameAsGuardian ? 'nullable' : 'required', 'string', 'max:10'],
            'city' => [$sameAsGuardian ? 'nullable' : 'required', 'string', 'max:100'],
        ]);
    }
}
