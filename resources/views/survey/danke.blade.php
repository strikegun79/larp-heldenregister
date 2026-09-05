<x-public-layout title="Danke für dein Feedback!">
    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="survey-wrap">
        <div class="survey-card survey-danke-wrap">

            <div class="survey-badge">
                <div class="survey-badge-icon">🏆</div>
            </div>

            @if(in_array($link->target_type, ['participant_child', 'participant_teen']))
                <h1 class="survey-danke-headline">Danke, tapfere/r Heldin/Held!</h1>
                <p class="text-lg text-stone-700 mt-3" style="font-family:'EB Garamond',serif;">
                    Deine Worte helfen uns, das nächste Abenteuer noch besser zu machen.
                    Du hast deine Mission erfüllt! 🛡
                </p>
            @elseif($link->target_type === 'teamer')
                <h1 class="survey-danke-headline">Vielen Dank!</h1>
                <p class="text-lg text-stone-700 mt-3" style="font-family:'EB Garamond',serif;">
                    Dein Feedback ist wertvoll für unser Team. Wir freuen uns auf
                    das nächste gemeinsame Abenteuer!
                </p>
            @else
                <h1 class="survey-danke-headline">Herzlichen Dank!</h1>
                <p class="text-lg text-stone-700 mt-3" style="font-family:'EB Garamond',serif;">
                    Ihre Rückmeldung hilft uns, unser Angebot für Kinder und Jugendliche
                    kontinuierlich zu verbessern.
                </p>
            @endif

            <p class="text-sm text-stone-500 mt-4">
                Veranstaltung: <strong>{{ $link->survey->adventure->name }}</strong>
            </p>

            <a href="/"
               class="survey-btn-secondary inline-block mt-6 px-6 py-3 rounded-lg no-underline"
               style="text-decoration:none;">
                Zur Startseite
            </a>

        </div>
    </div>
</x-public-layout>
