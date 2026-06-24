{{--
    Teamer/NSC-Übersicht im Verwaltungs-Modal (ADV-29).
    Variablen: $adventure, $teamerSignups (Collection<TeamerSignup>), $nscBookings (Collection<Booking>)
--}}

@if ($teamerSignups->isEmpty() && $nscBookings->isEmpty())
    <p class="text-stone-500 text-sm">Noch keine Teamer- oder NSC-Anmeldungen vorhanden.</p>
@else
    <x-mobile.cards-or-table>
        <table class="ui very basic compact table">
            <thead>
                <tr>
                    <th>Nutzer / Spieler</th>
                    <th>Alter</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th class="right aligned">Aktionen</th>
                </tr>
            </thead>
            <tbody>

                {{-- Teamer-Anmeldungen (teamer_signups) --}}
                @foreach ($teamerSignups as $signup)
                    <tr>
                        <td data-label="Nutzer">{{ $signup->user->name }} {{ $signup->user->lastname }}</td>
                        <td data-label="Alter">—</td>
                        <td data-label="Rolle">
                            @can('events.edit')
                                <form method="POST"
                                      action="{{ route('adventures.teamer.update-role', [$adventure, $signup]) }}"
                                      class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <select name="teamer_role" class="ui compact dropdown">
                                        <option value="">— keine —</option>
                                        @foreach (\App\Models\TeamerSignup::ROLES as $role)
                                            <option value="{{ $role }}" @selected($signup->teamer_role === $role)>{{ $role }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="ui mini primary button">OK</button>
                                </form>
                            @else
                                {{ $signup->teamer_role ?? '—' }}
                            @endcan
                        </td>
                        <td data-label="Status">
                            @if ($signup->approved_at)
                                <span class="ui mini green label">bestätigt</span>
                            @elseif ($signup->rejected_at)
                                <span class="ui mini red label">abgelehnt</span>
                            @else
                                <span class="ui mini grey label">offen</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                @can('events.edit')
                                    <form method="POST"
                                          action="{{ route('adventures.teamer.approve', [$adventure, $signup]) }}"
                                          data-refresh-modal>
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="ui mini icon button {{ $signup->approved_at ? '' : 'green' }}"
                                                data-tooltip="{{ $signup->approved_at ? 'Bestätigung zurücknehmen' : 'Bestätigen' }}"
                                                data-position="top center">
                                            <i class="check icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">{{ $signup->approved_at ? 'Zurück' : 'Bestät.' }}</span>
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('adventures.teamer.reject', [$adventure, $signup]) }}"
                                          data-refresh-modal>
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="ui mini icon button {{ $signup->rejected_at ? '' : 'orange' }}"
                                                data-tooltip="{{ $signup->rejected_at ? 'Ablehnung zurücknehmen' : 'Ablehnen' }}"
                                                data-position="top center">
                                            <i class="hand paper outline icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">{{ $signup->rejected_at ? 'Zurück' : 'Ablehnen' }}</span>
                                        </button>
                                    </form>
                                    <a href="{{ route('adventures.teamer.edit', [$adventure, $signup]) }}"
                                       data-modal-stack="{{ route('adventures.teamer.edit', [$adventure, $signup]) }}"
                                       class="ui mini icon button"
                                       data-tooltip="Bearbeiten" data-position="top center">
                                        <i class="edit icon"></i>
                                        <span class="sm:hidden ml-1 text-xs">Bearb.</span>
                                    </a>
                                @endcan
                                @can('adventure.cancel')
                                    <form method="POST"
                                          action="{{ route('adventures.teamer.destroy', [$adventure, $signup]) }}"
                                          data-refresh-modal
                                          data-confirm="Teamer-Anmeldung stornieren?">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="ui mini icon button red"
                                                data-tooltip="Stornieren" data-position="top center">
                                            <i class="times icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">Storn.</span>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- NSC-Elternteil-Buchungen --}}
                @foreach ($nscBookings as $booking)
                    <tr>
                        <td data-label="Spieler">
                            {{ $booking->is_guest
                                ? $booking->guest_name.' '.$booking->guest_lastname.' (Gast)'
                                : ($booking->player?->name.' '.$booking->player?->lastname) }}
                        </td>
                        <td data-label="Alter">{{ $booking->participant_age ?? '—' }}</td>
                        <td data-label="Rolle">Eltern-NSC</td>
                        <td data-label="Status">
                            @php($status = $booking->status ?? 'offen')
                            <span class="ui mini label {{ match($status) {
                                'bestaetigt' => 'green',
                                'abgelehnt'  => 'red',
                                'abgemeldet' => 'grey',
                                default      => 'grey',
                            } }}">{{ \App\Models\Booking::STATUS_LABELS[$status] ?? $status }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                @can('approve-bookings')
                                    <form method="POST"
                                          action="{{ route('adventures.bookings.approval', [$adventure, $booking]) }}"
                                          data-refresh-modal>
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="ui mini icon button {{ $booking->status === 'bestaetigt' ? '' : 'green' }}"
                                                data-tooltip="{{ $booking->status === 'bestaetigt' ? 'Bestätigung zurücknehmen' : 'Bestätigen' }}"
                                                data-position="top center">
                                            <i class="check icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">{{ $booking->status === 'bestaetigt' ? 'Zurück' : 'Bestät.' }}</span>
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('adventures.bookings.rejection', [$adventure, $booking]) }}"
                                          data-refresh-modal>
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="ui mini icon button {{ $booking->status === 'abgelehnt' ? '' : 'orange' }}"
                                                data-tooltip="{{ $booking->status === 'abgelehnt' ? 'Ablehnung zurücknehmen' : 'Ablehnen' }}"
                                                data-position="top center">
                                            <i class="hand paper outline icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">{{ $booking->status === 'abgelehnt' ? 'Zurück' : 'Ablehnen' }}</span>
                                        </button>
                                    </form>
                                @endcan
                                @can('adventure.modify')
                                    <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                       data-modal-stack="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                       class="ui mini icon button"
                                       data-tooltip="Bearbeiten" data-position="top center">
                                        <i class="edit icon"></i>
                                        <span class="sm:hidden ml-1 text-xs">Bearb.</span>
                                    </a>
                                @endcan
                                @can('adventure.cancel')
                                    <form method="POST"
                                          action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                                          data-refresh-modal
                                          data-confirm="Anmeldung stornieren?">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="ui mini icon button red"
                                                data-tooltip="Stornieren" data-position="top center">
                                            <i class="times icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">Storn.</span>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach

            </tbody>
        </table>
    </x-mobile.cards-or-table>
@endif
