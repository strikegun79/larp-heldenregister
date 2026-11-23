@php($manage = $manage ?? false)
{{-- Spalte erscheint wenn: Manage-Modus mit irgendeiner Verwaltungs-Berechtigung,
     ODER Detailansicht mit Bearbeiten/Stornieren-Recht (nur eigene Buchungen). --}}
@php($canAnyBookingAction = $manage
    ? auth()->user()?->canAny(['approve-bookings', 'manage-payments', 'adventure.modify', 'adventure.cancel'])
    : auth()->user()?->can('adventure.book'))
<x-mobile.cards-or-table>
<table class="ui very basic compact unstackable table" @if ($manage) data-bookings-table @endif>
    <thead class="mob-thead" hidden><tr>
        @if ($manage)
            <th data-sort-col="name" class="cursor-pointer select-none">Spieler <i data-sort-icon class="sort icon text-stone-300 text-xs ml-0.5" aria-hidden="true"></i>
                <input type="search" data-bookings-search placeholder="Suchen…"
                       class="ml-1 text-xs border border-stone-200 rounded px-1.5 py-0.5 font-normal cursor-text w-24 align-middle"
                       onclick="event.stopPropagation()"></th>
            <th data-sort-col="age" class="cursor-pointer select-none">Alter <i data-sort-icon class="sort icon text-stone-300 text-xs ml-0.5" aria-hidden="true"></i></th>
            <th>Rolle</th><th>Liste</th>
            <th data-sort-col="status" class="cursor-pointer select-none">Status <i data-sort-icon class="sort icon text-stone-300 text-xs ml-0.5" aria-hidden="true"></i></th>
            <th data-sort-col="paid" class="cursor-pointer select-none">Beitrag <i data-sort-icon class="sort icon text-stone-300 text-xs ml-0.5" aria-hidden="true"></i></th>
            <th data-sort-col="created" class="cursor-pointer select-none whitespace-nowrap">Angemeldet <i data-sort-icon class="sort down icon text-xs ml-0.5" aria-hidden="true"></i></th>
        @else
            <th>Spieler</th><th>Alter</th><th>Rolle</th><th>Liste</th><th>Status</th><th>Beitrag</th>
        @endif
        @if ($canAnyBookingAction)<th></th>@endif
    </tr></thead>
    <tbody>
        @forelse ($bookings as $booking)
            {{-- Verwaltung: Zeilenklick öffnet den Inspektor (Issue #9) --}}
            <tr @if ($manage)
                    data-inspector-url="{{ route('adventures.bookings.inspector', [$adventure, $booking]) }}"
                    tabindex="0" class="booking-row-clickable"
                    aria-label="Details zur Anmeldung von {{ $booking->participant_name }} öffnen"
                @endif>
                <td data-label="Spieler" @if ($manage) data-col="name" data-sort-val="{{ $booking->participant_name }}" @endif>
                    {{ $booking->participant_name }}
                    @if ($booking->is_guest)<span class="ui mini label">Gast</span>@endif
                    @if ($manage)
                        @canany(['approve-bookings', 'manage-payments'])
                            @php($guardian = $booking->guardian())
                            @if ($guardian)
                                <div class="text-xs text-stone-500 mt-0.5">
                                    {{ $guardian->name }} {{ $guardian->lastname }}
                                    @if ($guardian->email) · <a href="mailto:{{ $guardian->email }}" class="text-stone-500 hover:text-waldritter underline">{{ $guardian->email }}</a> @endif
                                    @if ($guardian->phone) · {{ $guardian->phone }} @endif
										{{-- Braucht zu viel Platz
										@if ($guardian->street)
                                        · {{ $guardian->street }} {{ $guardian->house_number }}, {{ $guardian->zip }} {{ $guardian->city }}
										@endif
										--}}
                                    @if (! $booking->usesGuardianAddress() && $booking->player?->street)
                                        <span class="text-amber-700">(Kind abw.: {{ $booking->player->street }} {{ $booking->player->house_number }}, {{ $booking->player->zip }} {{ $booking->player->city }})</span>
                                    @endif
                                </div>
                            @endif
                        @endcanany
                    @endif
                </td>
                <td data-label="Alter" @if ($manage) data-col="age" data-sort-val="{{ $booking->participant_age ?? 9999 }}" @endif>{{ $booking->participant_age ?? '—' }}</td>
                <td data-label="Rolle">{{ $booking->role?->description }}</td>
                <td data-label="Liste">{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                <td data-label="Status" @if ($manage) data-col="status" data-sort-val="{{ $booking->waitlisted ? 0 : ($booking->status === 'bestaetigt' ? 2 : ($booking->status === 'abgemeldet' ? 3 : ($booking->status === 'abgelehnt' ? 4 : ($booking->status === 'storniert' ? 5 : 1)))) }}" @endif>
                    {{-- Status als Pill mit Text und Symbol (nicht nur Farbe) --}}
                    @if ($booking->waitlisted)
                        <span class="wr-pill wr-pill--small wr-pill--waitlist"><i class="hourglass half icon" aria-hidden="true"></i>Warteliste</span>
                    @elseif ($booking->status === 'bestaetigt')
                        <span class="wr-pill wr-pill--small wr-pill--success"><i class="check circle icon" aria-hidden="true"></i>bestätigt</span>
                    @elseif ($booking->status === 'abgelehnt')
                        <span class="wr-pill wr-pill--small wr-pill--danger"><i class="times circle icon" aria-hidden="true"></i>abgelehnt</span>
                    @elseif ($booking->status === 'abgemeldet')
                        <span class="wr-pill wr-pill--small wr-pill--waitlist"><i class="sign out alternate icon" aria-hidden="true"></i>abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
                    @elseif ($booking->status === 'storniert')
                        <span class="wr-pill wr-pill--small wr-pill--danger"><i class="ban icon" aria-hidden="true"></i>storniert</span>
                    @else
                        <span class="wr-pill wr-pill--small wr-pill--pending"><i class="clock icon" aria-hidden="true"></i>offen</span>
                    @endif
                </td>
                <td data-label="Beitrag" @if ($manage) data-col="paid" data-sort-val="{{ $booking->paid ? 1 : 0 }}" @endif>
                    @if ($booking->role?->is_teamer_like)
                        <span class="text-stone-400">—</span>
                    @else
                        @if ($adventure->fee > 0)
                            @if ($booking->ermaessigung && $adventure->fee_reduced !== null)
                                <span class="text-xs text-stone-500 mr-1">{{ number_format($adventure->fee_reduced, 2, ',', '.') }} €</span>
                                <span class="ui mini label" title="Ermäßigt">Erm.</span>
                            @else
                                <span class="text-xs text-stone-500 mr-1">{{ number_format($adventure->fee, 2, ',', '.') }} €</span>
                            @endif
                        @endif
                        @if ($booking->paid)
                            <span class="wr-pill wr-pill--small wr-pill--success"><i class="check icon" aria-hidden="true"></i>bezahlt</span>
                        @else
                            <span class="wr-pill wr-pill--small wr-pill--pending"><i class="euro sign icon" aria-hidden="true"></i>offen</span>
                        @endif
                    @endif
                </td>
                @if ($manage)
                    <td data-label="Angemeldet" data-col="created" data-sort-val="{{ $booking->created_at->timestamp }}" class="text-xs text-stone-500 whitespace-nowrap">{{ $booking->created_at->format('d.m.Y H:i') }}</td>
                @endif
                @if ($canAnyBookingAction)
                    <td>
                        <div class="flex items-center justify-end gap-1 flex-wrap">
                            {{-- Verwaltung: Warteliste, Stornieren, Wiederherstellen und Mail
                                 liegen im Inspektor (Issue #9). In der Liste bleiben Bezahlt/offen und Bearbeiten. --}}
                            @if ($manage)
                                @if (! $booking->role?->is_teamer_like)
                                    @can('manage-payments')
                                        <form method="POST" action="{{ route('adventures.bookings.payment', [$adventure, $booking]) }}" data-refresh-modal
                                              data-confirm="{{ $booking->paid ? 'Beitrag als offen markieren?' : 'Beitrag als bezahlt markieren?' }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ui mini icon button {{ $booking->paid ? '' : 'yellow' }}"
                                                    aria-label="{{ $booking->paid ? 'Beitrag von '.$booking->participant_name.' als offen markieren' : 'Beitrag von '.$booking->participant_name.' als bezahlt markieren' }}"
                                                    data-tooltip="{{ $booking->paid ? 'Als offen markieren' : 'Als bezahlt markieren' }}" data-position="top right">
                                                <i class="coins icon" aria-hidden="true"></i>
                                                <span class="sm:hidden ml-1 text-xs">{{ $booking->paid ? 'Offen' : 'Bezahlt' }}</span>
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            @endif
                            {{-- Bestätigung erneut senden: nur wenn nicht storniert (Verwaltung: im Inspektor) --}}
                            @unless ($manage || $booking->is_guest || $booking->waitlisted || $booking->status === 'storniert')
                                @if (Gate::allows('approve-bookings') || $booking->booked_by_user_id === auth()->id())
                                <form method="POST" action="{{ route('adventures.bookings.resend-confirmation', [$adventure, $booking]) }}" data-refresh-modal>
                                    @csrf
                                    <button type="submit" class="ui mini icon button"
                                            aria-label="Anmeldebestätigung für {{ $booking->participant_name }} erneut senden"
                                            data-tooltip="Anmeldebestätigung erneut senden" data-position="top right">
                                        <i class="envelope icon" aria-hidden="true"></i>
                                        <span class="sm:hidden ml-1 text-xs">Mail</span>
                                    </button>
                                </form>
                                @endif
                            @endunless
                            {{-- Bearbeiten: nur wenn nicht storniert --}}
                            @if ($booking->status !== 'storniert')
                                @can('adventure.modify')
                                    <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                       data-modal-stack="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                       class="ui mini icon button" aria-label="Anmeldung von {{ $booking->participant_name }} bearbeiten"
                                       data-tooltip="Bearbeiten" data-position="top right">
                                        <i class="edit icon" aria-hidden="true"></i>
                                        <span class="sm:hidden ml-1 text-xs">Bearb.</span>
                                    </a>
                                @endcan
                                @unless ($manage)
                                    @can('adventure.cancel')
                                        <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                                              data-refresh-modal data-confirm="Anmeldung wirklich stornieren? Diese Aktion kann nur der Projektleiter rückgängig machen.">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ui mini icon button red" aria-label="Anmeldung von {{ $booking->participant_name }} stornieren"
                                                    data-tooltip="Stornieren" data-position="top right">
                                                <i class="times icon" aria-hidden="true"></i>
                                                <span class="sm:hidden ml-1 text-xs">Storn.</span>
                                            </button>
                                        </form>
                                    @endcan
                                @endunless
                            @else
                                @unless ($manage)
                                    {{-- Detail-View: Nutzer beantragt Rücknahme beim Projektleiter (BOOK-09) --}}
                                    @if ($booking->reinstate_requested)
                                        <span data-tooltip="Rücknahme der Stornierung schon beantragt"
                                              data-position="top right">
                                            <button type="button" disabled
                                                    class="ui mini icon button disabled"
                                                    style="pointer-events:none;">
                                                <i class="undo icon" aria-hidden="true"></i>
                                                <span class="sm:hidden ml-1 text-xs">Anfragen</span>
                                            </button>
                                        </span>
                                    @else
                                        <button type="button"
                                                class="reinstate-request-trigger ui mini icon button orange"
                                                data-url="{{ route('adventures.bookings.request-reinstate', [$adventure, $booking]) }}"
                                                data-tooltip="Rücknahme der Stornierung anfragen"
                                                data-position="top right">
                                            <i class="undo icon" aria-hidden="true"></i>
                                            <span class="sm:hidden ml-1 text-xs">Anfragen</span>
                                        </button>
                                    @endif
                                @endunless
                            @endif
                        </div>
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ ($canAnyBookingAction ? 7 : 6) + ($manage ? 1 : 0) }}" class="text-stone-500">Noch keine Anmeldungen.</td></tr>
        @endforelse
    </tbody>
</table>
</x-mobile.cards-or-table>
@if ($manage)
    @can('manage-payments')
        @php($summary = $adventure->paymentSummary())
        <div class="text-sm text-stone-600 mt-3 space-y-0.5">
            <p>
                Bezahlt: <strong>{{ $summary['paid_count'] }}/{{ $summary['total_count'] }}</strong>
                · eingegangen <strong class="text-green-700">{{ number_format($summary['paid_amount'], 2, ',', '.') }} €</strong>
                · offen <strong class="{{ $summary['open_amount'] > 0 ? 'text-orange-600' : 'text-stone-500' }}">{{ number_format($summary['open_amount'], 2, ',', '.') }} €</strong>
                · gesamt {{ number_format($summary['total_amount'], 2, ',', '.') }} €
            </p>
            @if ($adventure->fee_reduced !== null && $summary['erm_count'] > 0)
                <p class="text-xs text-stone-400">
                    Davon {{ $summary['erm_count'] }} ermäßigt ({{ number_format($adventure->fee_reduced, 2, ',', '.') }} €),
                    {{ $summary['total_count'] - $summary['erm_count'] }} regulär ({{ number_format($adventure->fee, 2, ',', '.') }} €)
                </p>
            @endif
        </div>

        {{-- Issue #5: Datum des letzten Bezahlungsabgleichs – Teilnehmer sehen es bei offenem Beitrag.
             _bookings wird für Handy und Desktop zweimal eingebunden → eindeutige IDs per Suffix. --}}
        @php($rid = 'reconciled-'.$adventure->id.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5)))
        <div class="mt-3 flex flex-wrap items-end gap-2" role="group" aria-labelledby="{{ $rid }}-label">
            <p id="{{ $rid }}-label" class="text-sm m-0 w-full">
                Letzter Abgleich der Zahlungen:
                <strong>{{ $adventure->payments_reconciled_at?->format('d.m.Y') ?? 'noch nicht eingetragen' }}</strong>
            </p>
            <form method="POST" action="{{ route('adventures.payments-reconciled', $adventure) }}" data-refresh-modal>
                @csrf @method('PATCH')
                <input type="hidden" name="today" value="1">
                <button type="submit" class="wr-btn wr-btn--secondary wr-btn--small">
                    <i class="calendar check icon m-0" aria-hidden="true"></i> Heute abgeglichen
                </button>
            </form>
            <form method="POST" action="{{ route('adventures.payments-reconciled', $adventure) }}" data-refresh-modal
                  class="flex flex-wrap items-end gap-2">
                @csrf @method('PATCH')
                <label for="{{ $rid }}-date" class="text-sm">
                    Anderes Datum <span class="text-stone-500">(leer = entfernen)</span>
                    <input type="date" id="{{ $rid }}-date" name="payments_reconciled_at"
                           value="{{ $adventure->payments_reconciled_at?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                           class="block mt-1 rounded border-stone-300 text-sm">
                </label>
                <button type="submit" class="wr-btn wr-btn--secondary wr-btn--small">Datum speichern</button>
            </form>
        </div>
    @endcan
@endif
