<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Nutzerverwaltung im Admin-Bereich (Legacy: pages/admin/users.php):
 * Liste aller Portal-Nutzer, Rollenzuweisung und Aktivierung.
 */
class UserController extends Controller
{
    /**
     * Liste aller Portal-Nutzer inkl. soft-gelöschter (AUTH-08).
     */
    public function index(): View
    {
        $users = User::withTrashed()->with('roles')->orderBy('name')->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Nutzer bearbeiten: Rollen + Aktivierung.
     */
    public function edit(User $user, Request $request): View
    {
        $data = [
            'user' => $user,
            'roles' => Role::orderBy('id')->get(),
            'assigned' => $user->roles->pluck('id')->all(),
        ];

        if ($request->expectsJson()) {
            return view('admin.users._form', $data);
        }

        return view('admin.users.edit', $data);
    }

    /**
     * Soft-gelöschtes Konto wiederherstellen (AUTH-08).
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users.index')
            ->with('status', 'Konto '.$user->name.' wurde wiederhergestellt.');
    }

    /**
     * Rollen und Aktivierungsstatus speichern.
     */
    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
            'activated' => ['boolean'],
        ]);

        $user->roles()->sync($data['roles'] ?? []);
        // Direktzuweisung, da activated nicht in $fillable (Mass-Assignment-Schutz).
        $user->activated = $request->boolean('activated');
        $user->save();

        $message = "Nutzer „{$user->name}“ wurde aktualisiert.";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.users.index')->with('status', $message);
    }
}
