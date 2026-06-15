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
    public function index(): View
    {
        $players = Player::withTrashed()
            ->withCount('heroes')
            ->with(['users', 'matrixAccount'])
            ->orderBy('name')
            ->paginate(30);

        return view('admin.players.index', compact('players'));
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
