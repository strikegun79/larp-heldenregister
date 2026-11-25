{{-- Vorschau einer Umfrage/Vorlage: deutlicher Hinweis, Ansicht wählen, zurück. Erwartet: $preview --}}
<div class="survey-card mb-4 p-4" role="note" style="border-color:#b45309;background:#fffbeb;">
    <p class="font-semibold text-amber-900">
        👁 Vorschau – deine Antworten werden nicht gespeichert.
    </p>
    @isset($preview['url'])
        <p class="text-sm text-amber-900 mt-2">
            Ansicht für:
            @foreach(\App\Http\Controllers\Admin\SurveyPreviewController::previewGroups() as $value => $label)
                @if($preview['as'] === $value)
                    <strong aria-current="true">{{ $label }}</strong>
                @else
                    <a href="{{ $preview['url'] }}?as={{ $value }}" class="underline">{{ $label }}</a>
                @endif
                @unless($loop->last) · @endunless
            @endforeach
        </p>
    @endisset
    <p class="text-sm mt-2">
        @isset($preview['again'])
            <a href="{{ $preview['again'] }}" class="underline text-amber-900">Vorschau erneut öffnen</a> ·
        @endisset
        <a href="{{ $preview['back'] }}" class="underline text-amber-900">Zurück zur Verwaltung</a>
    </p>
</div>
