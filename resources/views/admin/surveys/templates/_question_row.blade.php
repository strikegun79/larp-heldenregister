@use('App\Models\SurveyTemplateQuestion')
@php
    // $q ist bei neuen Fragen (JS-Vorlage) null; $i ist dann der Platzhalter „__INDEX__“.
    $type = old("questions.{$i}.type", $q->type ?? 'rating');
    $hasOld = old("questions.{$i}.question_text") !== null;

    // Issue #24: Einstellungen der Typen „Optionen“ und „Smileys“ (Vorauswahl, falls leer).
    $choicesText = old("questions.{$i}.choices", implode("\n", $q?->type === 'choice' ? $q->choices() : SurveyTemplateQuestion::DEFAULT_CHOICES));
    $multiple = $hasOld ? (bool) old("questions.{$i}.multiple") : ($q?->type === 'choice' && $q->allowsMultiple());
    $freeText = $hasOld ? (bool) old("questions.{$i}.free_text") : ($q?->type === 'choice' ? $q->hasFreeText() : true);
    $smileyLabels = $q?->type === 'smiley' ? $q->smileyLabels() : SurveyTemplateQuestion::DEFAULT_SMILEY_LABELS;
@endphp
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
</div>
