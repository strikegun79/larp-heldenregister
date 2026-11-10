<span data-modal-title hidden>Steckbrief: {{ $hero->character_name ?? 'Held' }}</span>

{{-- Avatar links, Steckbrief-Text (bis 12.000 Zeichen) fließt rechts daneben und darunter --}}
<div style="overflow: hidden;">
    <img src="{{ $hero->image_url }}" alt="{{ $hero->character_name }}"
         class="w-28 h-28 object-cover rounded shadow border-2 border-[#5a3a22]/40"
         style="float: left; margin: 0 1rem 0.5rem 0;" loading="lazy">
    @if (filled($hero->description_plain))
        {{-- Bereinigtes HTML (Purifier-Profil "steckbrief") --}}
        <div class="wr-steckbrief text-stone-700 break-words">{!! $hero->description_html !!}</div>
    @else
        <p class="text-stone-400 italic">Kein Steckbrief vorhanden.</p>
    @endif
</div>
