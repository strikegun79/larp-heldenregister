{{-- Waldritter v1: Termin eines Abenteuers mit Wochentag, Uhrzeit und Ende.
     Beispiele: „Sa, 14.03., 10:00 Uhr – So, 15.03.2026, 15:00 Uhr“,
     „Sa, 14.03.2026, 10:00 – 15:00 Uhr“. Uhrzeiten 00:00 entfallen (nur Datum gepflegt). --}}
@props(['adventure'])

@php
    $start = $adventure->start_at?->copy()->locale('de');
    $end = $adventure->end_at?->copy()->locale('de');
    if ($end && $start && $end->lessThanOrEqualTo($start)) {
        $end = null;
    }
    $mitZeit = fn ($d) => $d && $d->format('H:i') !== '00:00';
    $termin = null;

    if ($start) {
        if (! $end) {
            $termin = $start->isoFormat('dd, DD.MM.YYYY') . ($mitZeit($start) ? ', ' . $start->format('H:i') . ' Uhr' : '');
        } elseif ($start->isSameDay($end)) {
            $termin = $start->isoFormat('dd, DD.MM.YYYY')
                . ($mitZeit($start) ? ', ' . $start->format('H:i') . ($mitZeit($end) ? ' – ' . $end->format('H:i') : '') . ' Uhr' : '');
        } else {
            $termin = $start->isoFormat($start->isSameYear($end) ? 'dd, DD.MM.' : 'dd, DD.MM.YYYY')
                . ($mitZeit($start) ? ', ' . $start->format('H:i') . ' Uhr' : '')
                . ' – ' . $end->isoFormat('dd, DD.MM.YYYY')
                . ($mitZeit($end) ? ', ' . $end->format('H:i') . ' Uhr' : '');
        }
    }
@endphp

@if ($termin)<time {{ $attributes }} datetime="{{ $start->toIso8601String() }}">{{ $termin }}</time>@endif
