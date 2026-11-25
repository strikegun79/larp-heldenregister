{{-- Karte eines vorgeschlagenen Helden im Heldenverzeichnis (gleicher Aufbau wie die Plot-Charakter-Karte) --}}
<a href="{{ route('public.hero', $hero->public_code) }}"
   class="flex items-center gap-3 bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-3 hover:border-[#5a3a22] transition-colors">
    <img src="{{ $hero->image_url }}" alt=""
         class="h-16 w-16 object-cover rounded border border-[#5a3a22]/30 shrink-0" loading="lazy">
    <span class="min-w-0">
        <span class="block font-uncial text-waldritter text-lg leading-tight">{{ $hero->character_name }}</span>
        @if ($hero->classes->isNotEmpty())
            <span class="block text-stone-500 text-sm">{{ $hero->classes->pluck('name')->implode(', ') }}</span>
        @endif
        <span class="flex flex-wrap gap-1 mt-1">
            <span class="ui mini green label">Held</span>
        </span>
    </span>
</a>
