<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Abenteuer verwalten</h2>
            <a href="{{ route('adventures.create') }}"><x-primary-button>Neues Abenteuer</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 mb-6 text-stone-700">
                Verwaltungsansicht – zum Browsen/Anmelden siehe <a href="{{ route('adventures.index') }}" class="text-waldritter hover:underline">Abenteuer</a>.
            </div>

            {{-- Desktop: Fomantic-UI-Tabelle (ab sm), Zeilenklick → Verwaltungsseite --}}
            <div class="hidden sm:block bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-x-auto">
                <table class="ui table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Beginn</th>
                            <th>Status</th>
                            <th>Belegung</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adventures as $adventure)
                            @php
                                $deletable = $adventure->bookings_count === 0
                                    && $adventure->teamer_signups_count === 0
                                    && $adventure->ep_transactions_count === 0;
                                if (! $deletable) {
                                    $reasons = [];
                                    if ($adventure->bookings_count > 0)
                                        $reasons[] = $adventure->bookings_count.($adventure->bookings_count === 1 ? ' Spieler-Anmeldung' : ' Spieler-Anmeldungen');
                                    if ($adventure->teamer_signups_count > 0)
                                        $reasons[] = $adventure->teamer_signups_count.($adventure->teamer_signups_count === 1 ? ' Teamer-Anmeldung' : ' Teamer-Anmeldungen');
                                    if ($adventure->ep_transactions_count > 0)
                                        $reasons[] = $adventure->ep_transactions_count.($adventure->ep_transactions_count === 1 ? ' EP-Transaktion' : ' EP-Transaktionen');
                                    $blockReason = 'Nicht löschbar: '.implode(', ', $reasons);
                                }
                            @endphp
                            <tr data-navigate="{{ route('adventures.manage', $adventure) }}"
                                role="button" tabindex="0"
                                aria-label="Abenteuer {{ $adventure->name }} verwalten"
                                class="cursor-pointer hover:!bg-stone-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600">
                                <td class="font-medium">{{ $adventure->name }}</td>
                                <td>{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</td>
                                <td>@include('adventures._status_badge', ['status' => $adventure->status])</td>
                                <td>{{ $adventure->confirmed_bookings_count }} / {{ $adventure->max_player }}</td>
                                <td onclick="event.stopPropagation()" class="collapsing">
                                    @if($deletable)
                                        <form method="POST"
                                              action="{{ route('adventures.destroy', $adventure) }}"
                                              id="del-{{ $adventure->id }}"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button"
                                                class="ui mini red basic icon button"
                                                title="Löschen"
                                                onclick="confirmDeleteFromIndex({{ $adventure->id }}, '{{ addslashes($adventure->name) }}')">
                                            <i class="trash icon"></i>
                                        </button>
                                    @else
                                        <span data-tooltip="{{ $blockReason }}"
                                              data-position="top right"
                                              data-variation="mini"
                                              class="inline-block cursor-not-allowed">
                                            <button type="button"
                                                    class="ui mini red basic icon button disabled"
                                                    style="pointer-events: none;">
                                                <i class="trash icon"></i>
                                            </button>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-stone-500">Noch keine Abenteuer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobil: Kartenliste (bis sm) --}}
            <div class="sm:hidden bg-white/70 border-2 border-[#5a3a22]/40 shadow rounded-lg overflow-hidden">
                @forelse ($adventures as $adventure)
                    @php
                        $deletable = $adventure->bookings_count === 0
                            && $adventure->teamer_signups_count === 0
                            && $adventure->ep_transactions_count === 0;
                        if (! $deletable) {
                            $reasons = [];
                            if ($adventure->bookings_count > 0)
                                $reasons[] = $adventure->bookings_count.($adventure->bookings_count === 1 ? ' Spieler-Anmeldung' : ' Spieler-Anmeldungen');
                            if ($adventure->teamer_signups_count > 0)
                                $reasons[] = $adventure->teamer_signups_count.($adventure->teamer_signups_count === 1 ? ' Teamer-Anmeldung' : ' Teamer-Anmeldungen');
                            if ($adventure->ep_transactions_count > 0)
                                $reasons[] = $adventure->ep_transactions_count.($adventure->ep_transactions_count === 1 ? ' EP-Transaktion' : ' EP-Transaktionen');
                            $blockReason = 'Nicht löschbar: '.implode(', ', $reasons);
                        }
                    @endphp
                    <div class="flex items-center border-b border-stone-200 last:border-b-0">
                        <a href="{{ route('adventures.manage', $adventure) }}"
                           class="flex-1 block p-4 hover:bg-stone-50 active:bg-stone-100 transition-colors">
                            <div class="font-medium text-stone-800">{{ $adventure->name }}</div>
                            <div class="text-sm text-stone-500 mt-0.5">
                                {{ optional($adventure->start_at)->format('d.m.Y H:i') }}
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs">
                                @include('adventures._status_badge', ['status' => $adventure->status])
                                <span class="text-stone-500">{{ $adventure->confirmed_bookings_count }}/{{ $adventure->max_player }} Plätze</span>
                            </div>
                        </a>
                        <div class="pr-3">
                            @if($deletable)
                                <form method="POST"
                                      action="{{ route('adventures.destroy', $adventure) }}"
                                      id="del-mobile-{{ $adventure->id }}"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button"
                                        class="ui mini red basic icon button"
                                        title="Löschen"
                                        onclick="confirmDeleteFromIndex({{ $adventure->id }}, '{{ addslashes($adventure->name) }}', true)">
                                    <i class="trash icon"></i>
                                </button>
                            @else
                                <span data-tooltip="{{ $blockReason }}"
                                      data-position="top right"
                                      data-variation="mini"
                                      class="inline-block cursor-not-allowed">
                                    <button type="button"
                                            class="ui mini red basic icon button disabled"
                                            style="pointer-events: none;">
                                        <i class="trash icon"></i>
                                    </button>
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-stone-500">Noch keine Abenteuer.</div>
                @endforelse
            </div>

            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
            <div class="mt-4">{{ $adventures->links() }}</div>
        </div>
    </div>
    @push('scripts')
    <script>
        function confirmDeleteFromIndex(id, name, mobile) {
            if (confirm('Abenteuer „' + name + '" wirklich löschen?\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
                document.getElementById((mobile ? 'del-mobile-' : 'del-') + id).submit();
            }
        }
    </script>
    @endpush
</x-app-layout>
