{{-- Belegung eines Abenteuers in der Verwaltungsliste als Text (nicht nur Farbe/Tooltip).
     Erwartet $adventure mit active_bookings_count, waitlisted_bookings_count, active_teamer_signups_count. --}}
<span class="inline-flex flex-wrap items-center gap-1.5">
    <span class="wr-pill wr-pill--small">
        <i class="users icon" aria-hidden="true"></i>{{ $adventure->active_bookings_count }} von <span title="Maximalplätze">{{ $adventure->max_player }}</span> Plätzen
    </span>
    @if ($adventure->waitlisted_bookings_count > 0)
        <span class="wr-pill wr-pill--small wr-pill--pending">
            <i class="hourglass half icon" aria-hidden="true"></i>+{{ $adventure->waitlisted_bookings_count }} Warteliste
        </span>
    @endif
    <span class="wr-pill wr-pill--small wr-pill--info">
        <i class="shield alternate icon" aria-hidden="true"></i>{{ $adventure->active_teamer_signups_count }} Teamer
    </span>
</span>
