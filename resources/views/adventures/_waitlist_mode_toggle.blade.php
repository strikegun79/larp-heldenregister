{{-- Wartelistenmodus-Leiste: Kapazität + Toggle-Button (ADV-WL) --}}
@php
    $freeSlots      = $adventure->freeSlots();
    $waitlistedCount = $adventure->bookings->where('waitlisted', true)->count();
    $inWaitlistMode  = (bool) $adventure->waitlist_mode;
@endphp
<div class="flex flex-wrap items-center gap-3 mb-3 p-2 rounded border
    {{ $inWaitlistMode ? 'bg-amber-50 border-amber-300' : 'bg-green-50 border-green-300' }}">

    {{-- Kapazitätszahlen --}}
    <span class="text-sm text-stone-700">
        <i class="users icon"></i>
        <strong>{{ $freeSlots }}</strong> freie Plätze
        @if ($waitlistedCount > 0)
            · <strong class="text-amber-700">{{ $waitlistedCount }}</strong> auf Warteliste
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
                  data-confirm="{{ $waitlistedCount > 0
                      ? 'Wartelistenmodus deaktivieren? ' . $waitlistedCount . ' wartende Anmeldung(en) rücken automatisch nach.'
                      : 'Wartelistenmodus deaktivieren? Neue Anmeldungen füllen dann freie Plätze.' }}"
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
