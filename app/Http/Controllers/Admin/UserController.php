<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
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
     * Liste aller Portal-Nutzer.
     */
    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Nutzer bearbeiten: Rollen + Aktivierung.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('id')->get(),
            'assigned' => $user->roles->pluck('id')->all(),
        ]);
    }

    /**
     * Rollen und Aktivierungsstatus speichern.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
            'activated' => ['boolean'],
        ]);

        $user->roles()->sync($data['roles'] ?? []);
        $user->update(['activated' => $request->boolean('activated')]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Nutzer „{$user->name}“ wurde aktualisiert.");
    }
}
