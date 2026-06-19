<span data-modal-title hidden>{{ $player->full_name }}</span>

@php($canEdit = auth()->user()?->can('update', $player))

<div class="ui top attached tabular menu" style="overflow-x: auto; flex-wrap: nowrap;">
    <a class="item active" data-tab="p-allg" style="white-space: nowrap;">Allgemeines</a>
    <a class="item" data-tab="p-helden" style="white-space: nowrap;">Helden</a>
    <a class="item" data-tab="p-abenteuer" style="white-space: nowrap;">Abenteuer</a>
    @if ($canEdit)<a class="item" data-tab="p-avatar" style="white-space: nowrap;">Avatar</a>@endif
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
            <div>
                <dt class="text-sm text-stone-500">Anschrift</dt>
                <dd>
                    @if (! $player->address_same_as_guardian && $player->street)
                        {{ $player->street }} {{ $player->house_number }}, {{ $player->zip }} {{ $player->city }}
                        <span class="text-xs text-stone-400">(abweichend)</span>
                    @else
                        <span class="text-stone-400 text-sm">wie erziehungsberechtigte Person</span>
                    @endif
                </dd>
            </div>
            <div><dt class="text-sm text-stone-500">E-Mail</dt><dd>{{ $player->email ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Erstellt</dt><dd>{{ optional($player->created_at)->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Status</dt><dd>{{ $player->active ? 'aktiv' : 'inaktiv' }}</dd></div>
        </dl>
    </div>
</div>

{{-- Tab: Helden (mit Helden-Foto) --}}
<div class="ui bottom attached tab segment" data-tab="p-helden">
    <div class="overflow-x-auto">
    <table class="ui very basic compact table">
        <thead><tr><th>Foto</th><th>Name</th><th>Klasse(n)</th><th class="right aligned">EP</th><th>Aktiv</th></tr></thead>
        <tbody>
            @forelse ($player->heroes as $hero)
                <tr data-modal-stack="{{ route('heroes.show', $hero) }}"
                    role="button" tabindex="0"
                    aria-label="Held {{ $hero->character_name ?? 'unbenannt' }} öffnen"
                    class="cursor-pointer hover:bg-stone-50 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]">
                    <td>
                        <img src="{{ $hero->image_url }}" alt="{{ $hero->character_name }}"
                             class="w-12 h-12 object-cover rounded border">
                    </td>
                    <td>{{ $hero->character_name ?? '—' }}</td>
                    <td>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</td>
                    <td class="right aligned">{{ number_format($hero->ep_balance, 0, ',', '.') }}</td>
                    <td onclick="event.stopPropagation()">
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
</div>

{{-- Tab: Abenteuer (besuchte Veranstaltungen) --}}
<div class="ui bottom attached tab segment" data-tab="p-abenteuer">
    @php($visited = $player->visits->sortByDesc(fn ($v) => optional($v->adventure)->start_at))
    @if ($visited->isEmpty())
        <p class="text-stone-500">Noch keine besuchten Veranstaltungen.</p>
    @else
        <div class="overflow-x-auto">
        <table class="ui very basic compact table">
            <thead><tr><th>Datum</th><th>Veranstaltung</th></tr></thead>
            <tbody>
                @foreach ($visited as $visit)
                    @continue (! $visit->adventure)
                    <tr>
                        <td>{{ optional($visit->adventure->start_at)->format('d.m.Y') ?? '—' }}</td>
                        <td><a href="{{ route('adventures.show', $visit->adventure) }}" data-modal-url="{{ route('adventures.show', $visit->adventure) }}" class="text-waldritter hover:underline">{{ $visit->adventure->name }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>

{{-- Tab: Avatar (Crop-Editor, PLAY-11) --}}
@if ($canEdit)
    <div class="ui bottom attached tab segment" data-tab="p-avatar">
        {{-- Avatar-Vorschau + Datei-Auswahl; Crop-Editor im gestapelten Modal --}}
        <img src="{{ $player->avatar_url }}" alt="Avatar"
             class="w-40 h-40 object-cover rounded border-2 border-[#5a3a22]/40 mb-3" style="aspect-ratio:1/1;">
        <div class="flex gap-2 flex-wrap mt-1">
            <label class="ui button" for="avatar-file-input" style="cursor:pointer;">
                <i class="upload icon"></i> Bild auswählen (JPG/PNG, max. 20 MB)
            </label>
            @if ($player->image)
                <form method="POST" action="{{ route('players.avatar.destroy', $player) }}"
                      data-refresh-modal
                      data-confirm="Avatar wirklich löschen?">
                    @csrf @method('DELETE')
                    <button type="submit" class="ui red button">
                        <i class="trash icon"></i> Avatar löschen
                    </button>
                </form>
            @endif
        </div>
        <input type="file" id="avatar-file-input" accept="image/jpeg,image/png"
               style="display:none"
               data-upload-url="{{ route('players.avatar', $player) }}">
        <script>
        (function () {
            var inp = document.getElementById('avatar-file-input');
            inp.addEventListener('change', function () {
                var file = this.files[0];
                if (!file) return;
                this.value = '';
                openPhotoCropper(file, inp.dataset.uploadUrl, function () {
                    if (appModalUrl) loadModalContent(appModalUrl, true);
                });
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
