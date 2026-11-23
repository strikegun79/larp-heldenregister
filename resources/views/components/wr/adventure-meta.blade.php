{{-- Waldritter v1: Eckdaten eines Abenteuers (Termin, Ort, Beitrag).
     Termin mit Wochentag, Uhrzeit und Ende (x-wr.adventure-date), damit Eltern Bring- und Abholzeiten sehen.
     Erwartet ein Adventure mit geladener Relation "location". --}}
@props(['adventure'])

<dl {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-5 gap-y-1 text-sm']) }}>
    @if ($adventure->start_at)
        <div class="flex items-center gap-1.5">
            <dt><i class="calendar alternate icon" aria-hidden="true"></i><span class="sr-only">Termin</span></dt>
            <dd><x-wr.adventure-date :adventure="$adventure" /></dd>
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
