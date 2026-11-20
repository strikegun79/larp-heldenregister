{{-- Waldritter v1: Anmeldestatus eines Abenteuers als Pill (Text + Symbol, nie nur Farbe),
     optional mit freien Plätzen. Begriffe wie auf dem Dashboard („Anmeldung möglich“ …).
     Ist "occupied_slots_count" per withCount geladen, entstehen keine weiteren Abfragen
     (Listen). Vereinfachung dort: Wartende bei freien Plätzen (Wartelistenmodus) werden
     nicht berücksichtigt – maßgeblich bleibt die Detailseite. Ohne Zählwert wird
     registrationState() verwendet.
     max_player = 0 gilt in der Buchungslogik als „voll“; dann keine Platzangabe. --}}
@props([
    'adventure',
    'showSlots' => true,
])

@php
    $statusId = (int) $adventure->event_status_id;
    $max = (int) $adventure->max_player;
    $occupied = $adventure->occupied_slots_count;
    $free = $occupied !== null ? max(0, $max - (int) $occupied) : null;

    if ($statusId === \App\Models\EventStatus::REGISTRATION_OPEN) {
        $state = $free !== null ? ($free > 0 ? 'open' : 'waitlist') : $adventure->registrationState();
    } else {
        $state = match ($statusId) {
            \App\Models\EventStatus::REGISTRATION_CLOSED => 'closed',
            \App\Models\EventStatus::CANCELLED => 'cancelled',
            \App\Models\EventStatus::COMPLETED, 50 => 'past',
            default => 'soon',
        };
    }

    [$pillClass, $icon, $label] = match ($state) {
        'open' => ['wr-pill--success', 'check circle', 'Anmeldung möglich'],
        'waitlist' => ['wr-pill--pending', 'hourglass half', 'Nur noch Warteliste'],
        'closed' => ['wr-pill--waitlist', 'lock', 'Anmeldung geschlossen'],
        'cancelled' => ['wr-pill--danger', 'ban', 'Abgesagt'],
        'past' => ['wr-pill--waitlist', 'flag checkered', 'Abenteuer vorbei'],
        default => ['wr-pill--info', 'calendar alternate', 'Anmeldung noch nicht geöffnet'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'wr-pill ' . $pillClass]) }}>
    <i class="{{ $icon }} icon" aria-hidden="true"></i>{{ $label }}
</span>
@if ($showSlots && $state === 'open' && $free !== null && $max > 0)
    <span class="wr-pill">
        <i class="users icon" aria-hidden="true"></i>Noch {{ $free }} {{ $free === 1 ? 'Platz' : 'Plätze' }} frei
    </span>
@endif
