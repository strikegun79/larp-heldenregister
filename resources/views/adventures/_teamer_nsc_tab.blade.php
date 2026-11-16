{{--
    Teamer/NSC-Übersicht im Verwaltungs-Modal (ADV-29).
    Variablen: $adventure, $teamerSignups (Collection<TeamerSignup>), $nscBookings (Collection<Booking>)
--}}

@if ($teamerSignups->isEmpty() && $nscBookings->isEmpty())
    <p class="text-stone-500 text-sm">Noch keine Teamer- oder NSC-Anmeldungen vorhanden.</p>
@else
    <x-mobile.cards-or-table class="min-h-48">
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
                    {{-- Zeilenklick öffnet den Inspektor mit allen Aktionen (wie bei den NSC-Eltern) --}}
                    <tr data-inspector-url="{{ route('adventures.teamer.inspector', [$adventure, $signup]) }}"
                        tabindex="0" class="booking-row-clickable"
                        aria-label="Details zur Teamer-Anmeldung von {{ $signup->user->name }} {{ $signup->user->lastname }} öffnen">
                        <td data-label="Nutzer">{{ $signup->user->name }} {{ $signup->user->lastname }}</td>
                        <td data-label="Alter">—</td>
                        <td data-label="Rolle">
                            @can('events.edit')
                                <form method="POST"
                                      action="{{ route('adventures.teamer.update-role', [$adventure, $signup]) }}"
                                      data-refresh-modal>
                                    @csrf @method('PATCH')
                                    <select name="event_role_id" class="ui compact"
                                            onchange="this.closest('form').requestSubmit()">
                                        <option value="">— keine —</option>
                                        @foreach (\App\Models\EventRole::forTeamer()->orderBy('id')->get() as $role)
                                            <option value="{{ $role->id }}" @selected($signup->event_role_id === $role->id)>{{ $role->description }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                {{ $signup->eventRole?->description ?? '—' }}
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
                            {{-- Bestätigen, Ablehnen, Mail, Stornieren liegen im Inspektor --}}
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                @can('events.edit')
                                    <a href="{{ route('adventures.teamer.edit', [$adventure, $signup]) }}"
                                       data-modal-stack="{{ route('adventures.teamer.edit', [$adventure, $signup]) }}"
                                       class="ui mini icon button"
                                       data-tooltip="Bearbeiten" data-position="top center">
                                        <i class="edit icon"></i>
                                        <span class="sm:hidden ml-1 text-xs">Bearb.</span>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- NSC-Elternteil-Buchungen --}}
                @foreach ($nscBookings as $booking)
                    {{-- Zeilenklick öffnet den Inspektor mit allen Aktionen (wie bei den Teilnehmern) --}}
                    <tr data-inspector-url="{{ route('adventures.bookings.inspector', [$adventure, $booking]) }}"
                        tabindex="0" class="booking-row-clickable"
                        aria-label="Details zur Anmeldung von {{ $booking->participant_name }} öffnen">
                        <td data-label="Spieler">
                            {{ $booking->is_guest
                                ? $booking->guest_name.' '.$booking->guest_lastname.' (Gast)'
                                : ($booking->player?->name.' '.$booking->player?->lastname) }}
                        </td>
                        <td data-label="Alter">{{ $booking->participant_age ?? '—' }}</td>
                        <td data-label="Rolle">{{ $booking->role?->description ?? '—' }}</td>
                        <td data-label="Status">
                            @if ($booking->waitlisted)
                                <span class="ui mini label yellow">Warteliste</span>
                            @else
                                @php($status = $booking->status ?? 'offen')
                                <span class="ui mini label {{ match($status) {
                                    'bestaetigt' => 'green',
                                    'abgelehnt'  => 'red',
                                    'abgemeldet' => 'grey',
                                    default      => 'grey',
                                } }}">{{ \App\Models\Booking::STATUS_LABELS[$status] ?? $status }}</span>
                            @endif
                        </td>
                        <td>
                            {{-- Bestätigen, Ablehnen, Mail, Stornieren/Wiederherstellen liegen im Inspektor --}}
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                @if ($booking->status !== 'storniert')
                                    @can('adventure.modify')
                                        <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                           data-modal-stack="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                                           class="ui mini icon button"
                                           data-tooltip="Bearbeiten" data-position="top center">
                                            <i class="edit icon"></i>
                                            <span class="sm:hidden ml-1 text-xs">Bearb.</span>
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach

            </tbody>
        </table>
    </x-mobile.cards-or-table>
@endif
