<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Neue Umfrage erstellen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">

                <form method="POST" action="{{ route('admin.surveys.store') }}">
                    @csrf

                    <div class="ui form">

                        <div class="field {{ $errors->has('adventure_id') ? 'error' : '' }}">
                            <label for="adventure_id">Veranstaltung</label>
                            <select name="adventure_id" id="adventure_id" class="ui search dropdown" required>
                                <option value="">– Veranstaltung wählen –</option>
                                @foreach($adventures as $adventure)
                                    <option value="{{ $adventure->id }}"
                                        {{ old('adventure_id') == $adventure->id ? 'selected' : '' }}>
                                        {{ $adventure->name }}
                                        ({{ $adventure->start_at?->format('d.m.Y') ?? '?' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('adventure_id')
                                <div class="ui pointing red basic label">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="field {{ $errors->has('survey_template_id') ? 'error' : '' }} mt-4">
                            <label for="survey_template_id">Fragebogen-Vorlage</label>
                            <select name="survey_template_id" id="survey_template_id" class="ui dropdown" required>
                                <option value="">– Vorlage wählen –</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}"
                                        {{ old('survey_template_id') == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                        ({{ $template->target_group_label }})
                                    </option>
                                @endforeach
                            </select>
                            @error('survey_template_id')
                                <div class="ui pointing red basic label">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="field {{ $errors->has('title') ? 'error' : '' }} mt-4">
                            <label for="title">Titel der Umfrage</label>
                            <input type="text"
                                   name="title"
                                   id="title"
                                   value="{{ old('title') }}"
                                   placeholder="z. B. Teilnehmer-Feedback Sommerlager 2026"
                                   required>
                            @error('title')
                                <div class="ui pointing red basic label">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="field {{ $errors->has('closes_at') ? 'error' : '' }} mt-4">
                            <label for="closes_at">Link läuft ab am (optional)</label>
                            <input type="date"
                                   name="closes_at"
                                   id="closes_at"
                                   value="{{ old('closes_at') }}">
                            @error('closes_at')
                                <div class="ui pointing red basic label">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="ui primary button">Umfrage erstellen</button>
                        <a href="{{ route('admin.surveys.index') }}" class="ui button">Abbrechen</a>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>
