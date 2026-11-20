<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Seitenkopf --}}
            <div class="mb-5">
                <a href="{{ route('players.index') }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zu deinen Spielern
                </a>
                <span class="wr-eyebrow">Spieler</span>
                <h1 class="wr-heading break-words hyphens-auto">{{ $player->full_name }}</h1>
                @if ($player->age !== null)
                    <span class="wr-pill"><i class="birthday cake icon" aria-hidden="true"></i>{{ $player->age }} Jahre</span>
                @endif
            </div>

            <div class="wr-card p-4 sm:p-6">
                @include('players._detail')
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
