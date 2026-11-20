{{-- Heldenbuch – Galerie (HERO-24, Waldritter v1). Einmal geschrieben für Akkordeon und Reiter.
     Aussagekräftige Alt-Texte, Löschen fragt einheitlich nach, Upload-Felder per Tastatur erreichbar.
     Erwartet: $hero, $canEditPhoto, $ctx (eindeutige IDs). --}}
@php($count = $hero->galleryImages->count())

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    @foreach ($hero->galleryImages as $img)
        <figure class="relative m-0">
            <img src="{{ $img->url }}" alt="Galeriebild {{ $loop->iteration }} von {{ $count }} von {{ $hero->character_name }}"
                 class="w-full h-auto rounded-lg border border-wr-border" loading="lazy">
            @if ($canEditPhoto)
                <form method="POST" action="{{ route('heroes.gallery.destroy', [$hero, $img]) }}"
                      data-refresh-modal data-confirm="Galeriebild wirklich löschen?" class="absolute top-1 right-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="wr-btn wr-btn--danger wr-btn--small !px-2.5"
                            aria-label="Galeriebild {{ $loop->iteration }} löschen">
                        <i class="trash icon m-0" aria-hidden="true"></i>
                    </button>
                </form>
            @endif
        </figure>
    @endforeach

    @if ($canEditPhoto)
        @for ($i = $count; $i < 4; $i++)
            <div>
                <input type="file" accept="image/*" id="gallery-upload-{{ $ctx }}-{{ $i }}" class="sr-only wr-file-input"
                       onchange="openPhotoCropper(this.files[0], '{{ route('heroes.gallery.store', $hero) }}', null, { aspectRatio: NaN })">
                <label for="gallery-upload-{{ $ctx }}-{{ $i }}"
                       class="flex flex-col items-center justify-center h-28 sm:h-36 border-2 border-dashed border-wr-border rounded-lg cursor-pointer
                              hover:border-wr-leather hover:bg-wr-parchment transition text-wr-ink-muted text-sm select-none">
                    <i class="camera icon text-xl mb-1" aria-hidden="true"></i>
                    Foto hinzufügen
                </label>
            </div>
        @endfor
    @elseif ($count === 0)
        <p class="text-wr-ink-muted italic col-span-2 sm:col-span-4 m-0">Noch keine Galeriebilder.</p>
    @endif
</div>
