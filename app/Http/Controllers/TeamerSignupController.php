<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\Role;
use App\Models\TeamerSignup;
use App\Models\User;
use App\Notifications\TeamerInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Teamer-Anmeldung zu einem Event (ADV-27).
 * Nur Teamer und Lehrmeister können sich anmelden; separate von Teilnehmer-Buchungen.
 */
class TeamerSignupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /** Anmeldeformular (Modal-Partial). */
    public function create(Adventure $adventure): View
    {
        abort_unless(
            request()->user()->hasAnyRole('teamer', 'lehrmeister'),
            403
        );

        return view('adventures._teamer_signup_form', compact('adventure'));
    }

    /** Teamer-Anmeldung speichern. */
    public function store(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasAnyRole('teamer', 'lehrmeister'), 403);

        // Bereits angemeldet?
        if ($adventure->teamerSignups()->where('user_id', $request->user()->id)->exists()) {
            $msg = 'Du bist bereits als Teamer angemeldet.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $data = $request->validate([
            'agb' => ['accepted'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'allergien' => ['nullable', 'string', 'max:500'],
            'medikamente' => ['nullable', 'string', 'max:500'],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'anmerkung' => ['nullable', 'string', 'max:1000'],
        ]);

        $adventure->teamerSignups()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        $msg = 'Teamer-Anmeldung gespeichert.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    /** Teamer-Anmeldung stornieren. */
    public function destroy(Request $request, Adventure $adventure, TeamerSignup $signup): RedirectResponse|JsonResponse
    {
        // Nur der eigene Eintrag oder Projektleitung/Admin darf stornieren.
        $isOwn = $signup->user_id === $request->user()->id;
        abort_unless($isOwn || $request->user()->can('portal.manage'), 403);

        abort_if($signup->adventure_id !== $adventure->id, 404);

        $signup->delete();

        $msg = 'Teamer-Anmeldung wurde storniert.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    /**
     * Teamer-Einladung versenden (ADV-28).
     * Schickt Mail + In-App-Notification an alle aktiven Teamer/Lehrmeister
     * mit eingeschalteten Teamer-Benachrichtigungen.
     */
    public function invite(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('events.edit'), 403);

        $teamerRoleIds = Role::whereIn('slug', ['teamer', 'lehrmeister'])->pluck('id');

        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('roles.id', $teamerRoleIds))
            ->where('activated', true)
            ->where('teamer_notifications', true)
            ->get();

        $recipients->each->notify(new TeamerInvitation($adventure));

        $msg = "Einladung an {$recipients->count()} Teamer verschickt.";

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    /** Teamer-Rolle zuweisen (nur Projektleitung/Admin). */
    public function updateRole(Request $request, Adventure $adventure, TeamerSignup $signup): RedirectResponse|JsonResponse
    {
        // Rollenzuweisung erfordert events.edit (Bürokrat, Projektleiter, Admin).
        abort_unless($request->user()->can('events.edit'), 403);
        abort_if($signup->adventure_id !== $adventure->id, 404);

        $data = $request->validate([
            'teamer_role' => ['nullable', 'string', 'in:'.implode(',', TeamerSignup::ROLES)],
        ]);

        $signup->update(['teamer_role' => $data['teamer_role'] ?: null]);

        $msg = 'Teamer-Rolle aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }
}
