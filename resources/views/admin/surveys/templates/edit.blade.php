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

                    </div>

                    {{-- Frageneditor --}}
                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-waldritter">Fragen</h3>
                            <button type="button" onclick="addQuestion()" class="ui secondary small button">
                                + Frage hinzufügen
                            </button>
                        </div>

                        <div id="questions-list" class="space-y-3">
                            @foreach($questions as $i => $q)
                                <div class="question-item border border-stone-200 rounded-lg p-4 bg-stone-50"
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

    <script>
    let qIdx = {{ $questions->count() }};

    function addQuestion() {
        const i = qIdx++;
        const types = {
            rating: 'Bewertung (1–10)',
            text:   'Freitext',
            yes_no: 'Ja / Nein',
        };
        const opts = Object.entries(types)
            .map(([v, l]) => `<option value="${v}">${l}</option>`).join('');

        const html = `
            <div class="question-item border border-stone-200 rounded-lg p-4 bg-stone-50">
                <div class="flex gap-3 flex-wrap items-start">
                    <div class="flex-1">
                        <label class="text-xs text-stone-500">Fragetext</label>
                        <input type="text"
                               name="questions[${i}][question_text]"
                               class="ui input w-full"
                               placeholder="Wie hat dir … gefallen?"
                               required>
                    </div>
                    <div>
                        <label class="text-xs text-stone-500">Typ</label>
                        <select name="questions[${i}][type]" class="ui dropdown">${opts}</select>
                    </div>
                    <div class="mt-4">
                        <div class="ui checkbox">
                            <input type="checkbox" name="questions[${i}][required]" value="1">
                            <label>Pflicht</label>
                        </div>
                    </div>
                    <button type="button"
                            onclick="this.closest('.question-item').remove()"
                            class="ui red icon button mt-4" title="Löschen">
                        <i class="trash icon"></i>
                    </button>
                </div>
            </div>`;
        document.getElementById('questions-list').insertAdjacentHTML('beforeend', html);
    }
    </script>
</x-app-layout>
