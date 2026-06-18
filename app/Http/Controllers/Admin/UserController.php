<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
     * Formular für neuen Nutzer (ADM-06).
     */
    public function create(Request $request): View
    {
        $data = ['roles' => Role::orderBy('id')->get()];

        if ($request->expectsJson()) {
            return view('admin.users._create_form', $data);
        }

        return view('admin.users.create', $data);
    }

    /**
     * Neuen Nutzer anlegen und Einladungsmail versenden (ADM-06).
     * E-Mail gilt durch Admin als verifiziert; Passwort wird per Reset-Link gesetzt.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user = new User;
        $user->name = $data['name'];
        $user->lastname = $data['lastname'] ?? null;
        $user->email = $data['email'];
        $user->password = Hash::make(Str::random(32));
        $user->activated = true;
        $user->email_verified_at = now();
        $user->save();

        $user->roles()->sync($data['roles'] ?? []);

        // Einladungsmail: Nutzer erhält Link zum Setzen seines Passworts.
        Password::sendResetLink(['email' => $user->email]);

        AuditLogger::log('user.created', $user, [
            'email' => $user->email,
            'roles' => $data['roles'] ?? [],
        ]);

        $message = "Konto für \"{$user->name}\" angelegt – Einladungsmail wurde verschickt.";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.users.index')->with('status', $message);
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
     * Nutzer soft-löschen. Schutz: kein Selbst-Löschen, keine Admins löschen.
     */
    public function destroy(int $id, Request $request): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['delete' => 'Du kannst dein eigenes Konto hier nicht löschen.']);
        }

        if ($user->hasRole('admin')) {
            return back()->withErrors(['delete' => 'Admin-Konten können nicht gelöscht werden.']);
        }

        $user->delete();

        AuditLogger::log('user.deleted', $user);

        return redirect()->route('admin.users.index')
            ->with('status', 'Konto '.$user->name.' wurde gelöscht.');
    }

    /**
     * Soft-gelöschtes Konto wiederherstellen (AUTH-08).
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        AuditLogger::log('user.restored', $user);

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

        $oldRoles = $user->roles->pluck('id')->sort()->values()->all();
        $oldActivated = $user->activated;

        $user->roles()->sync($data['roles'] ?? []);
        // Direktzuweisung, da activated nicht in $fillable (Mass-Assignment-Schutz).
        $user->activated = $request->boolean('activated');
        $user->save();

        $newRoles = array_values(array_map('intval', $data['roles'] ?? []));
        sort($newRoles);

        AuditLogger::log('user.updated', $user, [
            'roles' => ['von' => $oldRoles, 'auf' => $newRoles],
            'activated' => ['von' => $oldActivated, 'auf' => $user->activated],
        ]);

        $message = "Nutzer „{$user->name}“ wurde aktualisiert.";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.users.index')->with('status', $message);
    }
}
