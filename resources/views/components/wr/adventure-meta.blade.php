{{-- Waldritter v1: Eckdaten eines Abenteuers (Datum, Ort, Beitrag).
     Erwartet ein Adventure mit geladener Relation "location". --}}
@props(['adventure'])

<dl {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-5 gap-y-1 text-sm']) }}>
    @if ($adventure->start_at)
        <div class="flex items-center gap-1.5">
            <dt><i class="calendar alternate icon" aria-hidden="true"></i><span class="sr-only">Datum</span></dt>
            <dd>{{ $adventure->start_at->format('d.m.Y') }}</dd>
        </div>
    @endif
    @if ($adventure->location)
        <div class="flex items-center gap-1.5 min-w-0">
            <dt><i class="map marker alternate icon" aria-hidden="true"></i><span class="sr-only">Ort</span></dt>
            <dd class="[overflow-wrap:anywhere]">{{ $adventure->location->titel }}</dd>
        </div>
    @endif
    <div class="flex items-center gap-1.5">
        <dt><i class="euro sign icon" aria-hidden="true"></i><span class="sr-only">Beitrag</span></dt>
        <dd>{{ $adventure->fee > 0 ? number_format($adventure->fee, 2, ',', '.') . ' €' : 'kostenlos' }}</dd>
    </div>
</dl>
