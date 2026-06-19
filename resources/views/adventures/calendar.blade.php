<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Kommende Events</h2>
            <a href="{{ route('adventures.index') }}">
                    <x-primary-button>Listenansicht</x-primary-button>
                </a>

        </div>
    </x-slot>

    @php($monthNames = [1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'])

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @forelse ($events as $yearMonth => $monthEvents)
                @php([$year, $month] = explode('-', $yearMonth))
                <h3 class="font-uncial text-xl text-waldritter mt-6 mb-2">{{ $monthNames[(int) $month] }} {{ $year }}</h3>
                <div class="bg-white/70 border-2 border-[#5a3a22]/30 shadow-sm sm:rounded-lg divide-y divide-stone-200">
                    @foreach ($monthEvents as $event)
                        <div class="flex items-center gap-4 px-4 py-3 cursor-pointer hover:bg-stone-50 transition-colors rounded"
                             data-modal-url="{{ route('adventures.show', $event) }}">
                            <div class="w-16 text-center shrink-0">
                                <div class="text-2xl font-semibold text-waldritter leading-none">{{ $event->start_at->format('d') }}</div>
                                <div class="text-xs uppercase text-stone-500">{{ $event->start_at->isoFormat('dd') }}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-stone-800">{{ $event->name }}</div>
                                <div class="text-sm text-stone-500">
                                    {{ $event->start_at->format('H:i') }} Uhr · {{ $event->location?->titel ?? 'Ort offen' }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div>@include('adventures._status_badge', ['status' => $event->status])</div>
                                <div class="text-sm text-stone-500 mt-1">{{ $event->confirmed_bookings_count }} / {{ $event->max_player }} Plätze</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="text-stone-500">Keine kommenden Events.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
