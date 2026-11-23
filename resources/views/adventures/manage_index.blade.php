<x-app-layout>
    @php
        // Löschbar nur ohne Anmeldungen, Teamer-Anmeldungen und EP-Buchungen; sonst sichtbarer Grund.
        $blockReason = function ($adventure) {
            $reasons = [];
            if ($adventure->bookings_count > 0) {
                $reasons[] = $adventure->bookings_count . ($adventure->bookings_count === 1 ? ' Spieler-Anmeldung' : ' Spieler-Anmeldungen');
            }
            if ($adventure->teamer_signups_count > 0) {
                $reasons[] = $adventure->teamer_signups_count . ($adventure->teamer_signups_count === 1 ? ' Teamer-Anmeldung' : ' Teamer-Anmeldungen');
            }
            if ($adventure->ep_transactions_count > 0) {
                $reasons[] = $adventure->ep_transactions_count . ($adventure->ep_transactions_count === 1 ? ' EP-Transaktion' : ' EP-Transaktionen');
            }

            return $reasons ? 'Nicht löschbar: ' . implode(', ', $reasons) : null;
        };
    @endphp

    {{-- Waldritter-Designsystem v1 (Verwaltungsliste) --}}
    <div class="wr-v1 py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <a href="{{ route('admin.index') }}"
                       class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                        <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zur Verwaltung
                    </a>
                    <span class="wr-eyebrow">Veranstaltungen</span>
                    <h1 class="wr-heading">Abenteuer verwalten</h1>
                    <p class="wr-intro">
                        Verwaltungsansicht – zum Ansehen und Anmelden siehe
                        <a href="{{ route('adventures.index') }}" class="wr-interactive underline text-wr-forest">Abenteuer</a>.
                    </p>
                </div>
                <a href="{{ route('adventures.create') }}" class="wr-btn wr-btn--primary">
                    <i class="plus icon m-0" aria-hidden="true"></i> Neues Abenteuer
                </a>
            </div>

            @if ($adventures->isEmpty())
                <div class="wr-card p-8 text-center items-center">
                    <p class="font-semibold m-0">Noch keine Abenteuer.</p>
                </div>
            @else
                {{-- Handy: Karten --}}
                <ul class="sm:hidden space-y-3 m-0 p-0 list-none" role="list">
                    @foreach ($adventures as $adventure)
                        @php($reason = $blockReason($adventure))
                        <li class="wr-card">
                            <a href="{{ route('adventures.manage', $adventure) }}" class="wr-card__body wr-interactive no-underline text-wr-ink">
                                <span class="wr-card__title break-words">{{ $adventure->name }}</span>
                                <span class="text-sm text-wr-ink-muted">{{ optional($adventure->start_at)->locale('de')->isoFormat('dd, DD.MM.YYYY HH:mm') }}</span>
                                <span class="flex flex-wrap items-center gap-1.5 mt-1 text-sm">
                                    @include('adventures._status_badge', ['status' => $adventure->status])
                                    @include('adventures._manage_occupancy')
                                </span>
                            </a>
                            <div class="px-4 pb-3">
                                @include('adventures._manage_delete', ['idSuffix' => 'm'])
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: ruhige Tabelle; der Name ist der Link (Tastatur), die Zeile bleibt per Maus klickbar --}}
                <div class="hidden sm:block wr-card overflow-x-auto">
                    <table class="wr-table">
                        <caption class="sr-only">Abenteuer, Seite {{ $adventures->currentPage() }} von {{ $adventures->lastPage() }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Beginn</th>
                                <th scope="col">Status</th>
                                <th scope="col">Belegung</th>
                                <th scope="col"><span class="sr-only">Aktionen</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($adventures as $adventure)
                                <tr data-navigate="{{ route('adventures.manage', $adventure) }}">
                                    <td>
                                        <a href="{{ route('adventures.manage', $adventure) }}"
                                           class="wr-interactive font-semibold text-wr-forest underline">{{ $adventure->name }}</a>
                                    </td>
                                    <td class="whitespace-nowrap">{{ optional($adventure->start_at)->locale('de')->isoFormat('dd, DD.MM.YYYY HH:mm') }}</td>
                                    <td>@include('adventures._status_badge', ['status' => $adventure->status])</td>
                                    <td>@include('adventures._manage_occupancy')</td>
                                    <td class="text-right">@include('adventures._manage_delete', ['idSuffix' => 'd'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $adventures->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
