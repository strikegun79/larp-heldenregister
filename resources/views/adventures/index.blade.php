<x-app-layout>
    @php
        // Ansicht umschalten: übrige Parameter (z. B. Suche) behalten, Seite zurücksetzen.
        $sortUrl = fn (string $value) => route('adventures.index', array_merge(request()->except('sort', 'page'), ['sort' => $value]));
        $ansichten = [
            'grouped' => ['label' => 'Nach Status', 'icon' => 'th list'],
            'asc' => ['label' => 'Nächstes zuerst', 'icon' => 'sort amount up'],
            'desc' => ['label' => 'Letztes zuerst', 'icon' => 'sort amount down'],
        ];
    @endphp

    {{-- Waldritter-Designsystem v1: neue Tokens gelten nur innerhalb von .wr-v1 --}}
    <div class="wr-v1 py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Seitenkopf --}}
            <div class="mb-6">
                <span class="wr-eyebrow">Veranstaltungen</span>
                <h1 class="wr-heading">Abenteuer</h1>
                <p class="wr-intro">Finde dein nächstes Abenteuer und melde dich an.</p>
            </div>

            {{-- Werkzeugleiste: Suche, Ansicht, Aktionen --}}
            <div class="wr-card wr-toolbar">
                <form method="GET" action="{{ route('adventures.index') }}" role="search"
                      class="flex flex-wrap items-end gap-2 flex-1 min-w-0 md:max-w-xl">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <div class="flex-1 min-w-[12rem]">
                        <label for="adventures-search" class="wr-label">Abenteuer suchen</label>
                        <input id="adventures-search" type="search" name="q" value="{{ $q }}"
                               placeholder="z. B. Sommercamp" class="wr-input">
                    </div>
                    <button type="submit" class="wr-btn wr-btn--primary">
                        <i class="search icon m-0" aria-hidden="true"></i> Suchen
                    </button>
                    @if ($q !== '')
                        <a href="{{ route('adventures.index', ['sort' => $sort]) }}" class="wr-btn wr-btn--secondary">
                            Zurücksetzen
                        </a>
                    @endif
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <nav aria-label="Ansicht der Abenteuerliste" class="wr-segmented">
                        @foreach ($ansichten as $value => $ansicht)
                            <a href="{{ $sortUrl($value) }}" @if ($sort === $value) aria-current="page" @endif>
                                <i class="{{ $ansicht['icon'] }} icon m-0" aria-hidden="true"></i>{{ $ansicht['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <a href="{{ route('adventures.calendar') }}" class="wr-btn wr-btn--secondary">
                        <i class="calendar icon m-0" aria-hidden="true"></i> Kalender
                    </a>
                    @can('events.edit')
                        <a href="{{ route('adventures.manage-index') }}" class="wr-btn wr-btn--secondary">
                            <i class="cogs icon m-0" aria-hidden="true"></i> Verwaltung
                        </a>
                    @endcan
                </div>
            </div>

            @if ($sort === 'grouped')
                {{-- Ansicht „Nach Status“: ein Abschnitt pro Status, Reihenfolge vom Controller --}}
                @forelse ($grouped as $statusId => $group)
                    @php $status = $group->first()->status; @endphp
                    <section aria-labelledby="status-{{ $statusId }}" class="mb-8">
                        <div class="wr-section-heading mt-0">
                            <h2 id="status-{{ $statusId }}" class="wr-section-heading__title inline-flex items-center gap-2">
                                <span class="wr-dot" style="background-color: {{ $status?->color ?? '#ffffff' }};" aria-hidden="true"></span>
                                {{ $status?->description ?? 'Ohne Status' }}
                            </h2>
                            <span class="wr-section-heading__aside">
                                {{ $group->count() }} Abenteuer
                            </span>
                        </div>
                        <div class="wr-card-grid" role="list">
                            @foreach ($group as $adventure)
                                <div role="listitem" class="flex">
                                    <x-wr.adventure-card :adventure="$adventure" class="flex-1" />
                                </div>
                            @endforeach
                        </div>
                    </section>
                @empty
                    @include('adventures._empty_state')
                @endforelse

            @else
                {{-- Ansicht nach Datum (flache Liste mit Blätterfunktion) --}}
                <h2 class="sr-only">{{ $ansichten[$sort]['label'] }}</h2>
                @if ($adventures->isEmpty())
                    @include('adventures._empty_state')
                @else
                    <div class="wr-card-grid" role="list">
                        @foreach ($adventures as $adventure)
                            <div role="listitem" class="flex">
                                <x-wr.adventure-card :adventure="$adventure" class="flex-1" />
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $adventures->links() }}</div>
                @endif
            @endif

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ab 640 px öffnet eine Abenteuerkarte das Detail-Modal (wie bisher die Tabellenzeile),
            // auf dem Smartphone die Detailseite. Mittelklick/Strg-Klick öffnen immer einen neuen Tab.
            document.addEventListener('click', function (e) {
                const link = e.target.closest('.wr-adventure-link');
                if (!link || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (!window.matchMedia('(min-width: 640px)').matches) return;
                e.preventDefault();
                window.appModalUrl = link.href;
                $('#app-modal').modal({ autofocus: false, observeChanges: true }).modal('show');
                loadModalContent(window.appModalUrl);
            });

            // Modal öffnen via ?open=<id>.
            const open = new URLSearchParams(window.location.search).get('open');
            if (!open) return;
            history.replaceState({}, '', '{{ route('adventures.index') }}');
            window.appModalUrl = '{{ url('adventures') }}/' + open;
            $('#app-modal').modal({ autofocus: false, observeChanges: true }).modal('show');
            loadModalContent(window.appModalUrl);
        });
    </script>
    @endpush
</x-app-layout>
