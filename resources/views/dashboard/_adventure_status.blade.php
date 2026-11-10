{{-- Freie Plätze (grün > 5, gelb 1–5, rot 0) und Anmeldestatus des nächsten Abenteuers. --}}
@php
    $free = $slots['free'];
    $slotColor = $free === 0 ? 'text-red-700' : ($free <= 5 ? 'text-amber-600' : 'text-green-700');
    $badge = [
        'open'     => ['label' => 'Anmeldung offen',       'class' => 'bg-green-100 text-green-800', 'icon' => 'check circle'],
        'waitlist' => ['label' => 'Warteliste',            'class' => 'bg-amber-100 text-amber-800', 'icon' => 'hourglass half'],
        'closed'   => ['label' => 'Anmeldung geschlossen', 'class' => 'bg-red-100 text-red-800',     'icon' => 'lock'],
    ][$slots['state']];
@endphp
<div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm {{ $class ?? '' }}">
    <span class="inline-flex items-center gap-1.5 font-semibold {{ $slotColor }}">
        <i class="users icon" aria-hidden="true"></i>
        @if ($slots['max'] > 0)
            {{ $free }} von {{ $slots['max'] }} {{ $slots['max'] === 1 ? 'Platz' : 'Plätzen' }} frei
        @else
            {{ $free }} {{ $free === 1 ? 'Platz' : 'Plätze' }} frei
        @endif
    </span>
    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full {{ $badge['class'] }}">
        <i class="{{ $badge['icon'] }} icon" aria-hidden="true"></i>{{ $badge['label'] }}
    </span>
</div>
