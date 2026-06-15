<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
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
