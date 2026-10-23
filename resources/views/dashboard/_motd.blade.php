{{-- Startseiten-Nachricht (MOTD). Wegklicken wird je Inhaltsversion im Browser gemerkt. --}}
@if ($motd)
    <div @if ($motd->dismissible)
             x-data="{
                 key: '{{ $motd->dismiss_key }}',
                 sichtbar: true,
                 init() { try { this.sichtbar = !localStorage.getItem(this.key); } catch (e) {} },
                 schliessen() { try { localStorage.setItem(this.key, '1'); } catch (e) {} this.sichtbar = false; this.$dispatch('motd-dismissed'); }
             }"
             x-show="sichtbar" x-cloak
         @endif
         class="ui {{ $motd->css_class }} message mb-6 sm:mb-8"
         style="display:block;@if ($motd->variant === 'neutral') background: rgb(255 255 255 / 0.6); border: 2px solid rgb(90 58 34 / 0.3); box-shadow: none; color: #292524; border-radius: .5rem;@endif"
         role="status">
        @if ($motd->dismissible)
            <i class="close icon" @click="schliessen()" role="button" tabindex="0"
               @keydown.enter="schliessen()" aria-label="Nachricht schließen"></i>
        @endif
        @if ($motd->image_url)
            @if ($motd->image_link)
                <a href="{{ $motd->image_link }}" class="block cursor-pointer"
                   @if ($motd->image_link_external) target="_blank" rel="noopener" @endif>
                    <img src="{{ $motd->image_url }}" alt="Banner – zum Link" class="w-full rounded mb-3" style="max-height: 320px; object-fit: cover;">
                </a>
            @else
                <img src="{{ $motd->image_url }}" alt="" class="w-full rounded mb-3" style="max-height: 320px; object-fit: cover;">
            @endif
        @endif
        @if ($motd->html)
            {{-- Beim Speichern per HTMLPurifier (Profil „motd") bereinigt --}}
            <div class="motd-content">{!! $motd->html !!}</div>
        @endif
    </div>
@endif
