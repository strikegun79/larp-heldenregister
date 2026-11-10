{{-- Wartelistenmodus-Leiste: Kapazität + Toggle-Button (ADV-WL) --}}
@php
    $freeSlots       = $adventure->freeSlots();
    $allWaitlisted   = $adventure->bookings->where('waitlisted', true);
    $waitlistedCount = $allWaitlisted->count();
    $ageExceptions   = $allWaitlisted->filter(fn($b) => $adventure->isOutsideAgeRange($b->player))->count();
    $promotable      = $waitlistedCount - $ageExceptions;
    $inWaitlistMode  = (bool) $adventure->waitlist_mode;
@endphp
<div class="flex flex-wrap items-center gap-3 mb-3 p-2 rounded border
    {{ $inWaitlistMode ? 'bg-amber-50 border-amber-300' : 'bg-green-50 border-green-300' }}">

    {{-- Kapazitätszahlen --}}
    <span class="text-sm text-stone-700">
        <i class="users icon"></i>
        <strong>{{ $freeSlots }}</strong> freie Plätze
        @if ($promotable > 0)
            · <strong class="text-amber-700">{{ $promotable }}</strong> auf Warteliste
        @endif
        @if ($ageExceptions > 0)
            · <strong class="text-red-600">{{ $ageExceptions }}</strong> Altersausnahme(n)
        @endif
    </span>

    {{-- Modus-Badge --}}
    @if ($inWaitlistMode)
        <span class="ui small orange label">
            <i class="list ol icon"></i> Wartelistenmodus
        </span>
    @else
        <span class="ui small green label">
            <i class="check circle icon"></i> Regulärmodus
        </span>
    @endif

    {{-- Toggle-Button --}}
    @can('events.edit')
        @if ($inWaitlistMode)
            <form method="POST" action="{{ route('adventures.toggle-waitlist-mode', $adventure) }}"
                  data-refresh-modal
                  data-confirm="{{ $promotable > 0
                      ? 'Wartelistenmodus deaktivieren? ' . $promotable . ' Anmeldung(en) rücken automatisch nach.' . ($ageExceptions > 0 ? ' ' . $ageExceptions . ' Altersgrenzen-Ausnahme(n) bleiben auf der Warteliste.' : '')
                      : 'Wartelistenmodus deaktivieren? Neue Anmeldungen füllen dann freie Plätze.' . ($ageExceptions > 0 ? ' (' . $ageExceptions . ' Altersgrenzen-Ausnahme(n) bleiben auf der Warteliste.)' : '') }}"
                  class="m-0">
                @csrf @method('PATCH')
                <button type="submit" class="ui small orange basic button">
                    <i class="toggle off icon"></i> Warteliste deaktivieren
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('adventures.toggle-waitlist-mode', $adventure) }}"
                  data-refresh-modal
                  data-confirm="Wartelistenmodus aktivieren? Alle weiteren Anmeldungen kommen dann auf die Warteliste."
                  class="m-0">
                @csrf @method('PATCH')
                <button type="submit" class="ui small basic button">
                    <i class="toggle on icon"></i> Warteliste aktivieren
                </button>
            </form>
        @endif
    @endcan
</div>
