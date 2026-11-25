<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            {{ $template->exists ? 'Vorlage bearbeiten' : 'Neue Vorlage' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">

                <form method="POST"
                      action="{{ $template->exists
                          ? route('admin.surveys.templates.update', $template)
                          : route('admin.surveys.templates.store') }}">
                    @csrf
                    @if($template->exists) @method('PUT') @endif

                    <div class="ui form">

                        <div class="field {{ $errors->has('name') ? 'error' : '' }}">
                            <label for="name">Name der Vorlage</label>
                            <input type="text" name="name" id="name"
                                   value="{{ old('name', $template->name) }}"
                                   placeholder="z. B. Teilnehmer-Feedback Kinder" required>
                            @error('name') <div class="ui pointing red basic label">{{ $message }}</div> @enderror
                        </div>

                        <div class="field {{ $errors->has('target_group') ? 'error' : '' }} mt-4">
                            <label for="target_group">Zielgruppe</label>
                            <select name="target_group" id="target_group" class="ui dropdown" required>
                                @foreach(\App\Models\SurveyTemplate::TARGET_GROUPS as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('target_group', $template->target_group) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('target_group') <div class="ui pointing red basic label">{{ $message }}</div> @enderror
                        </div>

                        <div class="field mt-4">
                            <label for="description">Beschreibung (intern)</label>
                            <textarea name="description" id="description" rows="2">{{ old('description', $template->description) }}</textarea>
                        </div>

                        <div class="field mt-2">
                            <div class="ui checkbox">
                                <input type="checkbox" name="active" id="active" value="1"
                                    {{ old('active', $template->active ?? true) ? 'checked' : '' }}>
                                <label for="active">Vorlage aktiv (für neue Umfragen auswählbar)</label>
                            </div>
                        </div>

                        {{-- Issue #46: Bereiche als Seiten --}}
                        <div class="field mt-2">
                            <div class="ui checkbox">
                                <input type="checkbox" name="section_pages" id="section_pages" value="1"
                                       aria-describedby="section_pages_hint"
                                    {{ old('section_pages', $template->section_pages ?? false) ? 'checked' : '' }}>
                                <label for="section_pages">Bereiche als einzelne Seiten anzeigen</label>
                            </div>
                            <p id="section_pages_hint" class="text-xs text-stone-500 mt-1">
                                Jeder Bereich wird eine eigene Seite mit „Weiter“ und „Zurück“ – gerade für Jüngere übersichtlicher.
                            </p>
                        </div>

                    </div>

                    {{-- Frageneditor --}}
                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-waldritter">Fragen und Bereiche</h3>
                            <span class="flex gap-2">
                                <button type="button" onclick="addQuestion('section-template')" class="ui small button">
                                    + Bereich hinzufügen
                                </button>
                                <button type="button" onclick="addQuestion()" class="ui secondary small button">
                                    + Frage hinzufügen
                                </button>
                            </span>
                        </div>

                        <div id="questions-list" class="space-y-3">
                            @foreach($questions as $i => $q)
                                <div class="question-item border rounded-lg p-4 {{ $q->isSection() ? 'border-amber-300 bg-amber-50' : 'border-stone-200 bg-stone-50' }}"
                                     data-index="{{ $i }}">
                                    @include('admin.surveys.templates._question_row', ['i' => $i, 'q' => $q])
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="ui primary button">
                            {{ $template->exists ? 'Speichern' : 'Vorlage erstellen' }}
                        </button>
                        <a href="{{ route('admin.surveys.templates.index') }}" class="ui button">Abbrechen</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Vorlage für neue Fragen: dasselbe Markup wie bestehende Fragen (Platzhalter __INDEX__) --}}
    <template id="question-template">
        <div class="question-item border border-stone-200 rounded-lg p-4 bg-stone-50">
            @include('admin.surveys.templates._question_row', ['i' => '__INDEX__', 'q' => null])
        </div>
    </template>

    {{-- Issue #46: Vorlage für neue Bereiche --}}
    <template id="section-template">
        <div class="question-item border border-amber-300 rounded-lg p-4 bg-amber-50">
            @include('admin.surveys.templates._question_row', ['i' => '__INDEX__', 'q' => null, 'newType' => 'section'])
        </div>
    </template>

    <script>
    let qIdx = {{ $questions->count() }};

    function addQuestion(templateId = 'question-template') {
        const html = document.getElementById(templateId).innerHTML.replaceAll('__INDEX__', qIdx++);
        const list = document.getElementById('questions-list');
        list.insertAdjacentHTML('beforeend', html);
        list.lastElementChild?.querySelector('input[type="text"]')?.focus();
    }

    // Issue #46: Fragen und Bereiche verschieben; die Reihenfolge im Formular wird gespeichert.
    document.getElementById('questions-list').addEventListener('click', (e) => {
        const button = e.target.closest('[data-move]');
        if (!button) return;
        const item = button.closest('.question-item');
        if (button.dataset.move === 'up' && item.previousElementSibling) {
            item.parentNode.insertBefore(item, item.previousElementSibling);
        } else if (button.dataset.move === 'down' && item.nextElementSibling) {
            item.parentNode.insertBefore(item.nextElementSibling, item);
        }
        button.focus();
    });

    // Issue #24: Typabhängige Einstellungen ein-/ausblenden.
    document.getElementById('questions-list').addEventListener('change', (e) => {
        if (!e.target.matches('[data-question-type]')) return;
        const item = e.target.closest('.question-item');
        item.querySelectorAll('[data-type-options]').forEach((el) => {
            el.hidden = el.dataset.typeOptions !== e.target.value;
        });
    });
    </script>
</x-app-layout>
