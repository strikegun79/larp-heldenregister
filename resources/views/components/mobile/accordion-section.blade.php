{{-- ARCH-003: Accordion-Abschnitt auf Basis von <details>/<summary>.
     Native HTML5, kein JS, ARIA-konform. CSS in heldenregister.css. --}}
@props(['title' => '', 'open' => false])
<details @if($open) open @endif
         class="mobile-accordion-section border border-stone-200 rounded-lg overflow-hidden">
    <summary class="flex items-center justify-between px-4 py-3 font-semibold text-stone-800
                    cursor-pointer select-none list-none bg-stone-50 hover:bg-amber-50 transition-colors">
        <span>{{ $title }}</span>
        <svg class="mobile-accordion-chevron h-4 w-4 text-stone-400 transition-transform flex-shrink-0"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </summary>
    <div class="px-4 py-3 border-t border-stone-100">
        {{ $slot }}
    </div>
</details>
