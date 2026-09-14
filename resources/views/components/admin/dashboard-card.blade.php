@props(['card'])

<a href="{{ $card['href'] }}"
   class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
    <div class="h-36 overflow-hidden save-data-hide">
        <img src="/images/{{ $card['img'] }}" alt="" aria-hidden="true" loading="lazy"
             width="400" height="144"
             class="w-full h-full object-cover group-hover:scale-105 transition">
    </div>
    <div class="p-3 text-center">
        <div class="font-uncial text-base text-waldritter">{{ $card['title'] }}</div>
        <div class="text-xs text-stone-600">{{ $card['subtitle'] }}</div>
    </div>
</a>
