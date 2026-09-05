<x-public-layout title="Fragebogen nicht verfügbar">
    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="survey-wrap">
        <div class="survey-card text-center py-10">

            <div class="text-5xl mb-4">
                @if($reason === 'completed') ✅ @else ⏰ @endif
            </div>

            @if($reason === 'completed')
                <h1 class="text-xl font-semibold text-waldritter mb-3"
                    style="font-family:'EB Garamond',serif;">
                    Du hast diesen Fragebogen bereits ausgefüllt
                </h1>
                <p class="text-stone-600">
                    Vielen Dank für dein Feedback! Du hast diesen Fragebogen
                    bereits abgeschickt – eine erneute Abgabe ist nicht möglich.
                </p>
            @else
                <h1 class="text-xl font-semibold text-waldritter mb-3"
                    style="font-family:'EB Garamond',serif;">
                    Dieser Link ist leider abgelaufen
                </h1>
                <p class="text-stone-600">
                    Der Fragebogen war nur für begrenzte Zeit ausfüllbar.
                    Wende dich bei Fragen an die Veranstalter.
                </p>
            @endif

            <a href="/"
               class="survey-btn-secondary inline-block mt-8 px-6 py-3 rounded-lg no-underline"
               style="text-decoration:none;">
                Zur Startseite
            </a>

        </div>
    </div>
</x-public-layout>
