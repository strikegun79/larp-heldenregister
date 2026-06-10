@php($manage = $manage ?? false)
<table class="ui very basic compact table">
    <thead><tr>
        <th>Spieler</th><th>Rolle</th><th>Liste</th>
        @if ($manage)<th>Status</th><th>Beitrag</th><th></th>@endif
    </tr></thead>
    <tbody>
        @forelse ($bookings as $booking)
            <tr>
                <td>{{ $booking->player?->full_name }}</td>
                <td>{{ $booking->role?->description }}</td>
                <td>{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                @if ($manage)
                    <td>
                        @if ($booking->approved_at)
                            <span class="text-green-700">✓ bestätigt</span>
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
                        <div class="flex items-center justify-end gap-3">
                            @can('approve-bookings')
                                <form method="POST" action="{{ route('adventures.bookings.approval', [$adventure, $booking]) }}" data-refresh-modal>
                                    @csrf @method('PATCH')
                                    <button class="{{ $booking->approved_at ? 'text-stone-600' : 'text-green-700' }} hover:underline">
                                        {{ $booking->approved_at ? 'zurücknehmen' : 'bestätigen' }}
                                    </button>
                                </form>
                            @endcan
                            @can('manage-payments')
                                <form method="POST" action="{{ route('adventures.bookings.payment', [$adventure, $booking]) }}" data-refresh-modal>
                                    @csrf @method('PATCH')
                                    <button class="{{ $booking->paid ? 'text-stone-600' : 'text-green-700' }} hover:underline">
                                        {{ $booking->paid ? 'als offen' : 'als bezahlt' }}
                                    </button>
                                </form>
                            @endcan
                            @can('adventure.modify')
                                <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                   data-modal-subview="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                   class="text-waldritter hover:underline">bearbeiten</a>
                            @endcan
                            @can('adventure.cancel')
                                <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                                      data-refresh-modal onsubmit="return confirm('Anmeldung stornieren?');">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">stornieren</button>
                                </form>
                            @endcan
                        </div>
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $manage ? 6 : 3 }}" class="text-stone-500">Noch keine Anmeldungen.</td></tr>
        @endforelse
    </tbody>
</table>

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
