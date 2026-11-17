@can('manage-checkin')
    @php
        // Deutsche Sortierung nach Nachname, dann Vorname (ohne Groß-/Kleinschreibung, Umlaute korrekt).
        $collator = new \Collator('de_DE');
        $checkinBookings = $adventure->bookings
            ->whereNotNull('player_id')
            ->sort(fn ($a, $b) => $collator->compare($a->player?->lastname ?? '', $b->player?->lastname ?? '')
                ?: $collator->compare($a->player?->name ?? '', $b->player?->name ?? ''))
            ->values();
    @endphp

    @php
        $visitedIds = $adventure->visits->pluck('player_id');
        // Kontaktdaten nur mit derselben Berechtigung durchsuchbar wie in der Anmeldeliste
        $searchContacts = auth()->user()?->canAny(['approve-bookings', 'manage-payments']);
        $statusOrder = ['offen' => 1, 'bestaetigt' => 2, 'abgemeldet' => 3, 'abgelehnt' => 4, 'storniert' => 5];
        $sortIcon = '<i data-sort-icon class="sort icon text-stone-300 text-xs ml-0.5" aria-hidden="true"></i>';
    @endphp

    {{-- Issue #10: Suche ab dem 3. Zeichen und sortierbare Spalten (heldenregister.js: initCheckinTable) --}}
    <div data-checkin-root data-state-key="checkin-{{ $adventure->id }}">
        {{-- Werkzeugleiste: Suchfeld links, Buttons rechtsbündig --}}
        <div class="flex flex-wrap items-center gap-2" style="margin-bottom:5px">
            @if ($checkinBookings->isNotEmpty())
                <label class="relative block w-full sm:w-80">
                    <span class="sr-only">Teilnehmer suchen</span>
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-stone-400">
                        <i class="search icon" style="margin:0" aria-hidden="true"></i>
                    </span>
                    <input type="search" data-checkin-search autocomplete="off"
                           placeholder="Name, Held, Eltern{{ $searchContacts ? ', E-Mail' : '' }} … (ab 3 Zeichen)"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:border-amber-600 focus:ring-amber-600 pl-9 text-sm">
                </label>
                <span data-checkin-count class="text-sm text-stone-500" aria-live="polite"></span>
            @endif

            <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                <a href="{{ route('adventures.participants-pdf', $adventure) }}" class="ui button" target="_blank" rel="noopener">
                    Teilnehmerliste als PDF
                </a>

                @if ($checkinBookings->isNotEmpty() && $adventure->checkinAllowed())
                    @php
                        $days = $adventure->start_at && $adventure->end_at ? max(1, (int) $adventure->start_at->copy()->startOfDay()->diffInDays($adventure->end_at->copy()->startOfDay()) + 1) : 1;
                        $epPerHero = $adventure->loot_ep_day * $days;
                        $epSummary = $adventure->epAwardSummary();
                    @endphp
                    {{-- Zeilenumbrüche im Bestätigungstext werden per white-space: pre-line dargestellt --}}
                    <form method="POST" action="{{ route('adventures.award-ep', $adventure) }}" data-refresh-modal class="m-0"
                          data-confirm="Es waren {{ $epSummary['total'] }} Helden auf diesem Abenteuer.&#10;{{ $epSummary['unknown'] }} unbekannte Helden, die keine EP erhalten.&#10;{{ $epSummary['already'] }} Helden haben schon vorher ihre EP erhalten.&#10;Somit werden nun an {{ $epSummary['pending'] }} Helden jeweils {{ $epPerHero }} EP verteilt.">
                        @csrf
                        <button type="submit" class="ui button">EP für Teilnehmer verbuchen ({{ $epPerHero }} EP/Held)</button>
                    </form>
                @endif
            </div>
        </div>

    @unless ($adventure->checkinAllowed())
        <div class="ui warning message" style="display:block">
            Check-in ist erst ab Status „Anmeldung geschlossen" möglich (aktuell: {{ $adventure->status?->description }}).
        </div>
    @endunless

    @if ($checkinBookings->isEmpty())
        <p class="text-stone-500">Keine Anmeldungen mit Check-in (Gäste sammeln keine EP).</p>
    @else
        <x-mobile.cards-or-table>
            <table class="ui very basic compact table w-full" data-checkin-table>
                <thead><tr>
                    {{-- Issue #11: Spalte für die Mehrfachauswahl --}}
                    <th class="collapsing"><span class="sr-only">Auswahl</span></th>
                    <th data-sort-col="lastname" data-sort-type="text" class="cursor-pointer select-none" tabindex="0" aria-sort="ascending">Nachname {!! $sortIcon !!}</th>
                    <th data-sort-col="firstname" data-sort-type="text" class="cursor-pointer select-none" tabindex="0">Vorname {!! $sortIcon !!}</th>
                    <th>Held</th>
                    <th data-sort-col="status" data-sort-type="num" class="cursor-pointer select-none" tabindex="0">Status {!! $sortIcon !!}</th>
                    <th data-sort-col="checkin" data-sort-type="num" class="cursor-pointer select-none" tabindex="0">Check-in {!! $sortIcon !!}</th>
                    <th>Unterschrift</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($checkinBookings as $booking)
                        @php
                            $present = $visitedIds->contains($booking->player_id);
                            // Aktiver Held: Heldenname, ersatzweise Heldenklasse(n)
                            $activeHero = $booking->player?->activeHero;
                            $heroLabel = $activeHero?->character_name ?: ($activeHero?->classes->pluck('name')->implode(', ') ?: '');
                            $guardian = $booking->guardian();

                            // Suchindex: Teilnehmer, Held, Erziehungsberechtigte/verknüpfte Accounts, ggf. E-Mails.
                            $searchParts = [$booking->player?->name, $booking->player?->lastname, $heroLabel,
                                $guardian?->name, $guardian?->lastname];
                            foreach ($booking->player?->users ?? [] as $u) {
                                $searchParts[] = $u->name;
                                $searchParts[] = $u->lastname;
                                if ($searchContacts) {
                                    $searchParts[] = $u->email;
                                }
                            }
                            if ($searchContacts) {
                                $searchParts[] = $guardian?->email;
                                $searchParts[] = $booking->player?->email;
                            }
                            $searchText = mb_strtolower(implode(' ', array_unique(array_filter($searchParts))));
                        @endphp
                        <tr data-search="{{ $searchText }}">
                            <td data-label="Auswahl" class="collapsing">
                                <input type="checkbox" data-checkin-select value="{{ $booking->id }}"
                                       data-name="{{ $booking->player?->full_name }}"
                                       aria-label="{{ $booking->player?->full_name }} auswählen"
                                       class="rounded border-gray-300 cursor-pointer">
                            </td>
                            <td data-label="Nachname" data-col="lastname" data-sort-val="{{ $booking->player?->lastname }}" class="font-medium">{{ $booking->player?->lastname }}</td>
                            <td data-label="Vorname" data-col="firstname" data-sort-val="{{ $booking->player?->name }}" class="font-medium">{{ $booking->player?->name }}</td>
                            <td data-label="Held">{{ $heroLabel ?: '—' }}</td>
                            <td data-label="Status" data-col="status" data-sort-val="{{ $statusOrder[$booking->status ?? 'offen'] ?? 9 }}">
                                @if ($booking->status === 'abgemeldet')
                                    <span class="text-orange-600">abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
                                @else
                                    <span class="text-stone-500">{{ $booking->status_label }}</span>
                                @endif
                            </td>
                            <td data-label="Check-in" data-col="checkin" data-sort-val="{{ $present ? 1 : 0 }}">
                                @if ($present)
                                    <span class="text-green-700">✓ anwesend</span>
                                @else
                                    <span class="text-stone-400">—</span>
                                @endif
                            </td>
                            <td data-label="Unterschrift">
                                @if ($booking->signature)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $booking->signature }}" alt="Unterschrift" style="height:34px; background:#fff; border:1px solid #ccc" loading="lazy">
                                        <form method="POST"
                                              action="{{ route('adventures.bookings.signature.destroy', [$adventure, $booking]) }}"
                                              data-refresh-modal
                                              data-confirm="Unterschrift von {{ $booking->player?->full_name }} unwiderruflich löschen?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ui mini basic red button" title="Unterschrift löschen (DSGVO Art. 17)">✕</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-stone-500">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    @if ($adventure->checkinAllowed())
                                        @if ($present)
                                            <form method="POST" action="{{ route('adventures.bookings.checkin', [$adventure, $booking]) }}" data-refresh-modal>
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ui mini button">auschecken</button>
                                            </form>
                                        @else
                                            <button type="button" class="ui mini primary button checkin-trigger"
                                                    data-url="{{ route('adventures.bookings.signature.update', [$adventure, $booking]) }}"
                                                    data-name="{{ $booking->player?->full_name }}">Check-in</button>
                                        @endif
                                    @endif
                                    @if ($booking->status === 'abgemeldet')
                                        <form method="POST" action="{{ route('adventures.bookings.deregister-undo', [$adventure, $booking]) }}" data-refresh-modal
                                              data-confirm="Abmeldung von {{ $booking->player?->full_name }} zurücknehmen? Der Status wird wieder „bestätigt“.">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ui mini button"><i class="undo icon"></i> Abmeldung zurücknehmen</button>
                                        </form>
                                    @else
                                        <button type="button" class="ui mini button deregister-trigger"
                                                data-url="{{ route('adventures.bookings.deregister', [$adventure, $booking]) }}"
                                                data-name="{{ $booking->player?->full_name }}">Abmelden</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr data-checkin-empty style="display:none">
                        <td colspan="8" class="text-stone-500">Keine Treffer für diese Suche.</td>
                    </tr>
                </tbody>
            </table>
        </x-mobile.cards-or-table>

        {{-- Issue #11: Sticky-Leiste mit Mehrfachaktionen, erscheint bei mindestens einer Auswahl --}}
        {{-- hidden liegt auf dem Wrapper: die Klasse „flex“ würde das hidden-Attribut überschreiben --}}
        <div data-checkin-bulkbar hidden role="region" aria-label="Mehrfachaktionen" class="sticky bottom-0 z-10 mt-2">
        <div class="flex flex-wrap items-center gap-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 shadow-md">
            <span data-checkin-bulk-count class="text-sm font-semibold" aria-live="polite"></span>
            <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                @if ($adventure->checkinAllowed())
                    @can('take-signatures')
                        <button type="button" class="ui small primary button" data-checkin-bulk-action="checkin"
                                data-url="{{ route('adventures.checkin.bulk', $adventure) }}">
                            <i class="signature icon"></i> Check-in
                        </button>
                    @endcan
                @endif
                <button type="button" class="ui small button" data-checkin-bulk-action="deregister"
                        data-url="{{ route('adventures.deregister.bulk', $adventure) }}">
                    <i class="user times icon"></i> Abmelden
                </button>
                <button type="button" class="ui small basic button" data-checkin-bulk-clear>Auswahl aufheben</button>
            </div>
        </div>
        </div>
    @endif
    </div>
@endcan
