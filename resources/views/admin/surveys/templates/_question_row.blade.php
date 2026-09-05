<div class="flex gap-3 flex-wrap items-start">
    <div class="flex-1">
        <label class="text-xs text-stone-500">Fragetext</label>
        <input type="text"
               name="questions[{{ $i }}][question_text]"
               class="ui input w-full"
               value="{{ old("questions.{$i}.question_text", $q->question_text ?? '') }}"
               required>
    </div>
    <div>
        <label class="text-xs text-stone-500">Typ</label>
        <select name="questions[{{ $i }}][type]" class="ui dropdown">
            @foreach(\App\Models\SurveyTemplateQuestion::TYPES as $value => $label)
                <option value="{{ $value }}"
                    {{ old("questions.{$i}.type", $q->type ?? 'rating') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mt-4">
        <div class="ui checkbox">
            <input type="checkbox"
                   name="questions[{{ $i }}][required]"
                   value="1"
                   {{ old("questions.{$i}.required", $q->required ?? false) ? 'checked' : '' }}>
            <label>Pflicht</label>
        </div>
    </div>
    <button type="button"
            onclick="this.closest('.question-item').remove()"
            class="ui red icon button mt-4" title="Frage entfernen">
        <i class="trash icon"></i>
    </button>
</div>
