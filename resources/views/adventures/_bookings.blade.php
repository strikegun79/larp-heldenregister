@php($manage = $manage ?? false)
<div class="overflow-x-auto">
<table class="ui very basic compact table">
    <thead><tr>
        <th>Spieler</th><th>Alter</th><th>Rolle</th><th>Liste</th><th>Status</th><th>Beitrag</th><th></th>
    </tr></thead>
    <tbody>
        @forelse ($bookings as $booking)
            <tr>
                <td>
                    {{ $booking->participant_name }}
                    @if ($booking->is_guest)<span class="ui mini label">Gast</span>@endif
                    @if ($manage)
                        @canany(['approve-bookings', 'manage-payments'])
                            @php($guardian = $booking->guardian())
                            @if ($guardian)
                                <div class="text-xs text-stone-500 mt-0.5">
                                    {{ $guardian->name }} {{ $guardian->lastname }}
                                    @if ($guardian->email) · {{ $guardian->email }} @endif
                                    @if ($guardian->phone) · {{ $guardian->phone }} @endif
                                    @if ($guardian->street)
                                        · {{ $guardian->street }} {{ $guardian->house_number }}, {{ $guardian->zip }} {{ $guardian->city }}
                                    @endif
                                    @if (! $booking->usesGuardianAddress() && $booking->player?->street)
                                        <span class="text-amber-700">(Kind abw.: {{ $booking->player->street }} {{ $booking->player->house_number }}, {{ $booking->player->zip }} {{ $booking->player->city }})</span>
                                    @endif
                                </div>
                            @endif
                        @endcanany
                    @endif
                </td>
                <td>{{ $booking->participant_age ?? '—' }}</td>
                <td>{{ $booking->role?->description }}</td>
                <td>{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                <td>
                    @if ($booking->status === 'bestaetigt')
                        <span class="text-green-700">✓ bestätigt</span>
                    @elseif ($booking->status === 'abgelehnt')
                        <span class="text-red-600">abgelehnt</span>
                    @elseif ($booking->status === 'abgemeldet')
                        <span class="text-orange-600">abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
                    @else
                        <span class="text-stone-500">offen</span>
                    @endif
                </td>
                <td>
                    @if ($booking->paid)
                        <span class="text-green-700">bezahlt</span>
                    @else
                        <span class="text-stone-500">offen</span>
                    @endif
                </td>
                <td class="right aligned">
                    <div class="flex items-center justify-end gap-1">
                        @can('approve-bookings')
                            <form method="POST" action="{{ route('adventures.bookings.approval', [$adventure, $booking]) }}" data-refresh-modal>
                                @csrf @method('PATCH')
                                <button type="submit" class="ui mini icon button {{ $booking->status === 'bestaetigt' ? '' : 'green' }}"
                                        data-tooltip="{{ $booking->status === 'bestaetigt' ? 'Bestätigung zurücknehmen' : 'Bestätigen' }}" data-position="top center">
                                    <i class="check icon"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('adventures.bookings.rejection', [$adventure, $booking]) }}" data-refresh-modal>
                                @csrf @method('PATCH')
                                <button type="submit" class="ui mini icon button {{ $booking->status === 'abgelehnt' ? '' : 'orange' }}"
                                        data-tooltip="{{ $booking->status === 'abgelehnt' ? 'Ablehnung zurücknehmen' : 'Ablehnen' }}" data-position="top center">
                                    <i class="hand paper outline icon"></i>
                                </button>
                            </form>
                        @endcan
                        @can('manage-payments')
                            <form method="POST" action="{{ route('adventures.bookings.payment', [$adventure, $booking]) }}" data-refresh-modal>
                                @csrf @method('PATCH')
                                <button type="submit" class="ui mini icon button {{ $booking->paid ? '' : 'yellow' }}"
                                        data-tooltip="{{ $booking->paid ? 'Als offen markieren' : 'Als bezahlt markieren' }}" data-position="top center">
                                    <i class="coins icon"></i>
                                </button>
                            </form>
                        @endcan
                        @can('adventure.modify')
                            <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                               data-modal-stack="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                               class="ui mini icon button" data-tooltip="Bearbeiten" data-position="top center">
                                <i class="edit icon"></i>
                            </a>
                        @endcan
                        @can('adventure.cancel')
                            <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                                  data-refresh-modal data-confirm="Anmeldung stornieren?">
                                @csrf @method('DELETE')
                                <button type="submit" class="ui mini icon button red" data-tooltip="Stornieren" data-position="top center">
                                    <i class="times icon"></i>
                                </button>
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-stone-500">Noch keine Anmeldungen.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

@if ($manage)
    @can('manage-payments')
        @php($payable = $adventure->bookings->where('waitlisted', false))
        @php($paidCount = $payable->where('paid', true)->count())
        @php($openCount = $payable->count() - $paidCount)
        <p class="text-stone-600 mb-2">
            Beitrag {{ number_format($adventure->fee, 2, ',', '.') }} € · bezahlt {{ $paidCount }}/{{ $payable->count() }}
            · eingegangen {{ number_format($paidCount * $adventure->fee, 2, ',', '.') }} €
            · offen {{ number_format($openCount * $adventure->fee, 2, ',', '.') }} €
        </p>
    @endcan
@endif
