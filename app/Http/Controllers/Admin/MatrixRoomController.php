<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatrixManagedRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MTX-05: CRUD für matrix_managed_rooms.
 * Räume werden in der Matrix-Konto-Provisionierung als Mitgliedschafts-
 * Checkboxen angeboten (MatrixAccountController@edit).
 */
class MatrixRoomController extends Controller
{
    public function index(): View
    {
        $rooms = MatrixManagedRoom::withCount('accounts')
            ->orderBy('roomname')
            ->get();

        return view('admin.matrix.rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('admin.matrix.rooms._form', [
            'room'   => new MatrixManagedRoom(),
            'action' => route('admin.matrix.rooms.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'roomid'        => ['required', 'string', 'max:120', 'unique:matrix_managed_rooms,roomid'],
            'roomname'      => ['required', 'string', 'max:50'],
            'roomtype'      => ['required', 'in:Raum,Space'],
            'default_allow' => ['boolean'],
            'default_deny'  => ['boolean'],
        ]);

        MatrixManagedRoom::create($data);

        return redirect()->route('admin.matrix.rooms.index')
            ->with('status', 'Raum "'.$data['roomname'].'" angelegt.');
    }

    public function edit(MatrixManagedRoom $room): View
    {
        return view('admin.matrix.rooms._form', [
            'room'   => $room,
            'action' => route('admin.matrix.rooms.update', $room),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, MatrixManagedRoom $room): RedirectResponse
    {
        $data = $request->validate([
            'roomname'      => ['required', 'string', 'max:50'],
            'roomtype'      => ['required', 'in:Raum,Space'],
            'default_allow' => ['boolean'],
            'default_deny'  => ['boolean'],
        ]);

        $room->update($data);

        return redirect()->route('admin.matrix.rooms.index')
            ->with('status', 'Raum "'.$room->roomname.'" gespeichert.');
    }

    public function destroy(MatrixManagedRoom $room): RedirectResponse
    {
        // Mitgliedschaften vorher prüfen — roomid ist FK in matrix_room_memberships.
        if ($room->accounts()->exists()) {
            return back()->withErrors([
                'roomid' => 'Raum "'.$room->roomname.'" hat noch aktive Mitglieder und kann nicht gelöscht werden.',
            ]);
        }

        $name = $room->roomname;
        $room->delete();

        return redirect()->route('admin.matrix.rooms.index')
            ->with('status', 'Raum "'.$name.'" gelöscht.');
    }
}
