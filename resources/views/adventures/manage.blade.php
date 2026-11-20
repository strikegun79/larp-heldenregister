<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-5">
                <a href="{{ route('adventures.manage-index') }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zur Verwaltungsübersicht
                </a>
                <span class="wr-eyebrow">Abenteuer verwalten</span>
                <h1 class="wr-heading break-words">{{ $adventure->name }}</h1>
            </div>

            <div class="wr-card p-4 sm:p-6 adv-manage-page">
                @include('adventures._manage')
            </div>

            {{-- Aktionen für Vollseite: auf Mobile sticky unten, auf Desktop inline --}}
            <x-mobile.sticky-footer class="mt-4">
                {{-- Mobile: Accordion-Form (manage-adventure-form-mobile); Wrapper trägt die Sichtbarkeitsklasse --}}
                <div class="sm:hidden">
                    <button type="submit" form="manage-adventure-form-mobile" class="wr-btn wr-btn--primary">
                        <i class="save icon m-0" aria-hidden="true"></i> Speichern
                    </button>
                </div>
                {{-- Desktop: Tab-Form (manage-adventure-form) --}}
                <div class="hidden sm:block">
                    <button type="submit" form="manage-adventure-form" class="wr-btn wr-btn--primary">
                        <i class="save icon m-0" aria-hidden="true"></i> Speichern
                    </button>
                </div>
                <a href="{{ route('adventures.show', $adventure) }}" class="wr-btn wr-btn--secondary">Zur Detailansicht</a>

                {{-- Löschen: nur ohne Abhängigkeiten; sonst sichtbarer Grund statt Tooltip --}}
                @if ($deletionBlocker === null)
                    <form method="POST" action="{{ route('adventures.destroy', $adventure) }}" class="inline sm:ml-auto"
                          data-confirm="Abenteuer „{{ $adventure->name }}“ wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="wr-btn wr-btn--danger">
                            <i class="trash icon m-0" aria-hidden="true"></i> Löschen
                        </button>
                    </form>
                @else
                    <span class="sm:ml-auto inline-flex items-center gap-1 text-sm text-wr-ink-muted">
                        <i class="lock icon m-0" aria-hidden="true"></i> {{ $deletionBlocker }}
                    </span>
                @endif
            </x-mobile.sticky-footer>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.menu .item[data-tab]').tab();
            $('.ui.floating.dropdown').dropdown();
        });
    </script>
    @endpush
</x-app-layout>
