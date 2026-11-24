{{-- Issue #31 (Phase 3): Reiter „Talk“ des Spielers – erscheint erst nach der Freigabe durch den Bürokraten.
     Eltern (verknüpft, nicht „self“) erteilen bzw. widerrufen hier die Erlaubnis für Minderjährige.
     Wird für Handy und Desktop doppelt eingebunden → $ctx als ID-Suffix. Erwartet: $player, $ctx. --}}
@php
    $isGuardian = auth()->user()->players()->whereKey($player->getKey())->wherePivot('self', false)->exists();
    $status = $player->talkStatus();
    $appUrl = \App\Models\Setting::get('talk_app_url');
    $helpUrl = \App\Models\Setting::get('talk_help_url') ?: config('nextcloud.default_help_url');
    $consentBy = $player->talk_consent_by ? \App\Models\User::find($player->talk_consent_by) : null;
@endphp

<div class="space-y-4">
    <p>
        <span class="font-medium">Status:</span> {{ $player->talkStatusLabel() }}
    </p>

    @if ($status === 'consent')
        @if ($isGuardian)
            <section aria-labelledby="talk-texts-{{ $ctx }}" class="space-y-3">
                <h3 id="talk-texts-{{ $ctx }}" class="font-medium text-stone-800">Bitte vor der Erlaubnis lesen</h3>
                @foreach (\App\Services\NextcloudTalk\TalkTexts::all() as $key => $text)
                    <div class="border border-stone-200 rounded-md p-3 bg-white/60">
                        <h4 class="font-medium mb-1">{{ \App\Services\NextcloudTalk\TalkTexts::SECTIONS[$key] }}</h4>
                        <p class="text-sm text-stone-700">{!! nl2br(e($text)) !!}</p>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('players.talk.consent', $player) }}" class="border border-amber-200 bg-amber-50 rounded-md p-3">
                    @csrf
                    <div class="flex items-start gap-2 mb-3">
                        <input type="checkbox" id="talk-accept-{{ $ctx }}" name="accept" value="1" required
                               class="mt-1 rounded border-gray-300 text-amber-700 focus:ring-amber-600">
                        <label for="talk-accept-{{ $ctx }}">
                            Ich habe die Teilnahmebedingungen, Sicherheitshinweise und Verhaltensregeln gelesen und erlaube
                            {{ $player->name }} die Nutzung von Nextcloud Talk.
                        </label>
                    </div>
                    <button type="submit" class="ui primary button"><i class="check icon" aria-hidden="true"></i> Erlaubnis erteilen</button>
                </form>
            </section>
        @else
            <p class="text-sm text-stone-700">Ein Elternteil muss die Talk-Nutzung hier im Heldenregister erlauben.</p>
        @endif
    @endif

    @if ($player->talk_consent_at)
        <p class="text-sm text-stone-700">
            Erlaubt{{ $consentBy ? ' von '.trim($consentBy->name.' '.$consentBy->lastname) : '' }}
            am {{ $player->talk_consent_at->format('d.m.Y') }}.
        </p>
    @endif

    @if ($status === 'pending')
        <p class="text-sm text-stone-700"><i class="hourglass half icon" aria-hidden="true"></i> Das Konto wird eingerichtet. Die Einladung kommt per E-Mail.</p>
    @elseif ($status === 'error')
        <p class="text-sm text-amber-800"><i class="exclamation triangle icon" aria-hidden="true"></i> Beim Einrichten des Kontos gab es ein Problem. Der Verein kümmert sich darum.</p>
    @elseif ($status === 'ready')
        <p class="text-sm text-stone-700">
            <i class="check circle icon text-green-700" aria-hidden="true"></i>
            Nextcloud-Benutzername: <strong>{{ $player->nextcloud_user_id }}</strong>
            @if ($player->nextcloud_account_created)
                – das Passwort wird über den Link in der Willkommens-E-Mail von Nextcloud gesetzt.
            @endif
        </p>
    @endif

    <div class="flex flex-wrap gap-2">
        @if ($appUrl)
            <a href="{{ $appUrl }}" target="_blank" rel="noopener" class="ui teal button">
                <i class="comments icon" aria-hidden="true"></i> Zur Talk-App
            </a>
        @endif
        <a href="{{ $helpUrl }}" target="_blank" rel="noopener" class="ui button">
            <i class="question circle icon" aria-hidden="true"></i> Hilfe zu Talk
        </a>
    </div>

    @if ($isGuardian && $player->isTalkMinor() && $player->talk_consent_at)
        <form method="POST" action="{{ route('players.talk.withdraw', $player) }}"
              data-confirm="Erlaubnis für Talk wirklich widerrufen? Das Konto wird deaktiviert.">
            @csrf @method('DELETE')
            <button type="submit" class="ui small basic red button"><i class="ban icon" aria-hidden="true"></i> Erlaubnis widerrufen</button>
        </form>
    @endif
</div>
