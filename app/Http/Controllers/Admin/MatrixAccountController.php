<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatrixAccount;
use App\Models\MatrixManagedRoom;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Provisioniert pro Spieler ein Matrix-Konto für matrix-corporal
 * (Legacy: ajax/admin_users.php → save_player → matrix_account).
 * Die Konten sind die Quelle der Wahrheit für den Matrix-Server.
 */
class MatrixAccountController extends Controller
{
    /**
     * Formular: Matrix-Konto eines Spielers verwalten.
     */
    public function edit(Player $player): View
    {
        $account = $player->matrixAccount()->withTrashed()->first();

        $rooms = MatrixManagedRoom::orderBy('roomname')->get();

        // MTX-06: Neues Konto → default_allow-Räume vorselektieren.
        if ($account) {
            $joined = $account->rooms()->pluck('matrix_managed_rooms.roomid')->all();
        } else {
            $joined = $rooms->where('default_allow', true)->pluck('roomid')->all();
        }

        return view('admin.matrix.edit', [
            'player'  => $player,
            'account' => $account,
            'rooms'   => $rooms,
            'joined'  => $joined,
            'mxid'    => $account?->mxid ?? $player->deriveMatrixId(),
        ]);
    }

    /**
     * Matrix-Konto anlegen/aktualisieren und Raum-Mitgliedschaften setzen.
     */
    public function update(Request $request, Player $player): RedirectResponse
    {
        $data = $request->validate([
            'active' => ['boolean'],
            'forbid_room_creation' => ['boolean'],
            'auth_credential' => ['nullable', 'string', 'max:100'],
            'rooms' => ['array'],
            'rooms.*' => ['exists:matrix_managed_rooms,roomid'],
        ]);

        $account = $player->matrixAccount()->withTrashed()->first();
        $active = $request->boolean('active');

        // Ohne bestehendes Konto und ohne aktiven Zugang gibt es nichts zu tun.
        if (! $account && ! $active) {
            return back()->with('status', 'Kein Matrix-Zugang vergeben.');
        }

        DB::transaction(function () use ($player, $account, $active, $request) {
            if (! $account) {
                // Neuanlage: mxid einmalig aus dem Namen ableiten.
                // uniqueMatrixId() sanitisiert + prüft Kollisionen (MTX-07).
                $account = new MatrixAccount(['mxid' => $player->uniqueMatrixId()]);
                $account->player_id = $player->id;
            }

            $account->deleted_at = null; // ggf. zuvor entzogenen Zugang reaktivieren
            $account->display_name = $player->full_name;
            $account->active = $active;
            $account->forbid_room_creation = $request->boolean('forbid_room_creation');
            if ($request->filled('auth_credential')) {
                $account->auth_credential = $request->input('auth_credential');
            }
            $account->save();

            // Raum-Mitgliedschaften abgleichen.
            $account->rooms()->sync($request->input('rooms', []));
        });

        return back()->with('status', "Matrix-Konto für {$player->full_name} gespeichert.");
    }

    /**
     * Matrix-Zugang entziehen (soft-delete -> fällt aus der corporal-Policy).
     */
    public function destroy(Player $player): RedirectResponse
    {
        $player->matrixAccount()->first()?->delete();

        return back()->with('status', 'Matrix-Zugang wurde entzogen.');
    }
}
