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
            <tr>
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
                    @if ($booking->waitlisted)
                        <span class="text-amber-600">⏳ Warteliste</span>
                    @elseif ($booking->status === 'bestaetigt')
                        <span class="text-green-700">✓ bestätigt</span>
                    @elseif ($booking->status === 'abgelehnt')
                        <span class="text-red-600">abgelehnt</span>
                    @elseif ($booking->status === 'abgemeldet')
                        <span class="text-orange-600">abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
                    @elseif ($booking->status === 'storniert')
                        <span class="text-stone-400 line-through">storniert</span>
                    @else
                        <span class="text-stone-500">offen</span>
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
                            <span class="text-green-700">✓ bezahlt</span>
                        @else
                            <span class="text-stone-500">offen</span>
                        @endif
                    @endif
                </td>
                @if ($manage)
                    <td data-label="Angemeldet" data-col="created" data-sort-val="{{ $booking->created_at->timestamp }}" class="text-xs text-stone-500 whitespace-nowrap">{{ $booking->created_at->format('d.m.Y H:i') }}</td>
                @endif
                @if ($canAnyBookingAction)
                    <td>
                        <div class="flex items-center justify-end gap-1 flex-wrap">
                            @if ($manage)
                                @can('approve-bookings')
                                    {{-- Warteliste-Promotion: nur in Verwaltungsansicht --}}
                                    @unless ($booking->is_guest)
                                    @if ($booking->waitlisted)
                                        <form method="POST" action="{{ route('adventures.bookings.resend-confirmation', [$adventure, $booking]) }}" data-refresh-modal
                                              data-confirm="{{ $booking->player?->full_name ?? 'Spieler' }} von der Warteliste auf regulären Platz hochstufen?">
                                            @csrf
                                            <button type="submit" class="ui mini icon green button"
                                                    data-tooltip="Von Warteliste bestätigen" data-position="top center">
                                                <i class="check icon"></i>
                                                <span class="sm:hidden ml-1 text-xs">Bestät.</span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('adventures.bookings.move-to-waitlist', [$adventure, $booking]) }}" data-refresh-modal
                                              data-confirm="{{ $booking->player?->full_name ?? 'Spieler' }} auf die Warteliste verschieben? Der Teilnehmer wird per E-Mail informiert.">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ui mini icon yellow button"
                                                    data-tooltip="Auf Warteliste verschieben" data-position="top center">
                                                <i class="hourglass half icon"></i>
                                                <span class="sm:hidden ml-1 text-xs">Warteliste</span>
                                            </button>
                                        </form>
                                    @endif
                                    @endunless
                                @endcan
                                @if (! $booking->role?->is_teamer_like)
                                    @can('manage-payments')
                                        <form method="POST" action="{{ route('adventures.bookings.payment', [$adventure, $booking]) }}" data-refresh-modal
                                              data-confirm="{{ $booking->paid ? 'Beitrag als offen markieren?' : 'Beitrag als bezahlt markieren?' }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ui mini icon button {{ $booking->paid ? '' : 'yellow' }}"
                                                    data-tooltip="{{ $booking->paid ? 'Als offen markieren' : 'Als bezahlt markieren' }}" data-position="top center">
                                                <i class="coins icon"></i>
                                                <span class="sm:hidden ml-1 text-xs">{{ $booking->paid ? 'Offen' : 'Bezahlt' }}</span>
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            @endif
                            {{-- Bestätigung erneut senden: nur wenn nicht storniert --}}
                            @unless ($booking->is_guest || $booking->waitlisted || $booking->status === 'storniert')
                                @if (Gate::allows('approve-bookings') || $booking->booked_by_user_id === auth()->id())
                                <form method="POST" action="{{ route('adventures.bookings.resend-confirmation', [$adventure, $booking]) }}" data-refresh-modal>
                                    @csrf
                                    <button type="submit" class="ui mini icon button"
                                            data-tooltip="Anmeldebestätigung erneut senden" data-position="top center">
                                        <i class="envelope outline icon"></i>
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
                                       class="ui mini icon button" data-tooltip="Bearbeiten" data-position="top center">
                                        <i class="edit icon"></i>
                                        <span class="sm:hidden ml-1 text-xs">Bearb.</span>
                                    </a>
                                @endcan
                                @can('adventure.cancel')
                                    <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                                          data-refresh-modal data-confirm="Anmeldung wirklich stornieren? Diese Aktion kann nur der Projektleiter rückgängig machen.">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ui mini icon button red" data-tooltip="Stornieren" data-position="top center">
                                            <i class="times icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">Storn.</span>
                                        </button>
                                    </form>
                                @endcan
                            @else
                                @if ($manage)
                                    {{-- Manage-View: Projektleiter/Admin kann direkt wiederherstellen --}}
                                    @can('approve-bookings')
                                        <form method="POST" action="{{ route('adventures.bookings.reinstate', [$adventure, $booking]) }}"
                                              data-refresh-modal data-confirm="Anmeldung von {{ $booking->participant_name }} wiederherstellen?">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ui mini icon button teal" data-tooltip="Anmeldung wiederherstellen" data-position="top center">
                                                <i class="undo icon"></i>
                                                <span class="sm:hidden ml-1 text-xs">Wiederherst.</span>
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    {{-- Detail-View: Nutzer beantragt Rücknahme beim Projektleiter (BOOK-09) --}}
                                    @if ($booking->reinstate_requested)
                                        <span data-tooltip="Rücknahme der Stornierung schon beantragt"
                                              data-position="top right">
                                            <button type="button" disabled
                                                    class="ui mini icon button disabled"
                                                    style="pointer-events:none;">
                                                <i class="undo icon"></i>
                                                <span class="sm:hidden ml-1 text-xs">Anfragen</span>
                                            </button>
                                        </span>
                                    @else
                                        <button type="button"
                                                class="reinstate-request-trigger ui mini icon button orange"
                                                data-url="{{ route('adventures.bookings.request-reinstate', [$adventure, $booking]) }}"
                                                data-tooltip="Rücknahme der Stornierung anfragen"
                                                data-position="top right">
                                            <i class="undo icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">Anfragen</span>
                                        </button>
                                    @endif
                                @endif
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
    @endcan
@endif
