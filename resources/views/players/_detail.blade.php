<span data-modal-title hidden>{{ $player->full_name }}</span>

@php($canEdit = auth()->user()?->can('update', $player))

{{-- UI-40: Mobile Accordion (< sm) --}}
<div class="sm:hidden space-y-2">
    <x-mobile.accordion-section title="Allgemeines" :open="true">
        <div class="flex items-start gap-3 mb-4">
            <img src="{{ $player->avatar_url }}" alt="{{ $player->full_name }}"
                 class="w-20 h-20 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0">
            <dl class="text-stone-800 text-sm space-y-1 flex-1">
                <div><dt class="text-stone-500">Status</dt><dd>{{ $player->active ? 'aktiv' : 'inaktiv' }}</dd></div>
                <div><dt class="text-stone-500">Erstellt</dt><dd>{{ optional($player->created_at)->format('d.m.Y') ?? '—' }}</dd></div>
            </dl>
        </div>
        <dl class="grid grid-cols-2 gap-3 text-stone-800 text-sm">
            <div><dt class="text-stone-500">Geburtsdatum</dt>
                <dd>{{ optional($player->dayofbirth)->format('d.m.Y') ?? '—' }}@if ($player->dayofbirth) ({{ $player->age }} J.)@endif</dd></div>
            <div><dt class="text-stone-500">Geschlecht</dt><dd>{{ $player->gender ?? '—' }}</dd></div>
            <div><dt class="text-stone-500">E-Mail</dt><dd>{{ $player->email ?? '—' }}</dd></div>
            <div>
                <dt class="text-stone-500">Anschrift</dt>
                <dd class="text-sm">
                    @if (! $player->address_same_as_guardian && $player->street)
                        {{ $player->street }} {{ $player->house_number }}, {{ $player->zip }} {{ $player->city }}
                    @else
                        <span class="text-stone-400">wie Erziehungsberechtigte</span>
                    @endif
                </dd>
            </div>
        </dl>
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Helden (' . $player->heroes->count() . ')'">
        @forelse ($player->heroes as $hero)
            <a href="{{ route('heroes.show', $hero) }}" data-modal-stack="{{ route('heroes.show', $hero) }}"
               class="flex items-center gap-3 py-2 border-b border-stone-100 last:border-0 hover:bg-amber-50 active:bg-amber-100 transition-colors rounded px-1">
                <img src="{{ $hero->image_url }}" alt="{{ $hero->character_name }}"
                     class="w-10 h-10 object-cover rounded border">
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-stone-800 text-sm truncate">{{ $hero->character_name ?? '—' }}</div>
                    <div class="text-xs text-stone-500">{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}
                        · {{ number_format($hero->ep_balance, 0, ',', '.') }} EP</div>
                </div>
                @if ($player->active_hero_id === $hero->id)
                    <span class="ui green tiny label shrink-0">aktiv</span>
                @elseif ($canEdit)
                    <form method="POST" action="{{ route('players.active-hero', $player) }}" data-refresh-modal onclick="event.stopPropagation()">
                        @csrf @method('PATCH')
                        <input type="hidden" name="hero_id" value="{{ $hero->id }}">
                        <button type="submit" class="ui tiny button">Aktiv</button>
                    </form>
                @endif
            </a>
        @empty
            <p class="text-stone-500 text-sm">Noch keine Helden.</p>
        @endforelse
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Abenteuer (' . $player->visits->count() . ')'">
        @php($visitedMobile = $player->visits->sortByDesc(fn ($v) => optional($v->adventure)->start_at))
        @forelse ($visitedMobile as $visit)
            @continue (! $visit->adventure)
            <div class="flex items-center justify-between py-2 border-b border-stone-100 last:border-0 text-sm">
                <span class="text-stone-700">{{ $visit->adventure->name }}</span>
                <span class="text-stone-400 text-xs">{{ optional($visit->adventure->start_at)->format('d.m.Y') ?? '—' }}</span>
            </div>
        @empty
            <p class="text-stone-500 text-sm">Noch keine besuchten Veranstaltungen.</p>
        @endforelse
    </x-mobile.accordion-section>

    @if ($canEdit)
    <x-mobile.accordion-section title="Avatar">
        <img src="{{ $player->avatar_url }}" alt="Avatar"
             class="w-32 h-32 object-cover rounded border-2 border-[#5a3a22]/40 mb-3">
        <p class="text-sm text-stone-500">Auf einem größeren Gerät vollständig bearbeitbar.</p>
    </x-mobile.accordion-section>
    @endif
</div>

{{-- Desktop: Fomantic-Tabs (sm+) --}}
<div class="hidden sm:block">
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
</div>

<div data-modal-actions hidden>
    @if ($canEdit)
        <a href="{{ route('players.edit', $player) }}" data-modal-url="{{ route('players.edit', $player) }}" class="ui button">Bearbeiten</a>
    @endif
</div>
