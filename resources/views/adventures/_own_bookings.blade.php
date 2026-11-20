{{-- Eigene Anmeldungen in der Abenteuer-Detailansicht (Waldritter v1): eigene Spieler und
     selbst angemeldete Gäste (Auswahl im Controller). Die Verwaltung nutzt weiterhin _bookings. --}}
@if ($visibleBookings->isEmpty())
    <p class="wr-card__text m-0">Du hast für dieses Abenteuer noch niemanden angemeldet.</p>
@else
    <ul class="space-y-3 m-0 p-0 list-none" role="list">
        @foreach ($visibleBookings as $booking)
            <x-wr.own-booking :booking="$booking" :adventure="$adventure" />
        @endforeach
    </ul>
@endif
