{{-- Waldritter v1: eine eigene Anmeldung in der Abenteuer-Detailansicht.
     Status und Beitrag als Text mit Symbol, Aktionen als Buttons mit sichtbarem Text.
     Rechte, Routen und JS-Haken (data-refresh-modal, data-confirm, data-modal-stack,
     reinstate-request-trigger) entsprechen adventures/_bookings.blade.php (Detailmodus);
     die Verwaltung nutzt weiterhin _bookings. --}}
@props(['booking', 'adventure'])

@php
    $user = auth()->user();
    $canAct = $user?->can('adventure.book');

    if ($booking->waitlisted) {
        [$pillClass, $icon, $statusText] = ['wr-pill--waitlist', 'hourglass half', 'Warteliste'];
    } else {
        [$pillClass, $icon, $statusText] = match ($booking->status) {
            'bestaetigt' => ['wr-pill--success', 'check circle', 'bestätigt'],
            'abgelehnt' => ['wr-pill--danger', 'times circle', 'abgelehnt'],
            'abgemeldet' => ['wr-pill--waitlist', 'sign out alternate', 'abgemeldet' . ($booking->absence_reason_label ? ' (' . $booking->absence_reason_label . ')' : '')],
            'storniert' => ['wr-pill--danger', 'ban', 'storniert'],
            default => ['wr-pill--pending', 'clock', 'wartet auf Bestätigung'],
        };
    }

    $isTeamer = (bool) $booking->role?->is_teamer_like;
    $fee = ($booking->ermaessigung && $adventure->fee_reduced !== null) ? $adventure->fee_reduced : $adventure->fee;
@endphp

<li {{ $attributes->merge(['class' => 'wr-card p-4']) }}>
    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <h4 class="font-semibold text-base m-0 break-words">{{ $booking->participant_name }}</h4>
            @if ($booking->is_guest)
                <span class="wr-pill wr-pill--small wr-pill--info">Gast</span>
            @endif
            <span class="text-sm text-wr-ink-muted">
                {{ $booking->role?->description }}@if ($booking->participant_age) · {{ $booking->participant_age }} Jahre @endif
            </span>
        </div>

        <div class="flex flex-wrap gap-1.5">
            <span class="wr-pill {{ $pillClass }}">
                <i class="{{ $icon }} icon" aria-hidden="true"></i>{{ $statusText }}
            </span>
            @if ($isTeamer)
                <span class="wr-pill wr-pill--info"><i class="shield alternate icon" aria-hidden="true"></i>Teamer</span>
            @elseif ($adventure->fee > 0 && $booking->status !== 'storniert')
                @if ($booking->paid)
                    <span class="wr-pill wr-pill--success">
                        <i class="money bill alternate icon" aria-hidden="true"></i>{{ number_format($fee, 2, ',', '.') }} € bezahlt
                    </span>
                @else
                    <span class="wr-pill wr-pill--pending">
                        <i class="euro sign icon" aria-hidden="true"></i>{{ number_format($fee, 2, ',', '.') }} € offen
                    </span>
                @endif
                @if ($booking->ermaessigung && $adventure->fee_reduced !== null)
                    <span class="wr-pill">ermäßigt</span>
                @endif
            @endif
        </div>

        @if ($canAct)
            <div class="flex flex-wrap gap-2 mt-1">
                @if ($booking->status !== 'storniert')
                    @unless ($booking->is_guest || $booking->waitlisted)
                        @if (Gate::allows('approve-bookings') || $booking->booked_by_user_id === auth()->id())
                            <form method="POST" action="{{ route('adventures.bookings.resend-confirmation', [$adventure, $booking]) }}" data-refresh-modal>
                                @csrf
                                <button type="submit" class="wr-btn wr-btn--secondary wr-btn--small">
                                    <i class="envelope icon m-0" aria-hidden="true"></i> Bestätigung erneut senden
                                </button>
                            </form>
                        @endif
                    @endunless
                    @can('adventure.modify')
                        <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                           data-modal-stack="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                           class="wr-btn wr-btn--secondary wr-btn--small">
                            <i class="edit icon m-0" aria-hidden="true"></i> Bearbeiten
                        </a>
                    @endcan
                    @can('adventure.cancel')
                        <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                              data-refresh-modal data-confirm="Anmeldung wirklich stornieren? Diese Aktion kann nur der Projektleiter rückgängig machen.">
                            @csrf @method('DELETE')
                            <button type="submit" class="wr-btn wr-btn--danger wr-btn--small">
                                <i class="times icon m-0" aria-hidden="true"></i> Stornieren
                            </button>
                        </form>
                    @endcan
                @else
                    {{-- Stornierte Anmeldung: Rücknahme beim Projektleiter beantragen (BOOK-09) --}}
                    @if ($booking->reinstate_requested)
                        <button type="button" disabled class="wr-btn wr-btn--secondary wr-btn--small">
                            <i class="undo icon m-0" aria-hidden="true"></i> Rücknahme bereits angefragt
                        </button>
                    @else
                        <button type="button"
                                class="reinstate-request-trigger wr-btn wr-btn--secondary wr-btn--small"
                                data-url="{{ route('adventures.bookings.request-reinstate', [$adventure, $booking]) }}">
                            <i class="undo icon m-0" aria-hidden="true"></i> Rücknahme der Stornierung anfragen
                        </button>
                    @endif
                @endif
            </div>
        @endif
    </div>
</li>
