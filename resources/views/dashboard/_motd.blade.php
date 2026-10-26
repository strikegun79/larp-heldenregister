{{-- Startseiten-Nachricht (MOTD). Wegklicken wird je Inhaltsversion im Browser gemerkt.
     Klicks auf Links werden per sendBeacon gezählt; der Link selbst öffnet sich unverändert. --}}
@if ($motd)
    <div x-data="{
             @if ($motd->dismissible)
             key: '{{ $motd->dismiss_key }}',
             sichtbar: true,
             init() { try { this.sichtbar = !localStorage.getItem(this.key); } catch (e) {} },
             schliessen() { try { localStorage.setItem(this.key, '1'); } catch (e) {} this.sichtbar = false; this.$dispatch('motd-dismissed'); },
             @endif
             zaehlen(e) {
                 const link = e.target.closest('a[href]');
                 if (!link || (e.type === 'auxclick' && e.button !== 1) || !navigator.sendBeacon) return;
                 const daten = new FormData();
                 daten.append('url', link.getAttribute('href').trim());
                 daten.append('_token', document.querySelector('meta[name=csrf-token]')?.content ?? '');
                 navigator.sendBeacon('{{ route('motd.click', $motd) }}', daten);
             }
         }"
         @click="zaehlen($event)" @auxclick="zaehlen($event)"
         @if ($motd->dismissible) x-show="sichtbar" x-cloak @endif
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
