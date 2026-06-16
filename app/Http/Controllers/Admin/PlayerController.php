<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-Übersicht ALLER Spieler/Teilnehmer (Legacy: pages/admin/players.php),
 * im Gegensatz zu „Deine Spieler" (nur die eigenen).
 */
class PlayerController extends Controller
{
    /** Erlaubte Sortierspalten (PLAY-09). */
    private const SORT_COLUMNS = ['name', 'lastname', 'dayofbirth', 'heroes_count'];

    public function index(Request $request): View
    {
        $q = trim($request->string('q'));
        $sort = in_array($request->query('sort'), self::SORT_COLUMNS, true)
            ? $request->query('sort')
            : 'name';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $query = Player::withTrashed()
            ->withCount('heroes')
            ->with(['users', 'matrixAccount']);

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('lastname', 'like', "%{$q}%");
            });
        }

        // heroes_count ist ein aggregiertes Alias → orderByRaw nötig.
        if ($sort === 'heroes_count') {
            $query->orderByRaw("heroes_count {$dir}");
        } else {
            $query->orderBy($sort, $dir);
        }

        $players = $query->paginate(30)->withQueryString();

        return view('admin.players.index', compact('players', 'q', 'sort', 'dir'));
    }

    /**
     * Betreuer-Verwaltung eines Spielers als Modal (PLAY-06).
     */
    public function caretakers(Player $player): View
    {
        $player->load('users');

        return view('admin.players._caretakers', [
            'player' => $player,
            'available' => User::whereNotIn('id', $player->users->pluck('id'))
                ->orderBy('name')->orderBy('lastname')->get(),
        ]);
    }

    /**
     * Einen weiteren Betreuer (Nutzer) zuordnen (PLAY-06).
     */
    public function attachCaretaker(Request $request, Player $player): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);

        // Doppelte Zuordnung vermeiden (kein zweiter Pivot-Eintrag).
        if (! $player->users()->whereKey($data['user_id'])->exists()) {
            $player->users()->attach($data['user_id'], ['self' => false]);
        }

        return $this->respond($request, 'Betreuer zugeordnet.');
    }

    /**
     * Einen Betreuer entfernen (PLAY-06).
     */
    public function detachCaretaker(Request $request, Player $player, User $user): RedirectResponse|JsonResponse
    {
        $player->users()->detach($user->id);

        return $this->respond($request, 'Betreuer entfernt.');
    }

    /**
     * Spieler soft-löschen (PLAY-08).
     * Ohne ?force=1: bei offenen Buchungen/aktiven Helden Warnung und Abbruch.
     * Mit ?force=1: Löschen trotz Warnung (Admin-Override).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $player = Player::withTrashed()->findOrFail($id);

        if (! $request->boolean('force')) {
            $blockers = $this->collectBlockers($player);

            if ($blockers !== []) {
                return redirect()
                    ->route('admin.players.index')
                    ->with('warning', implode(' ', $blockers))
                    ->with('force_delete_id', $player->id)
                    ->with('force_delete_name', $player->full_name);
            }
        }

        $player->delete();

        return redirect()->route('admin.players.index')
            ->with('status', 'Spieler "'.$player->full_name.'" wurde gelöscht.');
    }

    /**
     * Soft-Delete eines Spielers rückgängig machen (PLAY-08).
     */
    public function restore(int $id): RedirectResponse
    {
        $player = Player::withTrashed()->findOrFail($id);
        $player->restore();

        return redirect()->route('admin.players.index')
            ->with('status', 'Spieler "'.$player->full_name.'" wurde wiederhergestellt.');
    }

    /** Offene Buchungen und aktive Helden als Warnhinweis-Texte sammeln. */
    private function collectBlockers(Player $player): array
    {
        $blockers = [];

        $openBookings = $player->bookings()
            ->whereNotIn('status', ['abgemeldet', 'abgelehnt'])
            ->count();
        if ($openBookings > 0) {
            $blockers[] = "Der Spieler hat {$openBookings} offene/bestätigte Anmeldung(en).";
        }

        $activeHeroes = $player->heroes()->count();
        if ($activeHeroes > 0) {
            $blockers[] = "Der Spieler hat {$activeHeroes} aktiven Helden.";
        }

        return $blockers;
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Mitglieder-/Spielerübersicht als CSV (REP-04). DSGVO-konform: nur die für
     * die Orga nötigen Felder.
     */
    public function export(): StreamedResponse
    {
        $players = Player::withCount('heroes')->orderBy('lastname')->orderBy('name')->get();

        return response()->streamDownload(function () use ($players) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nachname', 'Vorname', 'E-Mail', 'Geburtsdatum', 'Geschlecht', 'Helden'], ';');

            foreach ($players as $p) {
                fputcsv($out, [
                    $p->lastname,
                    $p->name,
                    $p->email,
                    optional($p->dayofbirth)->format('d.m.Y'),
                    $p->gender,
                    $p->heroes_count,
                ], ';');
            }
            fclose($out);
        }, 'spieler.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
