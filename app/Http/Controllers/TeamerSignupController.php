<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\EventRole;
use App\Models\Role;
use App\Models\TeamerSignup;
use App\Models\User;
use App\Notifications\TeamerApproved;
use App\Notifications\TeamerInvitation;
use App\Notifications\TeamerRejected;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            // DSGVO Art. 9: Einwilligung Pflicht wenn Gesundheitsdaten angegeben (H-2).
            'health_data_consent' => [
                Rule::requiredIf(fn () => filled($request->allergien) || filled($request->medikamente)),
                'boolean',
            ],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'anmerkung' => ['nullable', 'string', 'max:1000'],
        ]);

        $hasHealthData = filled($data['allergien'] ?? null) || filled($data['medikamente'] ?? null);
        $data['health_data_consent_at'] = ($hasHealthData && $request->boolean('health_data_consent'))
            ? now()
            : null;
        unset($data['health_data_consent']);

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

    /** Bearbeitungsformular einer Teamer-Anmeldung (Modal-Partial, ADV-29). */
    public function edit(Adventure $adventure, TeamerSignup $signup): View
    {
        abort_unless(request()->user()->can('events.edit'), 403);
        abort_if($signup->adventure_id !== $adventure->id, 404);

        return view('adventures._teamer_signup_edit', compact('adventure', 'signup'));
    }

    /** Teamer-Anmeldung aktualisieren (ADV-29). */
    public function update(Request $request, Adventure $adventure, TeamerSignup $signup): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('events.edit'), 403);
        abort_if($signup->adventure_id !== $adventure->id, 404);

        $data = $request->validate([
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'allergien' => ['nullable', 'string', 'max:500'],
            'medikamente' => ['nullable', 'string', 'max:500'],
            // DSGVO Art. 9: Einwilligung Pflicht wenn Gesundheitsdaten angegeben (H-2).
            'health_data_consent' => [
                Rule::requiredIf(fn () => filled($request->allergien) || filled($request->medikamente)),
                'boolean',
            ],
            'leih_tunika' => ['boolean'],
            'leih_waffe' => ['boolean'],
            'anmerkung' => ['nullable', 'string', 'max:1000'],
            'teamer_role' => ['nullable', 'string', Rule::in(EventRole::forTeamer()->pluck('description'))],
        ]);

        $hasHealthData = filled($data['allergien'] ?? null) || filled($data['medikamente'] ?? null);
        $data['health_data_consent_at'] = ($hasHealthData && $request->boolean('health_data_consent'))
            ? ($signup->health_data_consent_at ?? now())
            : null;
        unset($data['health_data_consent']);

        $signup->update($data);

        $msg = 'Teamer-Anmeldung aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    /** Teamer-Anmeldung bestätigen / Bestätigung zurücknehmen (Toggle, ADV-29). */
    public function approve(Request $request, Adventure $adventure, TeamerSignup $signup): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('events.edit'), 403);
        abort_if($signup->adventure_id !== $adventure->id, 404);

        $wasApproved = (bool) $signup->approved_at;
        $signup->update([
            'approved_at' => $wasApproved ? null : now(),
            'rejected_at' => null,
        ]);

        if (! $wasApproved) {
            $signup->user->notify(new TeamerApproved($signup));
        }

        $msg = ! $wasApproved ? 'Teamer bestätigt.' : 'Bestätigung zurückgenommen.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    /** Teamer-Anmeldung ablehnen / Ablehnung zurücknehmen (Toggle, ADV-29). */
    public function reject(Request $request, Adventure $adventure, TeamerSignup $signup): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('events.edit'), 403);
        abort_if($signup->adventure_id !== $adventure->id, 404);

        $wasRejected = (bool) $signup->rejected_at;
        $signup->update([
            'rejected_at' => $wasRejected ? null : now(),
            'approved_at' => null,
        ]);

        if (! $wasRejected) {
            $signup->user->notify(new TeamerRejected($adventure));
        }

        $msg = ! $wasRejected ? 'Teamer abgelehnt.' : 'Ablehnung zurückgenommen.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }

    /** Bestätigungsmail für eine bereits bestätigte Teamer-Anmeldung erneut senden. */
    public function resendConfirmation(Request $request, Adventure $adventure, TeamerSignup $signup): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('events.edit'), 403);
        abort_if($signup->adventure_id !== $adventure->id, 404);

        if (! $signup->approved_at) {
            $msg = 'Die Teamer-Anmeldung ist noch nicht bestätigt.';
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $signup->user->notify(new TeamerApproved($signup));

        $msg = 'Bestätigungsmail erneut gesendet.';
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
            'teamer_role' => ['nullable', 'string', Rule::in(EventRole::forTeamer()->pluck('description'))],
        ]);

        $signup->update(['teamer_role' => $data['teamer_role'] ?: null]);

        $msg = 'Teamer-Rolle aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'refresh_modal' => true])
            : back()->with('status', $msg);
    }
}
