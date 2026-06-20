<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $hero->character_name ?? 'Held' }}</h2>
        @if ($hero->player)
            <p class="text-sm text-stone-500 mt-0.5">{{ $hero->player->full_name }}</p>
        @endif
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4 sm:p-6">
                @include('heroes._detail')
            </div>

            {{-- UI-38: Aktions-Footer (auf Mobile sticky, auf Desktop inline). --}}
            <x-mobile.sticky-footer class="mt-4">
                @can('heldenregister.edit')
                    <a href="{{ route('heroes.edit', $hero) }}"
                       data-modal-url="{{ route('heroes.edit', $hero) }}"
                       class="ui button">Bearbeiten</a>
                @endcan
                <a href="{{ route('heroes.index') }}" class="ui button">&larr; Zurück zum Register</a>
            </x-mobile.sticky-footer>
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
