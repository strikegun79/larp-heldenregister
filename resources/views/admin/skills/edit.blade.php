<x-app-layout>
    {{-- Vollseite für „Fertigkeit bearbeiten / Neue Fertigkeit“ (ohne JavaScript bzw. direkter Aufruf).
         Normalerweise öffnet die Liste den Inspektor bzw. das Modal mit admin.skills._form. --}}
    <div class="wr-v1 py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-5">
                <a href="{{ route('admin.skills.index') }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zu den Fertigkeiten
                </a>
                <span class="wr-eyebrow">Fertigkeiten</span>
                <h1 class="wr-heading break-words">{{ $skill->exists ? $skill->name : 'Neue Fertigkeit' }}</h1>
            </div>

            <div class="wr-card p-4 sm:p-6">
                @include('admin.skills._form')

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit" form="skill-form" class="wr-btn wr-btn--primary">
                        <i class="save icon m-0" aria-hidden="true"></i> Speichern
                    </button>
                    <a href="{{ route('admin.skills.index') }}" class="wr-btn wr-btn--secondary">Abbrechen</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
