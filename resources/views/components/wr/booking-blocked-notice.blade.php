{{-- Waldritter v1: sichtbarer Hinweis, warum „Anmelden“ gesperrt ist (statt nur Tooltip).
     Erscheint nur, wenn der Nutzer buchen darf, die Anmeldung offen ist und Profil oder Spieler fehlen. --}}
@props(['adventure', 'profileComplete', 'userHasPlayers'])

@if (auth()->user()?->can('adventure.book') && $adventure->registrationOpen() && (! $profileComplete || ! $userHasPlayers))
    <div {{ $attributes->merge(['class' => 'wr-notice']) }} role="note">
        <i class="info circle icon m-0 mt-0.5" aria-hidden="true"></i>
        <div class="space-y-2">
            <p class="font-semibold">Bevor du dich anmelden kannst:</p>
            <ul class="m-0 pl-5 list-disc space-y-1">
                @unless ($profileComplete)
                    <li>Vervollständige dein Profil (Adresse und Telefonnummer).
                        <a href="{{ route('profile.edit') }}" class="wr-interactive underline font-semibold text-wr-ink">Zum Profil</a>
                    </li>
                @endunless
                @unless ($userHasPlayers)
                    <li>Lege einen Spieler an (dich selbst oder dein Kind).
                        <a href="{{ route('players.create') }}" class="wr-interactive underline font-semibold text-wr-ink">Spieler anlegen</a>
                    </li>
                @endunless
            </ul>
        </div>
    </div>
@endif
