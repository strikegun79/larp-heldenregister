{{-- Dropdown der Navigation (Waldritter v1): schließt mit Escape und bei Klick daneben,
     der Fokus kehrt zum Auslöser zurück; aria-expanded am Auslöser-Button. --}}
@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'ltr:origin-top-left rtl:origin-top-right start-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-top';
        break;
    case 'right':
    default:
        $alignmentClasses = 'ltr:origin-top-right rtl:origin-top-left end-0';
        break;
}

switch ($width) {
    case '48':
        $width = 'w-48';
        break;
}
@endphp

<div class="relative"
     x-data="{
         open: false,
         schliessen(fokus) {
             this.open = false;
             if (fokus) this.$nextTick(() => this.$refs.trigger.querySelector('button')?.focus());
         }
     }"
     x-init="
         const aria = (v) => $refs.trigger?.querySelector('button')?.setAttribute('aria-expanded', v ? 'true' : 'false');
         $nextTick(() => aria(false));
         $watch('open', aria);
     "
     @click.outside="open = false"
     @keydown.escape.window="if (open) schliessen(true)"
     @close.stop="open = false">
    <div x-ref="trigger" @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
