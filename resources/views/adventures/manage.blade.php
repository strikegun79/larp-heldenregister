<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Verwaltung: {{ $adventure->name }}
            </h2>
            <a href="{{ route('adventures.manage-index') }}" class="ui small button">
                &larr; Verwaltungsübersicht
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                @include('adventures._manage')
            </div>

            {{-- Aktionen für Vollseite (im Modal übernimmt data-modal-actions diese Rolle) --}}
            <div class="mt-4 flex items-center gap-3">
                <button type="submit" form="manage-adventure-form" class="ui primary button">
                    <i class="save icon"></i> Speichern
                </button>
                <a href="{{ route('adventures.manage-index') }}" class="ui button">&larr; Zurück</a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.menu .item[data-tab]').tab();
        });
    </script>
    @endpush
</x-app-layout>
