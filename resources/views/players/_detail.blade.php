<span data-modal-title hidden>{{ $player->full_name }}</span>

@php($canEdit = auth()->user()?->can('update', $player))

<div class="ui top attached tabular menu">
    <a class="item active" data-tab="p-allg">Allgemeines</a>
    <a class="item" data-tab="p-helden">Helden</a>
    <a class="item" data-tab="p-abenteuer">Abenteuer</a>
    @if ($canEdit)<a class="item" data-tab="p-avatar">Avatar</a>@endif
</div>

{{-- Tab: Allgemeines --}}
<div class="ui bottom attached tab segment active" data-tab="p-allg">
    <div class="flex gap-4 items-start">
        <img src="{{ $player->avatar_url }}" alt="{{ $player->full_name }}"
             class="w-40 h-40 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0" style="aspect-ratio:1/1;">
        <dl class="grid grid-cols-1 gap-2 text-stone-800">
            <div><dt class="text-sm text-stone-500">Geburtsdatum</dt>
                <dd>{{ optional($player->dayofbirth)->format('d.m.Y') ?? '—' }}@if ($player->dayofbirth) ({{ $player->age }} Jahre)@endif</dd></div>
            <div><dt class="text-sm text-stone-500">Geschlecht</dt><dd>{{ $player->gender ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Wohnort</dt><dd>{{ $player->place ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">E-Mail</dt><dd>{{ $player->email ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Erstellt</dt><dd>{{ optional($player->created_at)->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Status</dt><dd>{{ $player->active ? 'aktiv' : 'inaktiv' }}</dd></div>
        </dl>
    </div>
</div>

{{-- Tab: Helden (mit Helden-Foto) --}}
<div class="ui bottom attached tab segment" data-tab="p-helden">
    <table class="ui very basic compact table">
        <thead><tr><th>Foto</th><th>Name</th><th>Klasse(n)</th><th class="right aligned">EP</th><th>Aktiv</th></tr></thead>
        <tbody>
            @forelse ($player->heroes as $hero)
                <tr>
                    <td>
                        @if ($hero->image_url)
                            <img src="{{ $hero->image_url }}" alt="" class="w-12 h-12 object-cover rounded border">
                        @else
                            <span class="text-stone-400">—</span>
                        @endif
                    </td>
                    {{-- Helden-Ansicht als gestapeltes Modal (PLAY-11). --}}
                    <td><a href="{{ route('heroes.show', $hero) }}" data-modal-stack="{{ route('heroes.show', $hero) }}" class="text-indigo-700 hover:underline">{{ $hero->character_name ?? '—' }}</a></td>
                    <td>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</td>
                    <td class="right aligned">{{ number_format($hero->ep_balance, 0, ',', '.') }}</td>
                    <td>
                        @if ($player->active_hero_id === $hero->id)
                            <span class="ui green label">aktiv</span>
                        @elseif ($canEdit)
                            <form method="POST" action="{{ route('players.active-hero', $player) }}" data-refresh-modal style="display:inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="hero_id" value="{{ $hero->id }}">
                                <button type="submit" class="ui tiny button">Aktiv setzen</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-stone-500">Noch keine Helden.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Tab: Abenteuer (besuchte Veranstaltungen) --}}
<div class="ui bottom attached tab segment" data-tab="p-abenteuer">
    @php($visited = $player->visits->sortByDesc(fn ($v) => optional($v->adventure)->start_at))
    @if ($visited->isEmpty())
        <p class="text-stone-500">Noch keine besuchten Veranstaltungen.</p>
    @else
        <table class="ui very basic compact table">
            <thead><tr><th>Datum</th><th>Veranstaltung</th></tr></thead>
            <tbody>
                @foreach ($visited as $visit)
                    @continue (! $visit->adventure)
                    <tr>
                        <td>{{ optional($visit->adventure->start_at)->format('d.m.Y') ?? '—' }}</td>
                        <td><a href="{{ route('adventures.show', $visit->adventure) }}" data-modal-url="{{ route('adventures.show', $visit->adventure) }}" class="text-indigo-700 hover:underline">{{ $visit->adventure->name }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Tab: Avatar (Crop-Editor, PLAY-11) --}}
@if ($canEdit)
    <div class="ui bottom attached tab segment" data-tab="p-avatar">
        {{-- Vorschau des aktuellen Avatars + Datei-Auswahl --}}
        <div id="avatar-preview-wrap">
            <img id="avatar-current-img" src="{{ $player->avatar_url }}" alt="Avatar"
                 class="w-40 h-40 object-cover rounded border-2 border-[#5a3a22]/40 mb-3" style="aspect-ratio:1/1;">
            <label class="ui button" for="avatar-file-input" style="cursor:pointer;">
                <i class="upload icon"></i> Bild auswählen (JPG/PNG, max. 20 MB)
            </label>
            <input type="file" id="avatar-file-input" accept="image/jpeg,image/png" class="hidden" style="display:none">
        </div>

        {{-- Crop-Editor (zunächst versteckt) --}}
        <div id="avatar-editor-wrap" style="display:none">
            <div style="max-height:340px; overflow:hidden; background:#111; border-radius:.4rem;">
                <img id="avatar-editor-img" src="" alt="Zuschnitt" style="display:block; max-width:100%;">
            </div>
            <p class="text-sm text-stone-500 mt-2">Rahmen verschieben und anpassen, dann „Übernehmen" klicken.</p>
            <div class="flex gap-2 mt-2">
                <button type="button" id="avatar-crop-save" class="ui primary button">
                    <i class="check icon"></i> Übernehmen
                </button>
                <button type="button" id="avatar-crop-cancel" class="ui button">Abbrechen</button>
            </div>
        </div>

        <script>
        (function () {
            var uploadUrl  = @json(route('players.avatar', $player));
            var cropper    = null;
            var fileInput  = document.getElementById('avatar-file-input');
            var previewEl  = document.getElementById('avatar-preview-wrap');
            var editorEl   = document.getElementById('avatar-editor-wrap');
            var editorImg  = document.getElementById('avatar-editor-img');

            fileInput.addEventListener('change', function () {
                var file = this.files[0];
                if (!file) return;
                if (file.size > 20 * 1024 * 1024) {
                    showToast('Bild zu groß (max. 20 MB).', 'error');
                    this.value = '';
                    return;
                }
                var reader = new FileReader();
                reader.onload = function (e) {
                    editorImg.src = e.target.result;
                    previewEl.style.display = 'none';
                    editorEl.style.display  = '';
                    if (cropper) { cropper.destroy(); }
                    cropper = new Cropper(editorImg, {
                        aspectRatio:  1,
                        viewMode:     1,
                        autoCropArea: 1,
                        background:   false,
                        responsive:   true,
                    });
                    // Modal-Größe nach Inhaltswechsel aktualisieren.
                    setTimeout(function () { $('#app-modal').modal('refresh'); }, 300);
                };
                reader.readAsDataURL(file);
            });

            document.getElementById('avatar-crop-cancel').addEventListener('click', function () {
                if (cropper) { cropper.destroy(); cropper = null; }
                editorImg.src      = '';
                editorEl.style.display  = 'none';
                previewEl.style.display = '';
                fileInput.value = '';
            });

            document.getElementById('avatar-crop-save').addEventListener('click', function () {
                if (!cropper) return;
                var btn = this;
                btn.classList.add('loading', 'disabled');
                cropper.getCroppedCanvas({ width: 400, height: 400 }).toBlob(function (blob) {
                    var fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                    fd.append('image', blob, 'avatar.jpg');
                    fetch(uploadUrl, {
                        method:  'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body:    fd,
                    })
                    .then(function (r) { return r.json().catch(function () { return {}; }); })
                    .then(function (data) {
                        showToast(data.message || 'Avatar gespeichert.', 'success');
                        if (appModalUrl) loadModalContent(appModalUrl, true);
                    })
                    .catch(function () { showToast('Netzwerkfehler.', 'error'); })
                    .finally(function () { btn.classList.remove('loading', 'disabled'); });
                }, 'image/jpeg', 0.85);
            });
        })();
        </script>
    </div>
@endif

<div data-modal-actions hidden>
    @if ($canEdit)
        <a href="{{ route('players.edit', $player) }}" data-modal-url="{{ route('players.edit', $player) }}" class="ui button">Bearbeiten</a>
    @endif
</div>
