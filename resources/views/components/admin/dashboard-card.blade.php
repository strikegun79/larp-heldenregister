{{-- Verwaltungs-Kachel (Waldritter v1): ganze Karte verlinkt, Bild bleibt, Titel als H3.
     Erwartet $card = ['title', 'subtitle', 'img', 'href'] (img: Dateiname unter public/images). --}}
@props(['card'])

<x-wr.card :href="$card['href']" :image="'/images/' . $card['img']"
           :title="$card['title']" :text="$card['subtitle']" heading-level="h3" {{ $attributes }} />
