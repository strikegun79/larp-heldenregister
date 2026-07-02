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
     * Admin: vollständiges Nutzerprofil anzeigen (AUTH-14).
     */
    public function showProfile(User $user): View
    {
        return view('admin.users.profile', compact('user'));
    }

    /**
     * Admin: Stammdaten eines Nutzers speichern (AUTH-14).
     */
    public function updateProfile(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'lastname'     => ['nullable', 'string', 'max:255'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
            'phone'        => ['nullable', 'string', 'max:50'],
            'street'       => ['nullable', 'string', 'max:100'],
            'house_number' => ['nullable', 'string', 'max:10'],
            'zip'          => ['nullable', 'string', 'max:10'],
            'city'         => ['nullable', 'string', 'max:100'],
        ]);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        AuditLogger::log('user.profile_updated', $user, ['by_admin' => $request->user()->id]);

        return redirect()->route('admin.users.profile', $user)
            ->with('status', 'Profil von "'.$user->name.'" wurde aktualisiert.');
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
    public function destroy(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Du kannst dein eigenes Konto hier nicht löschen.'], 422)
                : back()->withErrors(['delete' => 'Du kannst dein eigenes Konto hier nicht löschen.']);
        }

        if ($user->hasRole('admin')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Admin-Konten können nicht gelöscht werden.'], 422)
                : back()->withErrors(['delete' => 'Admin-Konten können nicht gelöscht werden.']);
        }

        $user->delete();

        AuditLogger::log('user.deleted', $user);

        $message = 'Konto '.$user->name.' wurde gelöscht.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.users.index')->with('status', $message);
    }

    /**
     * Admin: Passwort eines Nutzers setzen (AUTH-14).
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($request->input('password'));
        $user->save();

        AuditLogger::log('user.password_changed', $user, ['by_admin' => $request->user()->id]);

        return redirect()->route('admin.users.profile', $user)
            ->with('status', 'Passwort von "'.$user->name.'" wurde geändert.');
    }

    /**
     * Admin: E-Mail-Benachrichtigungen eines Nutzers speichern (AUTH-14).
     */
    public function updateNotifications(Request $request, User $user): RedirectResponse
    {
        $cols = [
            'teamer_notifications', 'notify_new_user', 'notify_booking_received',
            'notify_booking_approved', 'notify_booking_rejected', 'notify_booking_cancelled',
            'notify_payment_confirmed', 'notify_waitlist_promoted', 'notify_event_cancelled',
            'notify_event_reminder', 'notify_cancellation_report',
        ];

        foreach ($cols as $col) {
            $user->$col = $request->boolean($col);
        }
        $user->save();

        AuditLogger::log('user.notifications_updated', $user, ['by_admin' => $request->user()->id]);

        return redirect()->route('admin.users.profile', $user)
            ->with('status', 'Benachrichtigungen von "'.$user->name.'" wurden gespeichert.');
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
