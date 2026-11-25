@use('App\Models\SurveyTemplateQuestion')
@php
    // $q ist bei neuen Fragen (JS-Vorlage) null; $i ist dann der Platzhalter „__INDEX__“.
    // Issue #46: $newType = 'section' für die JS-Vorlage eines neuen Bereichs.
    $type = old("questions.{$i}.type", $q->type ?? ($newType ?? 'rating'));
    $hasOld = old("questions.{$i}.question_text") !== null;

    // Issue #24: Einstellungen der Typen „Optionen“ und „Smileys“ (Vorauswahl, falls leer).
    $choicesText = old("questions.{$i}.choices", implode("\n", $q?->type === 'choice' ? $q->choices() : SurveyTemplateQuestion::DEFAULT_CHOICES));
    $multiple = $hasOld ? (bool) old("questions.{$i}.multiple") : ($q?->type === 'choice' && $q->allowsMultiple());
    $freeText = $hasOld ? (bool) old("questions.{$i}.free_text") : ($q?->type === 'choice' ? $q->hasFreeText() : true);
    $smileyLabels = $q?->type === 'smiley' ? $q->smileyLabels() : SurveyTemplateQuestion::DEFAULT_SMILEY_LABELS;
    // Issue #46: Smiley-Zusatzantwort ohne Smiley.
    $naOption = $hasOld ? (bool) old("questions.{$i}.na_option") : ($q?->type === 'smiley' && $q->hasNaOption());
    $naLabel = old("questions.{$i}.na_label", $q?->type === 'smiley' ? $q->naLabel() : SurveyTemplateQuestion::DEFAULT_NA_LABEL);
