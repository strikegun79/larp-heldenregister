{{-- Issue #30/#34/#36: Karte eines Plot-Charakters (Suchtreffer und Vorschläge) --}}
<a href="{{ route('public.plot-character', $character) }}"
   class="flex items-center gap-3 bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-3 hover:border-[#5a3a22] transition-colors">
    @if ($character->imageUrl())
        <img src="{{ $character->imageUrl() }}" alt=""
             class="h-16 w-16 object-cover rounded border border-[#5a3a22]/30 shrink-0" loading="lazy">
    @else
        <span class="h-16 w-16 rounded border border-[#5a3a22]/30 bg-amber-50 flex items-center justify-center shrink-0" aria-hidden="true">
            <i class="user secret icon text-[#5a3a22] m-0 text-2xl"></i>
        </span>
    @endif
    <span class="min-w-0">
        <span class="block font-uncial text-waldritter text-lg leading-tight">{{ $character->name }}</span>
        @if ($character->title)
            <span class="block text-stone-500 text-sm">{{ $character->title }}</span>
        @endif
        {{-- Issue #34: Status und Volk als kurze Kennzeichen --}}
        <span class="flex flex-wrap gap-1 mt-1">
            <span class="ui mini {{ $character->statusColor() }} label">{{ $character->statusLabel() }}</span>
            @if ($character->species !== 'unknown')
                <span class="ui mini basic label">{{ $character->speciesLabel() }}</span>
            @endif
        </span>
    </span>
</a>
