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
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6 adv-manage-page">
                @include('adventures._manage')
            </div>

            {{-- Aktionen für Vollseite: auf Mobile sticky unten, auf Desktop inline --}}
            <x-mobile.sticky-footer class="mt-4">
                {{-- Mobile: Accordion-Form (manage-adventure-form-mobile) --}}
                {{-- Wrapper trägt die Sichtbarkeitsklasse, damit .ui.button sie nicht überschreibt --}}
                <div class="sm:hidden">
                    <button type="submit" form="manage-adventure-form-mobile" class="ui primary button">
                        <i class="save icon"></i> Speichern
                    </button>
                </div>
                {{-- Desktop: Tab-Form (manage-adventure-form) --}}
                <div class="hidden sm:block">
                    <button type="submit" form="manage-adventure-form" class="ui primary button">
                        <i class="save icon"></i> Speichern
                    </button>
                </div>
                <a href="{{ route('adventures.show', $adventure) }}" class="ui button">&larr; Zur Detailansicht</a>

                {{-- Löschen: nur wenn keine Abhängigkeiten vorhanden --}}
                @if($deletionBlocker === null)
                    <form method="POST" action="{{ route('adventures.destroy', $adventure) }}"
                          id="delete-adventure-form" class="inline">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="button" class="ui red basic button ml-auto"
                            onclick="confirmDeleteAdventure()">
                        <i class="trash icon"></i> Löschen
                    </button>
                @else
                    <span data-tooltip="{{ $deletionBlocker }}"
                          data-position="top center"
                          data-variation="mini"
                          class="inline-block cursor-not-allowed ml-auto">
                        <button type="button" class="ui red basic button disabled"
                                style="pointer-events: none;">
                            <i class="trash icon"></i> Löschen
                        </button>
                    </span>
                @endif
            </x-mobile.sticky-footer>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.menu .item[data-tab]').tab();
        });

        function confirmDeleteAdventure() {
            if (confirm('Abenteuer „{{ addslashes($adventure->name) }}" wirklich löschen?\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
                document.getElementById('delete-adventure-form').submit();
            }
        }
    </script>
    @endpush
</x-app-layout>
