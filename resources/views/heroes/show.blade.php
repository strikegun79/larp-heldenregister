<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Seitenkopf --}}
            <div class="mb-5">
                @can('heldenregister.view')
                    <a href="{{ route('heroes.index') }}"
                       class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                        <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zum Register
                    </a>
                @else
                    <a href="{{ route('players.index') }}"
                       class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                        <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zu meinen Spielern
                    </a>
                @endcan
                <span class="wr-eyebrow">Heldenbuch</span>
                <h1 class="wr-heading break-words hyphens-auto">{{ $hero->character_name ?? 'Held' }}</h1>
                @if ($hero->player)
                    <p class="wr-intro mb-3">gespielt von {{ $hero->player->full_name }}</p>
                @endif
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($hero->classes as $heroClass)
                        <x-wr.class-badge :hero-class="$heroClass" />
                    @endforeach
                </div>
            </div>

            <div class="wr-card p-4 sm:p-6">
                @include('heroes._detail')
            </div>

            {{-- UI-38: Aktions-Footer (auf Mobile sticky, auf Desktop inline). Zurück-Link steht oben. --}}
            @can('heldenregister.edit')
                <x-mobile.sticky-footer class="mt-4">
                    <a href="{{ route('heroes.edit', $hero) }}"
                       data-modal-url="{{ route('heroes.edit', $hero) }}"
                       class="wr-btn wr-btn--primary">Bearbeiten</a>
                </x-mobile.sticky-footer>
            @endcan
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
