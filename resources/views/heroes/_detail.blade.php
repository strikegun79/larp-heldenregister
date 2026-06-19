<span data-modal-title hidden>{{ $hero->character_name ?? 'Held' }}</span>

@php($learnedIds = $hero->skills->pluck('id'))
@php($canEditPhoto = auth()->user()?->can('heldenregister.edit')
    || optional($hero->player)->users->contains('id', auth()->id()))

<div id="skilltree"
     data-learn-url="{{ route('heroes.skills.store', $hero) }}"
     data-balance="{{ $hero->ep_balance }}"
     data-can-edit="{{ auth()->user()?->can('heldenregister.edit') ? 1 : 0 }}">

    <div class="ui top attached tabular menu" style="overflow-x: auto; flex-wrap: nowrap;">
        <a class="item active" data-tab="overview" style="white-space: nowrap;">Übersicht</a>
        <a class="item" data-tab="adventures" style="white-space: nowrap;">Abenteuer</a>
        @foreach ($hero->classes as $class)
            <a class="item" data-tab="cls-{{ $class->id }}" style="white-space: nowrap;">{{ $class->name }}</a>
        @endforeach
        <a class="item" data-tab="ep" style="white-space: nowrap;">EP-Verlauf</a>
    </div>

    {{-- Tab: Übersicht --}}
    <div class="ui bottom attached tab segment active" data-tab="overview">
        <div class="float-right ml-4 mb-4 text-center" style="min-width:8rem;">
            {{-- Helden-Foto (Dummy-Bild wenn keins, HERO-22) --}}
            <img src="{{ $hero->image_url }}" alt="{{ $hero->character_name }}"
                 class="h-32 w-32 object-cover rounded border-2 border-[#5a3a22]/40">

            @if ($canEditPhoto)
                <div class="flex gap-1 mt-2 justify-center">
                    {{-- Ändern: öffnet Crop-Editor im gestapelten Modal --}}
                    <label for="hero-photo-file-input" class="ui mini button" style="cursor:pointer;">
                        <i class="upload icon"></i> Ändern
                    </label>
                    <input type="file" id="hero-photo-file-input" accept="image/jpeg,image/png"
                           style="display:none"
                           data-upload-url="{{ route('heroes.photo', $hero) }}">
                    <script>
                    (function () {
                        var inp = document.getElementById('hero-photo-file-input');
                        inp.addEventListener('change', function () {
                            var file = this.files[0];
                            if (!file) return;
                            this.value = '';
                            openPhotoCropper(file, inp.dataset.uploadUrl, function () {
                                if (appModal2Url) loadStackContent(appModal2Url, true);
                                else if (appModalUrl) loadModalContent(appModalUrl, true);
                            });
                        });
                    })();
                    </script>
                    @if ($hero->image)
                        <form method="POST" action="{{ route('heroes.photo.destroy', $hero) }}"
                              data-refresh-modal
                              data-confirm="Helden-Foto wirklich löschen?">
                            @csrf @method('DELETE')
                            <button type="submit" class="ui mini red icon button"
                                    data-tooltip="Foto löschen" data-position="top center">
                                <i class="trash icon"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
        <dl class="grid grid-cols-2 gap-4 text-stone-800">
            <div><dt class="text-sm text-stone-500">Spieler</dt><dd>{{ $hero->player?->full_name ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Klassen</dt><dd>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Heimatort</dt><dd>{{ $hero->homeplace ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">EP-Saldo</dt><dd class="font-semibold">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</dd></div>
            <div><dt class="text-sm text-stone-500">EP gesamt / ausgegeben</dt><dd>{{ number_format($hero->ep_total, 0, ',', '.') }} / {{ number_format($hero->ep_spent, 0, ',', '.') }}</dd></div>
            <div><dt class="text-sm text-stone-500">Fertigkeiten / Klassen</dt><dd>{{ $hero->skills_count }} / {{ $hero->classes_count }}</dd></div>
            <div><dt class="text-sm text-stone-500">Erste Erblickung</dt><dd>{{ optional($hero->born)->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Verschollen</dt><dd>{{ optional($hero->died)->format('d.m.Y') ?? '—' }}</dd></div>
            <div>
                <dt class="text-sm text-stone-500">Status</dt>
                <dd>@if ($hero->died)<span class="text-red-700">verschollen</span>@else{{ $hero->active ? 'aktiv' : 'inaktiv' }}@endif</dd>
            </div>
        </dl>

        <a href="{{ route('heroes.sheet-pdf', $hero) }}" class="ui small button mt-3" target="_blank" rel="noopener">Charakterbogen (PDF)</a>

        @if ($hero->description)
            <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Steckbrief</h3>
            <p class="text-stone-700 whitespace-pre-line">{{ $hero->description }}</p>
        @endif

        <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Erworbene Fertigkeiten</h3>
        @forelse ($hero->skills as $skill)
            <span class="ui label">{{ $skill->name }} ({{ $skill->ep_costs }} EP)</span>
        @empty
            <p class="text-stone-500">Keine Fertigkeiten erlernt.</p>
        @endforelse

        @php($perlSummary = $hero->perl_summary)
        @if ($perlSummary->isNotEmpty())
            <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Bändchen / Perlen</h3>
            <div class="overflow-x-auto">
            <table class="ui very basic compact table" style="max-width:20rem">
                <thead><tr><th>Farbe</th><th class="right aligned">Anzahl</th></tr></thead>
                <tbody>
                    @foreach ($perlSummary as $entry)
                        <tr>
                            <td>
                                <span class="inline-block w-3 h-3 rounded-full mr-1 align-middle"
                                      style="background:{{ $entry->color->code }}"></span>
                                {{ $entry->color->name }}
                            </td>
                            <td class="right aligned font-semibold">{{ $entry->count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif

        <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Klassen</h3>
        <div class="flex flex-wrap items-center gap-2">
            @forelse ($hero->classes as $class)
                <span class="ui label">
                    {{ $class->name }}
                    @can('heldenregister.edit')
                        <form method="POST" action="{{ route('heroes.classes.destroy', [$hero, $class]) }}" data-refresh-modal class="inline"
                              data-confirm="Klasse „{{ $class->name }}” entfernen? {{ $class->ep_cost }} EP werden erstattet.">
                            @csrf @method('DELETE')
                            <button type="submit" class="ml-1 text-red-600" title="Entfernen">&times;</button>
                        </form>
                    @endcan
                </span>
            @empty
                <span class="text-stone-500">Keine Klassen.</span>
            @endforelse
        </div>

        @can('heldenregister.edit')
            @if ($availableClasses->isNotEmpty())
                {{-- HERO-20: Hinzufügen kostet EP (Abfrage vor Abzug); Korrektur fügt 0 EP hinzu. --}}
                <form method="POST" action="{{ route('heroes.classes.store', $hero) }}" data-refresh-modal class="ui form mt-3"
                      data-confirm="Sollen die EP-Kosten wirklich abgezogen werden?"
                      data-confirm-unless-id="class-free-{{ $hero->id }}"
                      data-confirm-unless-val="1">
                    @csrf
                    <input type="hidden" name="free" id="class-free-{{ $hero->id }}" value="0">
                    <div class="flex items-end gap-2 flex-wrap">
                        <div class="field !mb-0">
                            <label>Klasse hinzufügen (kostet EP)</label>
                            <select name="hero_class_id" required>
                                @foreach ($availableClasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }} (−{{ $class->ep_cost }} EP)</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="ui primary button"
                                onclick="document.getElementById('class-free-{{ $hero->id }}').value='0'">Hinzufügen</button>
                        <button type="submit" class="ui basic button"
                                onclick="document.getElementById('class-free-{{ $hero->id }}').value='1'"
                                title="Versehentlich entfernt? Ohne EP-Abzug wieder hinzufügen">Korrektur (0 EP)</button>
                    </div>
                </form>
            @endif
        @endcan

        @can('heldenregister.edit')
            <form method="POST" action="{{ route('heroes.missing', $hero) }}" data-refresh-modal class="mt-6">
                @csrf @method('PATCH')
                @if ($hero->died)
                    <button type="submit" class="ui small button">Als wiedergefunden markieren</button>
                @else
                    <button type="submit" class="ui small red button">Als verschollen markieren</button>
                @endif
            </form>
        @endcan
    </div>

    {{-- Tab: Abenteuer (bestrittene Abenteuer des Helden + Anmeldungen) --}}
    <div class="ui bottom attached tab segment" data-tab="adventures">
        <h4 class="font-uncial text-base text-waldritter mb-2">Bestrittene Abenteuer</h4>
        @if ($hero->adventure_history->isEmpty())
            <p class="text-stone-500">Noch keine Abenteuer bestritten.</p>
        @else
            <div class="overflow-x-auto">
            <table class="ui very basic compact table">
                <thead><tr><th>Datum</th><th>Abenteuer</th><th class="right aligned">EP</th></tr></thead>
                <tbody>
                    @foreach ($hero->adventure_history as $tx)
                        <tr>
                            <td>{{ optional($tx->transacted_at)->format('d.m.Y') }}</td>
                            <td>{{ $tx->adventure?->name ?? '—' }}</td>
                            <td class="right aligned text-green-600">+{{ number_format($tx->ep_count, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="right aligned">EP aus Abenteuern gesamt</th>
                        <th class="right aligned">{{ number_format($hero->adventures_ep_total, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
            </div>
        @endif

        @php($bookings = $hero->player?->bookings?->sortByDesc(fn ($b) => optional($b->adventure)->start_at) ?? collect())
        <h4 class="font-uncial text-base text-waldritter mt-6 mb-2">Anmeldungen des Spielers</h4>
        @if ($bookings->isEmpty())
            <p class="text-stone-500">Keine Anmeldungen.</p>
        @else
            <div class="overflow-x-auto">
            <table class="ui very basic compact table">
                <thead><tr><th>Abenteuer</th><th>Beginn</th><th>Liste</th></tr></thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->adventure?->name ?? '—' }}</td>
                            <td>{{ optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <p class="text-xs text-stone-400 mt-2">Anmeldungen sind spielerbezogen (alle Helden des Spielers).</p>
        @endif
    </div>

    {{-- Tabs: Fertigkeitsbaum je Klasse --}}
    @foreach ($hero->classes as $class)
        <div class="ui bottom attached tab segment" data-tab="cls-{{ $class->id }}">
            <div class="skill-map">
                <img src="{{ $class->skilltreeImage() }}" alt="Fertigkeitsbaum {{ $class->name }}" class="skill-image">
                @foreach ($class->skills as $skill)
                    @php($learned = $learnedIds->contains($skill->id))
                    @php($unset = ($skill->pivot->x_percentage == 0 && $skill->pivot->y_percentage == 0))
                    @php($px = $unset ? 6 + ($loop->index % 10) * 9 : (int) $skill->pivot->x_percentage)
                    @php($py = $unset ? 8 + intdiv($loop->index, 10) * 11 : (int) $skill->pivot->y_percentage)
                    <button type="button"
                            class="skill-marker skill-trigger {{ $learned ? 'learned' : 'unlearned' }}"
                            style="left: {{ $px }}%; top: {{ $py }}%;"
                            title="{{ $skill->name }} ({{ $skill->ep_costs }} EP)"
                            data-skill-id="{{ $skill->id }}"
                            data-skill-name="{{ $skill->name }}"
                            data-skill-desc="{{ $skill->description }}"
                            data-skill-cost="{{ $skill->ep_costs }}"
                            data-skill-learned="{{ $learned ? 1 : 0 }}"></button>
                @endforeach
            </div>

            @if ($class->skills->isEmpty())
                <p class="text-stone-500">Für diese Klasse sind keine Fertigkeiten hinterlegt.</p>
            @else
                <div class="ui middle aligned divided list skill-list">
                    @foreach ($class->skills as $skill)
                        @php($learned = $learnedIds->contains($skill->id))
                        <div class="item">
                            <div class="content">
                                <a class="skill-trigger {{ $learned ? 'text-green-700' : 'text-waldritter' }} hover:underline" style="cursor:pointer"
                                   data-skill-id="{{ $skill->id }}"
                                   data-skill-name="{{ $skill->name }}"
                                   data-skill-desc="{{ $skill->description }}"
                                   data-skill-cost="{{ $skill->ep_costs }}"
                                   data-skill-learned="{{ $learned ? 1 : 0 }}">
                                    {{ $learned ? '✓ ' : '' }}{{ $skill->name }} ({{ $skill->ep_costs }} EP)
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @can('heldenregister.edit')
                <div class="mt-3">
                    <a href="{{ route('skilltree.edit', $class) }}" class="ui tiny basic button">Positionen bearbeiten</a>
                </div>
            @endcan
        </div>
    @endforeach

    {{-- Tab: EP-Verlauf (Buchen + Historie) --}}
    <div class="ui bottom attached tab segment" data-tab="ep">
        <a href="{{ route('heroes.ep.export', $hero) }}" class="ui small button mb-3" target="_blank" rel="noopener">EP-Auszug (CSV)</a>

        @can('heldenregister.edit')
            <form method="POST" action="{{ route('heroes.ep.store', $hero) }}" class="ui form" data-refresh-modal style="margin-bottom:1rem">
                @csrf
                <div class="inline fields" style="align-items:flex-end;flex-wrap:wrap">
                    <div class="field">
                        <label>EP</label>
                        <input type="number" name="ep_count" step="1" min="1" placeholder="Anzahl" required style="width:7rem">
                    </div>
                    <div class="field" style="flex:1;min-width:12rem">
                        <label>Grund</label>
                        <select name="ep_transaction_type_id" required>
                            @foreach ($epTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->description }} ({{ $type->is_credit ? '+' : '−' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Datum</label>
                        <input type="date" name="transacted_at" style="width:10rem">
                    </div>
                    <div class="field">
                        <button type="submit" class="ui primary button">Buchen</button>
                    </div>
                </div>
            </form>
        @endcan

        <div class="overflow-x-auto">
        <table class="ui very basic compact table">
            <thead><tr><th>Datum</th><th>Art</th><th class="right aligned">EP</th></tr></thead>
            <tbody>
                @forelse ($hero->epTransactions->sortByDesc('transacted_at') as $tx)
                    <tr>
                        <td>{{ optional($tx->transacted_at)->format('d.m.Y') }}</td>
                        <td>{{ $tx->type?->description }}</td>
                        <td class="right aligned {{ $tx->type?->is_credit ? 'text-green-600' : 'text-red-600' }}">
                            {{ $tx->type?->is_credit ? '+' : '−' }}{{ number_format($tx->ep_count, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-stone-500">Keine EP-Buchungen.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<div data-modal-actions hidden>
    @can('heldenregister.edit')
        <a href="{{ route('heroes.edit', $hero) }}" data-modal-url="{{ route('heroes.edit', $hero) }}" class="ui button">Bearbeiten</a>
    @endcan
</div>
