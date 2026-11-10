<x-public-layout :title="'Fragebogen: ' . $link->survey->adventure->name">
    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="survey-wrap">
        <div class="survey-card">

            {{-- Veranstaltungsname --}}
            <div class="text-center mb-6">
                <div class="text-4xl mb-2">🏰</div>
                <h1 class="text-2xl font-bold text-waldritter" style="font-family:'EB Garamond',serif;">
                    {{ $link->survey->adventure->name }}
                </h1>
                <p class="text-sm text-stone-500 mt-1">Rückmeldung nach dem Abenteuer</p>
            </div>

            {{-- Begrüßung je Zielgruppe --}}
            @if(in_array($link->target_type, ['participant_child', 'participant_teen']))
                <div class="mb-5 p-4 rounded-lg" style="background:#f0f7ee;border:1px solid #a8d89a;">
                    <p class="text-lg" style="font-family:'EB Garamond',serif;">
                        Hallo <strong>{{ $link->name }}</strong>! 👋
                    </p>
                    <p class="mt-2 text-stone-700">
                        Das Abenteuer ist vorbei – aber deine Meinung zählt noch!
                        Erzähl uns kurz, wie es dir gefallen hat. Das hilft uns,
                        das nächste Abenteuer noch besser zu machen.
                    </p>
                </div>
            @elseif($link->target_type === 'teamer')
                <div class="mb-5 p-4 rounded-lg" style="background:#f0f7ee;border:1px solid #a8d89a;">
                    <p class="text-lg" style="font-family:'EB Garamond',serif;">
                        Hallo <strong>{{ $link->name }}</strong>!
                    </p>
                    <p class="mt-2 text-stone-700">
                        Als Teil des Teams ist uns dein Feedback besonders wichtig.
                        Bitte nimm dir einen Moment und teile deine Erfahrungen mit uns.
                    </p>
                </div>
            @else
                <div class="mb-5 p-4 rounded-lg" style="background:#f0f7ee;border:1px solid #a8d89a;">
                    <p class="text-lg" style="font-family:'EB Garamond',serif;">
                        Guten Tag, <strong>{{ $link->name }}</strong>!
                    </p>
                    <p class="mt-2 text-stone-700">
                        Wir freuen uns über Ihre Rückmeldung zur Veranstaltung.
                        Ihre Antworten helfen uns, unser Angebot für Kinder und
                        Jugendliche stetig zu verbessern.
                    </p>
                </div>
            @endif

            {{-- Info-Box: Dauer, Fragen, Freiwilligkeit --}}
            <div class="mb-6 text-sm text-stone-600 space-y-1">
                <p>📋 <strong>{{ $link->survey->template->questions->count() }} Fragen</strong> – dauert nur wenige Minuten</p>
                <p>🔒 Deine Antworten werden nur intern ausgewertet</p>
                <p>✋ Das Ausfüllen ist freiwillig – du kannst Fragen überspringen</p>
                @if($link->survey->closes_at)
                    <p>⏰ Der Fragebogen ist bis <strong>{{ $link->survey->closes_at->format('d.m.Y') }}</strong> ausfüllbar</p>
                @endif
            </div>

            {{-- Datenschutz-Hinweis --}}
            <p class="text-xs text-stone-400 mb-6">
                Weitere Informationen findest du in unserer
                <a href="{{ route('datenschutz') }}" class="underline hover:text-waldritter">Datenschutzerklärung</a>.
            </p>

            {{-- Start-Button --}}
            <a href="{{ route('survey.show', ['token' => $link->token]) }}"
               class="survey-btn-primary block text-center py-4 rounded-xl no-underline"
               style="text-decoration:none;">
                @if(in_array($link->target_type, ['participant_child', 'participant_teen']))
                    🛡 Los geht's!
                @else
                    Zum Fragebogen
                @endif
            </a>

        </div>
    </div>
</x-public-layout>
