<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\NewsletterSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $nlSub = NewsletterSubscription::where('email', $user->email)->first();

        return view('profile.edit', [
            'user'                  => $user,
            'newsletterSubscription' => $nlSub,
            'nlActive'              => $nlSub !== null && $nlSub->isActive(),
            'nlPending'             => $nlSub !== null && ! $nlSub->isConfirmed() && $nlSub->unsubscribed_at === null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Checkboxen senden keinen Wert wenn deaktiviert → explizit setzen.
        $user = $request->user();
        foreach ([
            'teamer_notifications',
            'notify_new_user',
            'notify_booking_received',
            'notify_booking_approved',
            'notify_booking_rejected',
            'notify_booking_cancelled',
            'notify_payment_confirmed',
            'notify_waitlist_promoted',
            'notify_event_cancelled',
            'notify_event_reminder',
            'notify_cancellation_report',
        ] as $col) {
            $user->$col = $request->boolean($col);
        }

        // Pflichtbenachrichtigungen können nicht deaktiviert werden.
        foreach (['notify_booking_received', 'notify_booking_approved', 'notify_booking_rejected', 'notify_waitlist_promoted', 'notify_event_cancelled'] as $col) {
            $user->$col = true;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * DSGVO Art. 20: Alle personenbezogenen Daten des Nutzers als JSON-Download.
     */
    public function exportData(Request $request): Response
    {
        $user = $request->user();
        $user->load([
            'players.heroes',
            'players.bookings.adventure',
            'players.bookings.role',
        ]);

        $data = [
            'export_erstellt_am' => now()->toIso8601String(),
            'konto' => [
                'vorname'        => $user->name,
                'nachname'       => $user->lastname,
                'email'          => $user->email,
                'telefon'        => $user->phone,
                'strasse'        => $user->street,
                'hausnummer'     => $user->house_number,
                'plz'            => $user->zip,
                'ort'            => $user->city,
                'registriert_am' => $user->created_at?->toIso8601String(),
            ],
            'spieler' => $user->players->map(fn ($player) => [
                'vorname'      => $player->name,
                'nachname'     => $player->lastname,
                'geburtsdatum' => $player->dayofbirth?->toDateString(),
                'geschlecht'   => $player->gender,
                'helden' => $player->heroes->map(fn ($hero) => [
                    'name'         => $hero->name,
                    'klasse'       => $hero->heroClass?->name ?? null,
                    'ep_gesamt'    => $hero->ep_total,
                    'erstellt_am'  => $hero->created_at?->toIso8601String(),
                ]),
                'anmeldungen' => $player->bookings->map(fn ($b) => [
                    'abenteuer'    => $b->adventure?->name,
                    'datum'        => $b->adventure?->start_at?->toDateString(),
                    'rolle'        => $b->role?->description,
                    'status'       => $b->status,
                    'warteliste'   => (bool) $b->waitlisted,
                    'allergien'    => $b->allergien,
                    'medikamente'  => $b->medikamente,
                    'angemeldet_am' => $b->created_at?->toIso8601String(),
                ]),
            ]),
        ];

        $filename = 'meine-daten-heldenregister-'.now()->format('Y-m-d').'.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // DSGVO Art. 17: Konto anonymisieren statt nur soft-deleten,
        // damit das Versprechen "alle Daten dauerhaft entfernt" stimmt.
        $user->anonymize();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
