<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $adventure->name }}</h2>
                <p class="text-sm text-stone-500 mt-0.5">
                    {{ optional($adventure->start_at)->format('d.m.Y') }}
                    @if ($adventure->location)
                        · {{ $adventure->location->titel }}
                    @endif
                    @if ($adventure->status)
                        · <span class="inline-block rounded px-2 py-0.5 text-xs"
                                style="background: {{ $adventure->status->color }}33;">{{ $adventure->status->description }}</span>
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4 sm:p-6 adv-detail-page">
                @include('adventures._detail')
            </div>

            {{-- UI-38: Aktions-Footer (auf Mobile sticky, auf Desktop inline).
                 Spiegelt data-modal-actions aus _detail, aber angepasst für Vollseite:
                 „Verwalten" navigiert direkt (kein Modal), Buchungs-Links öffnen Modal. --}}
            <x-mobile.sticky-footer class="mt-4">
                @can('adventure.book')
                    @if ($adventure->registrationOpen())
                        <a href="{{ route('adventures.bookings.create', $adventure) }}"
                           data-modal-stack="{{ route('adventures.bookings.create', $adventure) }}"
                           class="ui primary button">Anmelden</a>
                        <a href="{{ route('adventures.group-bookings.create', $adventure) }}"
                           data-modal-stack="{{ route('adventures.group-bookings.create', $adventure) }}"
                           class="ui button">Gruppe anmelden</a>
                        <a href="{{ route('adventures.bookings.create-guest', $adventure) }}"
                           data-modal-stack="{{ route('adventures.bookings.create-guest', $adventure) }}"
                           class="ui button">Gast anmelden</a>
                    @endif
                @endcan
                @if (auth()->user()->hasAnyRole('teamer', 'lehrmeister') && $myTeamerSignup === null)
                    <a href="{{ route('adventures.teamer.create', $adventure) }}"
                       data-modal-stack="{{ route('adventures.teamer.create', $adventure) }}"
                       class="ui teal button">Teamer-Anmeldung</a>
                @endif
                @can('events.edit')
                    <a href="{{ route('adventures.manage', $adventure) }}" class="ui button">Verwalten</a>
                @endcan
                <a href="{{ route('adventures.index') }}" class="ui button">&larr; Zurück</a>
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