@endphp
{{-- Issue #35: ID mitschicken, damit die Frage beim Speichern aktualisiert statt neu angelegt wird --}}
@if($q?->id)
    <input type="hidden" name="questions[{{ $i }}][id]" value="{{ $q->id }}">
@endif

{{-- Issue #46: Reihenfolge verschieben (Fragen und Bereiche) --}}
@php($itemLabel = $type === SurveyTemplateQuestion::SECTION ? 'Bereich' : 'Frage')
<div class="flex justify-end gap-1 -mt-1 mb-1">
    <button type="button" class="ui mini basic icon button" data-move="up"
            title="{{ $itemLabel }} nach oben" aria-label="{{ $itemLabel }} nach oben verschieben">
        <i class="arrow up icon"></i>
    </button>
    <button type="button" class="ui mini basic icon button" data-move="down"
            title="{{ $itemLabel }} nach unten" aria-label="{{ $itemLabel }} nach unten verschieben">
        <i class="arrow down icon"></i>
    </button>
</div>

@if($type === SurveyTemplateQuestion::SECTION)
    {{-- Issue #46: Bereich – Titel und Untertext, beim Ausfüllen automatisch nummeriert --}}
    <input type="hidden" name="questions[{{ $i }}][type]" value="{{ SurveyTemplateQuestion::SECTION }}">
    <div class="flex gap-3 flex-wrap items-start">
        <div class="flex-1 ui form">
            <div class="field">
                <label class="text-xs text-stone-500" for="q_{{ $i }}_text">Bereich (Titel, wird automatisch nummeriert)</label>
                <input type="text"
                       id="q_{{ $i }}_text"
                       name="questions[{{ $i }}][question_text]"
                       value="{{ old("questions.{$i}.question_text", $q->question_text ?? '') }}"
                       placeholder="z. B. Über dich"
                       maxlength="1000"
                       required>
            </div>
            <div class="field">
                <label class="text-xs text-stone-500" for="q_{{ $i }}_subtitle">Untertext (optional)</label>
                <input type="text"
                       id="q_{{ $i }}_subtitle"
                       name="questions[{{ $i }}][subtitle]"
                       value="{{ old("questions.{$i}.subtitle", $q?->subtitle() ?? '') }}"
                       placeholder="z. B. Damit wir unsere Teilnehmer besser kennenlernen."
                       maxlength="500">
            </div>
        </div>
        <button type="button"
                onclick="this.closest('.question-item').remove()"
                class="ui red icon button mt-5" title="Bereich entfernen" aria-label="Bereich entfernen">
            <i class="trash icon"></i>
        </button>
    </div>
@else
<div class="flex gap-3 flex-wrap items-start">
    <div class="flex-1">
        <label class="text-xs text-stone-500" for="q_{{ $i }}_text">Fragetext</label>
        <input type="text"
               id="q_{{ $i }}_text"
               name="questions[{{ $i }}][question_text]"
               class="ui input w-full"
               value="{{ old("questions.{$i}.question_text", $q->question_text ?? '') }}"
               placeholder="Wie hat dir … gefallen?"
               required>
    </div>
    <div>
        <label class="text-xs text-stone-500" for="q_{{ $i }}_type">Typ</label>
        <select name="questions[{{ $i }}][type]" id="q_{{ $i }}_type" class="ui dropdown" data-question-type>
            @foreach(SurveyTemplateQuestion::TYPES as $value => $label)
                <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="mt-4">
        <div class="ui checkbox">
            <input type="checkbox"
                   id="q_{{ $i }}_required"
                   name="questions[{{ $i }}][required]"
                   value="1"
                   {{ old("questions.{$i}.required", $q->required ?? false) ? 'checked' : '' }}>
            <label for="q_{{ $i }}_required">Pflicht</label>
        </div>
    </div>
    <button type="button"
            onclick="this.closest('.question-item').remove()"
            class="ui red icon button mt-4" title="Frage entfernen" aria-label="Frage entfernen">
        <i class="trash icon"></i>
    </button>
</div>

{{-- Issue #24: Einstellungen „Optionen mit Freitext“ --}}
<div class="mt-3 ui form" data-type-options="choice" @if($type !== 'choice') hidden @endif>
    <div class="field">
        <label class="text-xs text-stone-500" for="q_{{ $i }}_choices">Antwortoptionen (eine pro Zeile)</label>
        <textarea name="questions[{{ $i }}][choices]" id="q_{{ $i }}_choices" rows="3">{{ $choicesText }}</textarea>
    </div>
    <div class="flex flex-wrap gap-6">
        <div class="ui checkbox">
            <input type="checkbox" id="q_{{ $i }}_multiple" name="questions[{{ $i }}][multiple]" value="1" {{ $multiple ? 'checked' : '' }}>
            <label for="q_{{ $i }}_multiple">Mehrfachauswahl erlaubt</label>
        </div>
        <div class="ui checkbox">
            <input type="checkbox" id="q_{{ $i }}_free_text" name="questions[{{ $i }}][free_text]" value="1" {{ $freeText ? 'checked' : '' }}>
            <label for="q_{{ $i }}_free_text">Freitextfeld für eine eigene Nachricht am Ende</label>
        </div>
    </div>
</div>

{{-- Issue #24: Einstellungen „Smileys (1–5)“ --}}
<div class="mt-3 ui form" data-type-options="smiley" @if($type !== 'smiley') hidden @endif>
    <p class="text-xs text-stone-500 mb-1">Antworttexte der Smileys (anpassbar)</p>
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
        @foreach(SurveyTemplateQuestion::SMILEYS as $points => $smiley)
            <div class="field">
                <label class="text-xs text-stone-500" for="q_{{ $i }}_label_{{ $points }}">{{ $smiley }} {{ $points }} {{ $points === 1 ? 'Punkt' : 'Punkte' }}</label>
                <input type="text" maxlength="100"
                       id="q_{{ $i }}_label_{{ $points }}"
                       name="questions[{{ $i }}][labels][{{ $points }}]"
                       value="{{ old("questions.{$i}.labels.{$points}", $smileyLabels[$points]) }}">
            </div>
        @endforeach
    </div>
    {{-- Issue #46: Zusatzantwort ohne Smiley, zählt nicht in Durchschnitt und Verteilung --}}
    <div class="flex flex-wrap items-center gap-3 mt-2">
        <div class="ui checkbox">
            <input type="checkbox" id="q_{{ $i }}_na_option" name="questions[{{ $i }}][na_option]" value="1" {{ $naOption ? 'checked' : '' }}>
            <label for="q_{{ $i }}_na_option">Zusätzliche Antwort ohne Smiley</label>
        </div>
        <div class="field flex-1 min-w-[12rem] mb-0">
            <label class="sr-only" for="q_{{ $i }}_na_label">Text der Zusatzantwort</label>
            <input type="text" maxlength="100"
                   id="q_{{ $i }}_na_label"
                   name="questions[{{ $i }}][na_label]"
                   value="{{ $naLabel }}">
        </div>
    </div>
    <p class="text-xs text-stone-500 mt-1 mb-0">Wird als 0 gespeichert und zählt nicht im Durchschnitt mit.</p>
</div>

@endif

{{-- Issue #35: Hinweis bei Fragen mit vorhandenen Antworten --}}
@if(($q->answers_count ?? 0) > 0)
    <p class="text-xs text-stone-500 mt-2 mb-0">
        <i class="info circle icon"></i>
        {{ $q->answers_count }} {{ $q->answers_count === 1 ? 'Antwort' : 'Antworten' }} vorhanden.
        Textänderungen gelten auch für bestehende Antworten. Ein anderer Typ legt eine neue Frage an,
        und Entfernen archiviert die Frage; die bisherigen Antworten bleiben in der Auswertung erhalten.
    </p>
@endif
