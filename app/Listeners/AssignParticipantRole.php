<?php

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

/**
 * Weist jedem neu registrierten Benutzer die Rolle „Teilnehmer" zu
 * (Basisrolle: nur Profil + eigene Spieler). Höhere Rollen vergibt
 * anschließend ein Admin in der Verwaltung.
 */
class AssignParticipantRole
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        // Defensiv: nur zuordnen, wenn die Rolle vorhanden ist (Seed).
        $participant = Role::where('slug', 'participant')->first();

        if ($participant) {
            $user->roles()->syncWithoutDetaching([$participant->id]);
        }
    }
}
