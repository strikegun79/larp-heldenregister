{{-- Waldritter v1: Eckdaten eines Abenteuers (Termin, Ort, Beitrag).
     Termin mit Wochentag, Uhrzeit und Ende, damit Eltern Bring- und Abholzeiten sehen.
     Uhrzeiten entfallen, wenn sie 00:00 sind (nur Datum gepflegt).
     Erwartet ein Adventure mit geladener Relation "location". --}}
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
            // Sa, 14.03.2026, 10:00 Uhr
            $termin = $start->isoFormat('dd, DD.MM.YYYY') . ($mitZeit($start) ? ', ' . $start->format('H:i') . ' Uhr' : '');
        } elseif ($start->isSameDay($end)) {
            // Sa, 14.03.2026, 10:00 – 15:00 Uhr
            $termin = $start->isoFormat('dd, DD.MM.YYYY')
                . ($mitZeit($start) ? ', ' . $start->format('H:i') . ($mitZeit($end) ? ' – ' . $end->format('H:i') : '') . ' Uhr' : '');
        } else {
            // Sa, 14.03., 10:00 Uhr – So, 15.03.2026, 15:00 Uhr
            $termin = $start->isoFormat($start->isSameYear($end) ? 'dd, DD.MM.' : 'dd, DD.MM.YYYY')
                . ($mitZeit($start) ? ', ' . $start->format('H:i') . ' Uhr' : '')
                . ' – ' . $end->isoFormat('dd, DD.MM.YYYY')
                . ($mitZeit($end) ? ', ' . $end->format('H:i') . ' Uhr' : '');
        }
    }
@endphp

<dl {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-5 gap-y-1 text-sm']) }}>
    @if ($termin)
        <div class="flex items-center gap-1.5">
            <dt><i class="calendar alternate icon" aria-hidden="true"></i><span class="sr-only">Termin</span></dt>
            <dd><time datetime="{{ $start->toIso8601String() }}">{{ $termin }}</time></dd>
        </div>
    @endif
    @if ($adventure->location)
        <div class="flex items-center gap-1.5 min-w-0">
            <dt><i class="map marker alternate icon" aria-hidden="true"></i><span class="sr-only">Ort</span></dt>
            <dd class="break-words hyphens-auto min-w-0">{{ $adventure->location->titel }}</dd>
        </div>
    @endif
    <div class="flex items-center gap-1.5">
        <dt><i class="euro sign icon" aria-hidden="true"></i><span class="sr-only">Beitrag</span></dt>
        <dd>{{ $adventure->fee > 0 ? number_format($adventure->fee, 2, ',', '.') . ' €' : 'kostenlos' }}</dd>
    </div>
</dl>
