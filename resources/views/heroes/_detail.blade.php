<span data-modal-title hidden>{{ $hero->character_name ?? 'Held' }}</span>

@php($learnedIds = $hero->skills->pluck('id'))
@php($canEditPhoto = auth()->user()?->can('heldenregister.edit')
    || optional($hero->player)->users->contains('id', auth()->id()))
{{-- PUB-07: Sichtbarkeit/Suche ändern darf: heldenregister.edit ODER Betreuer des Spielers. --}}
@php($canManagePublic = auth()->user()?->can('heldenregister.edit')
    || optional($hero->player)->users->contains('id', auth()->id()))

<div id="skilltree"
     data-learn-url="{{ route('heroes.skills.store', $hero) }}"
     data-balance="{{ $hero->ep_balance }}"
     data-can-edit="{{ auth()->user()?->can('heldenregister.edit') ? 1 : 0 }}">

    {{-- UI-40: Mobile Accordion (< sm) --}}
    <div class="sm:hidden space-y-2">

        {{-- Übersicht --}}
        <x-mobile.accordion-section title="Übersicht" :open="true">
            <div class="flex items-start gap-3 mb-3">
                <img src="{{ $hero->image_url }}" alt="{{ $hero->character_name }}"
                     class="h-20 w-20 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0">
                <dl class="text-stone-800 text-sm space-y-1 flex-1">
                    <div>
                        <dt class="text-stone-500">Verfügbare EP</dt>
                        <dd class="font-semibold text-base">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</dd>
                        <dd class="text-xs text-stone-400">{{ number_format($hero->ep_total, 0, ',', '.') }} gesammelt / {{ number_format($hero->ep_spent, 0, ',', '.') }} für Fertigkeiten ausgegeben</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Status</dt>
                        <dd>@if ($hero->died)<span class="text-red-700">verschollen</span>@else{{ $hero->active ? 'aktiv' : 'inaktiv' }}@endif</dd>
                    </div>
                </dl>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded px-3 py-2 mb-3 text-xs text-amber-900 leading-snug">
                <strong>Was sind EP?</strong> Erfahrungspunkte sammelst du durch Abenteuer-Teilnahme. Mit EP kannst du Fertigkeiten für deinen Helden kaufen — schau in die Fertigkeitsbaum-Bereiche unten!
            </div>
            <dl class="grid grid-cols-2 gap-3 text-stone-800 text-sm mb-4">
                <div><dt class="text-stone-500">Spieler</dt><dd>{{ $hero->player?->full_name ?? '—' }}</dd></div>
                <div><dt class="text-stone-500">Klassen</dt><dd>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</dd></div>
                @if ($hero->groups->isNotEmpty())
                <div class="col-span-2">
                    <dt class="text-stone-500">Gruppen</dt>
                    <dd>{{ $hero->groups->map(fn ($g) => $g->name . ($g->pivot->role ? ' ('.$g->pivot->role.')' : ''))->implode(', ') }}</dd>
                </div>
                @endif
                <div><dt class="text-stone-500">Heimatort</dt><dd>{{ $hero->homeplace ?? '—' }}</dd></div>
                <div>
                    <dt class="text-stone-500">Fertigkeiten</dt>
                    <dd>{{ $hero->skills_count }} in {{ $hero->classes_count }} Klassen</dd>
                    <dd class="text-xs text-stone-400">Fähigkeiten, die dein Held beherrscht</dd>
                </div>
                <div>
                    <dt class="text-stone-500">Erste Erblickung</dt>
                    <dd>{{ optional($hero->born)->format('d.m.Y') ?? '—' }}</dd>
                    <dd class="text-xs text-stone-400">Geburtstag des Helden</dd>
                </div>
                <div>
                    <dt class="text-stone-500">Verschollen seit</dt>
                    <dd>{{ optional($hero->died)->format('d.m.Y') ?? '—' }}</dd>
                    <dd class="text-xs text-stone-400">Held spielt nicht mehr aktiv</dd>
                </div>
                @if ($hero->public_code)
                <div class="col-span-2">
                    <dt class="text-stone-500">Helden-Code</dt>
                    <dd class="flex flex-wrap items-center gap-2">
                        <code class="font-mono tracking-widest text-waldritter bg-stone-100 rounded px-2 py-0.5 text-sm">{{ $hero->public_code }}</code>
                        @if ($hero->public_visible)
                            <span class="text-xs text-green-700 bg-green-50 border border-green-200 rounded px-1.5 py-0.5">öffentlich</span>
                        @else
                            <span class="text-xs text-stone-500 bg-stone-100 border border-stone-200 rounded px-1.5 py-0.5">versteckt</span>
                        @endif
                    </dd>
                    @if ($canManagePublic)
                        <dd class="mt-1 flex flex-wrap gap-3">
                            <form method="POST" action="{{ route('heroes.visibility', $hero) }}" data-refresh-modal class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-waldritter hover:underline">
                                    {{ $hero->public_visible ? 'Profil verstecken' : 'Profil sichtbar machen' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('heroes.searchable', $hero) }}" data-refresh-modal class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-waldritter hover:underline">
                                    {{ $hero->public_searchable ? 'Aus Suche entfernen' : 'In Suche aufnehmen' }}
                                </button>
                            </form>
                        </dd>
                    @endif
                    {{-- PUB-07: QR/URL für Ausweis – Admin/Bürokrat sehen es auch wenn versteckt. --}}
                    @if ($hero->public_visible || auth()->user()?->can('heldenregister.edit'))
                        <dd class="mt-2 flex flex-wrap items-end gap-3">
                            @if ($hero->public_visible)
                                <canvas data-qr-url="{{ route('public.hero', $hero->public_code) }}"
                                        title="QR-Code zum öffentlichen Profil"></canvas>
                            @endif
                            <div class="text-xs text-stone-500 space-y-1">
                                <div>Öffentliches Profil{{ $hero->public_visible ? '' : ' (aktuell versteckt)' }}:</div>
                                <a href="{{ route('public.hero', $hero->public_code) }}"
                                   target="_blank" rel="noopener"
                                   class="text-waldritter hover:underline break-all {{ $hero->public_visible ? '' : 'opacity-50' }}">
                                    {{ url(route('public.hero', $hero->public_code)) }}
                                </a>
                            </div>
                        </dd>
                    @endif
                </div>
                @endif
                @can('heldenregister.edit')
                <div class="col-span-2 pt-2">
                    <dt class="text-stone-500">Ausweis-Code (Bürokrat)</dt>
                    <dd class="mt-1">
                        <form method="POST" action="{{ route('heroes.assign-code', $hero) }}" data-refresh-modal class="ui form">
                            @csrf @method('PATCH')
                            <div class="flex items-end gap-2 flex-wrap">
                                <div class="field !mb-0">
                                    <input type="text" name="code" maxlength="6" minlength="6"
                                           pattern="[A-HJ-NP-Z2-9]{6}"
                                           placeholder="XXXXXX"
                                           value="{{ old('code', $hero->public_code) }}"
                                           class="font-mono tracking-widest w-28"
                                           style="text-transform:uppercase"
                                           required>
                                </div>
                                <button type="submit" class="ui primary mini button">Zuweisen</button>
                                @if ($hero->public_code)
                                    <a href="{{ route('admin.id-cards.reprint', $hero) }}" class="ui mini basic button" target="_blank" rel="noopener">
                                        <i class="print icon"></i> Drucken
                                    </a>
                                @endif
                            </div>
                        </form>
                        @error('code') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-stone-400 mt-1">6 Zeichen aus A–Z (kein I/L/O) und 2–9</p>
                    </dd>
                </div>
                @endcan
            </dl>

            @if ($hero->description)
                <h4 class="font-uncial text-waldritter mb-1">Steckbrief</h4>
                <p class="text-stone-700 whitespace-pre-line text-sm mb-4">{{ $hero->description }}</p>
            @endif

            <h4 class="font-uncial text-waldritter mb-2">Erworbene Fertigkeiten</h4>
            @forelse ($hero->skills as $skill)
                <span class="ui small label">{{ $skill->name }} ({{ $skill->ep_costs }} EP)</span>
            @empty
                <p class="text-stone-500 text-sm">Keine Fertigkeiten erlernt.</p>
            @endforelse

            @php($perlSummary = $hero->perl_summary)
            @if ($perlSummary->isNotEmpty())
                <h4 class="font-uncial text-waldritter mt-4 mb-1">Bändchen / Perlen</h4>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-stone-700">
                    @foreach ($perlSummary as $entry)
                        <span>
                            <span class="inline-block w-3 h-3 rounded-full mr-1 align-middle" style="background:{{ $entry->color->code }}"></span>
                            {{ $entry->color->name }}: <strong>{{ $entry->count }}</strong>
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap gap-2 mt-4 items-center">
                <h4 class="font-uncial text-waldritter w-full mb-1">Klassen</h4>
                @forelse ($hero->classes as $class)
                    <span class="ui label">
                        {{ $class->name }}
                        @can('heldenregister.edit')
                            <form method="POST" action="{{ route('heroes.classes.destroy', [$hero, $class]) }}" data-refresh-modal class="inline"
                                  data-confirm="Klasse „{{ $class->name }}" entfernen? {{ $class->ep_cost }} EP werden erstattet.">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-1 text-red-600" title="Entfernen">&times;</button>
                            </form>
                        @endcan
                    </span>
                @empty
                    <span class="text-stone-500 text-sm">Keine Klassen.</span>
                @endforelse
            </div>

            @can('heldenregister.edit')
                @if ($availableClasses->isNotEmpty())
                    <form method="POST" action="{{ route('heroes.classes.store', $hero) }}" data-refresh-modal class="ui form mt-3"
                          data-confirm="Sollen die EP-Kosten wirklich abgezogen werden?"
                          data-confirm-unless-id="class-free-mobile-{{ $hero->id }}"
                          data-confirm-unless-val="1">
                        @csrf
                        <input type="hidden" name="free" id="class-free-mobile-{{ $hero->id }}" value="0">
                        <div class="flex items-end gap-2 flex-wrap">
                            <div class="field !mb-0">
                                <label>Klasse hinzufügen</label>
                                <select name="hero_class_id" required>
                                    @foreach ($availableClasses as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }} (−{{ $class->ep_cost }} EP)</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="ui primary button"
                                    onclick="document.getElementById('class-free-mobile-{{ $hero->id }}').value='0'">Hinzufügen</button>
                            <button type="submit" class="ui basic button"
                                    onclick="document.getElementById('class-free-mobile-{{ $hero->id }}').value='1'"
                                    title="Ohne EP-Abzug">Korrektur (0 EP)</button>
                        </div>
                    </form>
                @endif
            @endcan

            <div class="mt-4 flex flex-wrap gap-2 items-center">
                <a href="{{ route('heroes.sheet-pdf', $hero) }}" class="ui small button" target="_blank" rel="noopener">Charakterbogen (PDF)</a>
                @can('heldenregister.edit')
                    <form method="POST" action="{{ route('heroes.missing', $hero) }}" data-refresh-modal>
                        @csrf @method('PATCH')
                        @if ($hero->died)
                            <button type="submit" class="ui small button">Als wiedergefunden markieren</button>
                        @else
                            <button type="submit" class="ui small red button">Als verschollen markieren</button>
                        @endif
                    </form>
                @endcan
            </div>
        </x-mobile.accordion-section>

        {{-- Abenteuer --}}
        <x-mobile.accordion-section :title="'Abenteuer (' . $hero->adventure_history->count() . ')'">
            <h4 class="font-uncial text-sm text-waldritter mb-2">Bestrittene Abenteuer</h4>
            @if ($hero->adventure_history->isEmpty())
                <p class="text-stone-500 text-sm">Noch keine Abenteuer bestritten.</p>
            @else
                @foreach ($hero->adventure_history as $tx)
                    <div class="flex items-center justify-between py-1.5 border-b border-stone-100 last:border-0 text-sm">
                        <span class="text-stone-700">{{ $tx->adventure?->name ?? '—' }}</span>
                        <span class="text-green-600 font-medium shrink-0 ml-2">+{{ number_format($tx->ep_count, 0, ',', '.') }} EP</span>
                    </div>
                @endforeach
            @endif

            @php($bookingsMobile = $hero->player?->bookings?->sortByDesc(fn ($b) => optional($b->adventure)->start_at) ?? collect())
            <h4 class="font-uncial text-sm text-waldritter mt-4 mb-2">Anmeldungen des Spielers</h4>
            @if ($bookingsMobile->isEmpty())
                <p class="text-stone-500 text-sm">Keine Anmeldungen.</p>
            @else
                @foreach ($bookingsMobile as $booking)
                    <div class="flex items-center justify-between py-1.5 border-b border-stone-100 last:border-0 text-sm">
                        <span class="text-stone-700">{{ $booking->adventure?->name ?? '—' }}</span>
                        <span class="text-stone-400 text-xs shrink-0 ml-2">{{ optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—' }}</span>
                    </div>
                @endforeach
                <p class="text-xs text-stone-400 mt-2">Anmeldungen sind spielerbezogen.</p>
            @endif
        </x-mobile.accordion-section>

        {{-- Fertigkeitsbaum je Klasse --}}
        @foreach ($hero->classes as $class)
            <x-mobile.accordion-section title="Fertigkeiten: {{ $class->name }}">
                {{-- SKILL-07: Mobile View-Toggle --}}
                <div x-data="{ view: 'tree' }" class="space-y-3">
                    <div class="flex gap-2">
                        <button type="button"
                                @click="view = 'tree'"
                                :class="view === 'tree' ? 'ui primary mini button' : 'ui mini basic button'">
                            <i class="sitemap icon"></i> Baum
                        </button>
                        <button type="button"
                                @click="view = 'columns'"
                                :class="view === 'columns' ? 'ui primary mini button' : 'ui mini basic button'">
                            <i class="columns icon"></i> Stufen
                        </button>
                    </div>

                    {{-- Baum-Ansicht --}}
                    <div x-show="view === 'tree'">
                        <div class="skill-map">
                            <img src="{{ $class->skilltreeImage() }}" alt="Fertigkeitsbaum {{ $class->name }}" class="skill-image">
                            @foreach ($class->skills as $skill)
                                @php($learned = $learnedIds->contains($skill->id))
                                @php($unset = ($skill->pivot->x_percentage == 0 && $skill->pivot->y_percentage == 0))
                                @php($px = $unset ? 6 + ($loop->index % 10) * 9 : (int) $skill->pivot->x_percentage)
                                @php($py = $unset ? 8 + intdiv($loop->index, 10) * 11 : (int) $skill->pivot->y_percentage)
                                @php($missingPrereqs = $skill->prerequisites->filter(fn ($p) => ! $learnedIds->contains($p->id)))
                                @php($locked = ! $learned && $missingPrereqs->isNotEmpty())
                                <button type="button"
                                        class="skill-marker skill-trigger {{ $learned ? 'learned' : ($locked ? 'locked' : 'unlearned') }}"
                                        style="left: {{ $px }}%; top: {{ $py }}%;"
                                        title="{{ $skill->name }} ({{ $skill->ep_costs }} EP){{ $locked ? ' – gesperrt' : '' }}"
                                        data-skill-id="{{ $skill->id }}"
                                        data-skill-name="{{ $skill->name }}"
                                        data-skill-desc="{{ $skill->description }}"
                                        data-skill-cost="{{ $skill->ep_costs }}"
                                        data-skill-learned="{{ $learned ? 1 : 0 }}"
                                        data-skill-locked="{{ $locked ? 1 : 0 }}"
                                        data-skill-prereqs="{{ $locked ? $missingPrereqs->pluck('name')->join(', ') : '' }}"></button>
                            @endforeach
                        </div>
                        @if ($class->skills->isEmpty())
                            <p class="text-stone-500 text-sm">Für diese Klasse sind keine Fertigkeiten hinterlegt.</p>
                        @else
                            <div class="ui middle aligned divided list skill-list">
                                @foreach ($class->skills as $skill)
                                    @php($learned = $learnedIds->contains($skill->id))
                                    @php($missingPrereqs = $skill->prerequisites->filter(fn ($p) => ! $learnedIds->contains($p->id)))
                                    @php($locked = ! $learned && $missingPrereqs->isNotEmpty())
                                    <div class="item">
                                        <div class="content">
                                            <a class="skill-trigger {{ $learned ? 'text-green-700' : ($locked ? 'text-stone-400' : 'text-waldritter') }} hover:underline" style="cursor:pointer"
                                               data-skill-id="{{ $skill->id }}"
                                               data-skill-name="{{ $skill->name }}"
                                               data-skill-desc="{{ $skill->description }}"
                                               data-skill-cost="{{ $skill->ep_costs }}"
                                               data-skill-learned="{{ $learned ? 1 : 0 }}"
                                               data-skill-locked="{{ $locked ? 1 : 0 }}"
                                               data-skill-prereqs="{{ $locked ? $missingPrereqs->pluck('name')->join(', ') : '' }}">
                                                {{ $learned ? '✓ ' : ($locked ? '🔒 ' : '') }}{{ $skill->name }} ({{ $skill->ep_costs }} EP)
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- SKILL-07: Spalten-Ansicht nach Stufe --}}
                    <div x-show="view === 'columns'">
                        @include('heroes.partials._skill_columns', [
                            'class'      => $class,
                            'learnedIds' => $learnedIds,
                        ])
                    </div>
                </div>

                @can('heldenregister.edit')
                    <div class="mt-3">
                        <a href="{{ route('skilltree.edit', $class) }}" class="ui tiny basic button">Positionen bearbeiten</a>
                    </div>
                @endcan
            </x-mobile.accordion-section>
        @endforeach

        {{-- EP-Verlauf --}}
        <x-mobile.accordion-section title="EP-Verlauf">
            <p class="text-xs text-stone-400 mb-3">Hier siehst du alle EP-Buchungen – zum Beispiel nach einem Abenteuer oder wenn du eine Fertigkeit gekauft hast.</p>
            <a href="{{ route('heroes.ep.export', $hero) }}" class="ui small button mb-3" target="_blank" rel="noopener">EP-Auszug (CSV)</a>

            @can('heldenregister.edit')
                <form method="POST" action="{{ route('heroes.ep.store', $hero) }}" class="ui form mb-4" data-refresh-modal>
                    @csrf
                    <div class="field">
                        <label>EP</label>
                        <input type="number" name="ep_count" step="1" min="1" placeholder="Anzahl" required style="width:7rem">
                    </div>
                    <div class="field">
                        <label>Grund</label>
                        <select name="ep_transaction_type_id" required>
                            @foreach ($epTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->description }} ({{ $type->is_credit ? '+' : '−' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Datum</label>
                        <input type="date" name="transacted_at">
                    </div>
                    <button type="submit" class="ui primary button">Buchen</button>
                </form>
            @endcan

            @forelse ($hero->epTransactions->sortByDesc('transacted_at') as $tx)
                <div class="flex items-center justify-between py-1.5 border-b border-stone-100 last:border-0 text-sm">
                    <div>
                        <span class="text-stone-700">{{ $tx->type?->description }}</span>
                        <span class="text-stone-400 text-xs ml-1">{{ optional($tx->transacted_at)->format('d.m.Y') }}</span>
                    </div>
                    <span class="{{ $tx->type?->is_credit ? 'text-green-600' : 'text-red-600' }} font-medium shrink-0 ml-2">
                        {{ $tx->type?->is_credit ? '+' : '−' }}{{ number_format($tx->ep_count, 0, ',', '.') }}
                    </span>
                </div>
            @empty
                <p class="text-stone-500 text-sm">Keine EP-Buchungen.</p>
            @endforelse
        </x-mobile.accordion-section>
    </div>

    {{-- Desktop: Fomantic-Tabs (sm+) --}}
    <div class="hidden sm:block">
        <div class="ui top attached tabular menu" style="overflow-x: auto; flex-wrap: nowrap;">
            <a class="item active" data-tab="overview" style="white-space: nowrap;">Übersicht</a>
            @if ($hero->classes->isNotEmpty())
                <a class="item" data-tab="skills" style="white-space: nowrap;">
                    Fertigkeiten
                    @if ($hero->classes_count > 1)
                        <span class="ui mini circular label ml-1">{{ $hero->classes_count }}</span>
                    @endif
                </a>
            @endif
            <a class="item" data-tab="adventures" style="white-space: nowrap;">Abenteuer</a>
            <a class="item" data-tab="ep" style="white-space: nowrap;">EP-Verlauf</a>
        </div>

        {{-- Tab: Übersicht --}}
        <div class="ui bottom attached tab segment active" data-tab="overview">
            <div class="float-right ml-4 mb-4 text-center" style="min-width:8rem;">
                <img src="{{ $hero->image_url }}" alt="{{ $hero->character_name }}"
                     class="h-32 w-32 object-cover rounded border-2 border-[#5a3a22]/40">

                @if ($canEditPhoto)
                    <div class="flex gap-1 mt-2 justify-center">
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
            <div class="bg-amber-50 border border-amber-200 rounded px-3 py-2 mb-3 text-sm text-amber-900 leading-snug clear-both">
                <strong>EP (Erfahrungspunkte)</strong> sammelst du durch Abenteuer-Teilnahme und gibst sie für Fertigkeiten aus. Die Fertigkeitsbäume findest du im Tab „Fertigkeiten" oben.
            </div>
            <dl class="grid grid-cols-2 gap-4 text-stone-800">
                <div><dt class="text-sm text-stone-500">Spieler</dt><dd>{{ $hero->player?->full_name ?? '—' }}</dd></div>
                <div><dt class="text-sm text-stone-500">Klassen</dt><dd>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</dd></div>
                @if ($hero->groups->isNotEmpty())
                <div class="col-span-2">
                    <dt class="text-sm text-stone-500">Gruppen</dt>
                    <dd>{{ $hero->groups->map(fn ($g) => $g->name . ($g->pivot->role ? ' ('.$g->pivot->role.')' : ''))->implode(', ') }}</dd>
                </div>
                @endif
                <div><dt class="text-sm text-stone-500">Heimatort</dt><dd>{{ $hero->homeplace ?? '—' }}</dd></div>
                <div>
                    <dt class="text-sm text-stone-500">Verfügbare EP</dt>
                    <dd class="font-semibold">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</dd>
                    <dd class="text-xs text-stone-400">frei für Fertigkeiten</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-500">EP gesammelt / ausgegeben</dt>
                    <dd>{{ number_format($hero->ep_total, 0, ',', '.') }} / {{ number_format($hero->ep_spent, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-500">Fertigkeiten / Klassen</dt>
                    <dd>{{ $hero->skills_count }} / {{ $hero->classes_count }}</dd>
                    <dd class="text-xs text-stone-400">erlernte Fähigkeiten</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-500">Erste Erblickung</dt>
                    <dd>{{ optional($hero->born)->format('d.m.Y') ?? '—' }}</dd>
                    <dd class="text-xs text-stone-400">Geburtstag des Helden</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-500">Verschollen seit</dt>
                    <dd>{{ optional($hero->died)->format('d.m.Y') ?? '—' }}</dd>
                    <dd class="text-xs text-stone-400">Held spielt nicht mehr aktiv</dd>
                </div>
                <div>
                    <dt class="text-sm text-stone-500">Status</dt>
                    <dd>@if ($hero->died)<span class="text-red-700">verschollen</span>@else{{ $hero->active ? 'aktiv' : 'inaktiv' }}@endif</dd>
                </div>
                @if ($hero->public_code)
                <div class="col-span-2">
                    <dt class="text-sm text-stone-500">Helden-Code</dt>
                    <dd class="flex flex-wrap items-center gap-2">
                        <code class="font-mono tracking-widest text-waldritter bg-stone-100 rounded px-2 py-0.5">{{ $hero->public_code }}</code>
                        @if ($hero->public_visible)
                            <span class="text-xs text-green-700 bg-green-50 border border-green-200 rounded px-1.5 py-0.5">öffentlich</span>
                        @else
                            <span class="text-xs text-stone-500 bg-stone-100 border border-stone-200 rounded px-1.5 py-0.5">versteckt</span>
                        @endif
                    </dd>
                    @if ($canManagePublic)
                        <dd class="mt-1 flex flex-wrap gap-3">
                            <form method="POST" action="{{ route('heroes.visibility', $hero) }}" data-refresh-modal class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-waldritter hover:underline">
                                    {{ $hero->public_visible ? 'Profil verstecken' : 'Profil sichtbar machen' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('heroes.searchable', $hero) }}" data-refresh-modal class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-waldritter hover:underline">
                                    {{ $hero->public_searchable ? 'Aus Suche entfernen' : 'In Suche aufnehmen' }}
                                </button>
                            </form>
                        </dd>
                    @endif
                    {{-- PUB-07: QR/URL für Ausweis – Admin/Bürokrat sehen es auch wenn versteckt. --}}
                    @if ($hero->public_visible || auth()->user()?->can('heldenregister.edit'))
                        <dd class="mt-2 flex flex-wrap items-end gap-3">
                            @if ($hero->public_visible)
                                <canvas data-qr-url="{{ route('public.hero', $hero->public_code) }}"
                                        title="QR-Code zum öffentlichen Profil"></canvas>
                            @endif
                            <div class="text-xs text-stone-500 space-y-1">
                                <div>Öffentliches Profil{{ $hero->public_visible ? '' : ' (aktuell versteckt)' }}:</div>
                                <a href="{{ route('public.hero', $hero->public_code) }}"
                                   target="_blank" rel="noopener"
                                   class="text-waldritter hover:underline break-all {{ $hero->public_visible ? '' : 'opacity-50' }}">
                                    {{ url(route('public.hero', $hero->public_code)) }}
                                </a>
                            </div>
                        </dd>
                    @endif
                </div>
                @endif
                @can('heldenregister.edit')
                <div class="col-span-2 pt-2">
                    <dt class="text-sm text-stone-500">Ausweis-Code zuweisen (Bürokrat)</dt>
                    <dd class="mt-1">
                        <form method="POST" action="{{ route('heroes.assign-code', $hero) }}" data-refresh-modal class="ui form flex items-end gap-2 flex-wrap">
                            @csrf @method('PATCH')
                            <div class="field !mb-0">
                                <input type="text" name="code" maxlength="6" minlength="6"
                                       pattern="[A-HJ-NP-Z2-9]{6}"
                                       placeholder="XXXXXX"
                                       value="{{ old('code', $hero->public_code) }}"
                                       class="font-mono tracking-widest w-28"
                                       style="text-transform:uppercase"
                                       required>
                            </div>
                            <button type="submit" class="ui primary small button">Code zuweisen</button>
                            @if ($hero->public_code)
                                <a href="{{ route('admin.id-cards.reprint', $hero) }}" class="ui small basic button" target="_blank" rel="noopener">
                                    <i class="print icon"></i> Ausweis drucken
                                </a>
                            @endif
                        </form>
                        @error('code') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-stone-400 mt-1">6 Zeichen aus A–Z (kein I/L/O) und 2–9</p>
                    </dd>
                </div>
                @endcan
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
                                  data-confirm="Klasse „{{ $class->name }}" entfernen? {{ $class->ep_cost }} EP werden erstattet.">
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

        {{-- Tab: Abenteuer --}}
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

        {{-- Tab: Fertigkeiten (alle Klassen zusammengefasst, UI-33) --}}
        @if ($hero->classes->isNotEmpty())
            <div class="ui bottom attached tab segment" data-tab="skills"
                 x-data="{ activeClass: {{ $hero->classes->first()->id }}, view: 'tree' }">

                {{-- Klassen-Pills zur Auswahl --}}
                @if ($hero->classes->count() > 1)
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach ($hero->classes as $class)
                            <button type="button"
                                    @click="activeClass = {{ $class->id }}"
                                    :class="activeClass === {{ $class->id }}
                                        ? 'ui primary label'
                                        : 'ui label'"
                                    class="cursor-pointer">
                                {{ $class->name }}
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- SKILL-07: View-Toggle Baum/Spalten --}}
                <div class="flex gap-2 mb-4">
                    <button type="button"
                            @click="view = 'tree'"
                            :class="view === 'tree' ? 'ui primary mini button' : 'ui mini basic button'">
                        <i class="sitemap icon"></i> Baum
                    </button>
                    <button type="button"
                            @click="view = 'columns'"
                            :class="view === 'columns' ? 'ui primary mini button' : 'ui mini basic button'">
                        <i class="columns icon"></i> Stufen
                    </button>
                </div>

                {{-- Fertigkeitsbaum je Klasse --}}
                @foreach ($hero->classes as $class)
                    <div x-show="activeClass === {{ $class->id }}">
                        @if ($hero->classes->count() === 1)
                            <h3 class="font-uncial text-lg text-waldritter mb-3">{{ $class->name }}</h3>
                        @endif

                        {{-- Baum-Ansicht (Bild) --}}
                        <div x-show="view === 'tree'">
                            <div class="skill-map">
                                <img src="{{ $class->skilltreeImage() }}" alt="Fertigkeitsbaum {{ $class->name }}" class="skill-image">
                                @foreach ($class->skills as $skill)
                                    @php($learned = $learnedIds->contains($skill->id))
                                    @php($unset = ($skill->pivot->x_percentage == 0 && $skill->pivot->y_percentage == 0))
                                    @php($px = $unset ? 6 + ($loop->index % 10) * 9 : (int) $skill->pivot->x_percentage)
                                    @php($py = $unset ? 8 + intdiv($loop->index, 10) * 11 : (int) $skill->pivot->y_percentage)
                                    @php($missingPrereqs = $skill->prerequisites->filter(fn ($p) => ! $learnedIds->contains($p->id)))
                                    @php($locked = ! $learned && $missingPrereqs->isNotEmpty())
                                    <button type="button"
                                            class="skill-marker skill-trigger {{ $learned ? 'learned' : ($locked ? 'locked' : 'unlearned') }}"
                                            style="left: {{ $px }}%; top: {{ $py }}%;"
                                            title="{{ $skill->name }} ({{ $skill->ep_costs }} EP){{ $locked ? ' – gesperrt' : '' }}"
                                            data-skill-id="{{ $skill->id }}"
                                            data-skill-name="{{ $skill->name }}"
                                            data-skill-desc="{{ $skill->description }}"
                                            data-skill-cost="{{ $skill->ep_costs }}"
                                            data-skill-learned="{{ $learned ? 1 : 0 }}"
                                            data-skill-locked="{{ $locked ? 1 : 0 }}"
                                            data-skill-prereqs="{{ $locked ? $missingPrereqs->pluck('name')->join(', ') : '' }}"></button>
                                @endforeach
                            </div>

                            @if ($class->skills->isEmpty())
                                <p class="text-stone-500">Für diese Klasse sind keine Fertigkeiten hinterlegt.</p>
                            @else
                                <div class="ui middle aligned divided list skill-list">
                                    @foreach ($class->skills as $skill)
                                        @php($learned = $learnedIds->contains($skill->id))
                                        @php($missingPrereqs = $skill->prerequisites->filter(fn ($p) => ! $learnedIds->contains($p->id)))
                                        @php($locked = ! $learned && $missingPrereqs->isNotEmpty())
                                        <div class="item">
                                            <div class="content">
                                                <a class="skill-trigger {{ $learned ? 'text-green-700' : ($locked ? 'text-stone-400' : 'text-waldritter') }} hover:underline" style="cursor:pointer"
                                                   data-skill-id="{{ $skill->id }}"
                                                   data-skill-name="{{ $skill->name }}"
                                                   data-skill-desc="{{ $skill->description }}"
                                                   data-skill-cost="{{ $skill->ep_costs }}"
                                                   data-skill-learned="{{ $learned ? 1 : 0 }}"
                                                   data-skill-locked="{{ $locked ? 1 : 0 }}"
                                                   data-skill-prereqs="{{ $locked ? $missingPrereqs->pluck('name')->join(', ') : '' }}">
                                                    {{ $learned ? '✓ ' : ($locked ? '🔒 ' : '') }}{{ $skill->name }} ({{ $skill->ep_costs }} EP)
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- SKILL-07: Spalten-Ansicht nach Stufe --}}
                        <div x-show="view === 'columns'">
                            @include('heroes.partials._skill_columns', [
                                'class'      => $class,
                                'learnedIds' => $learnedIds,
                            ])
                        </div>

                        @can('heldenregister.edit')
                            <div class="mt-3">
                                <a href="{{ route('skilltree.edit', $class) }}" class="ui tiny basic button">Positionen bearbeiten</a>
                            </div>
                        @endcan
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Tab: EP-Verlauf --}}
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
    </div>{{-- /hidden sm:block --}}

</div>{{-- /#skilltree --}}

<div data-modal-actions hidden>
    @can('heldenregister.edit')
        <button type="button" data-modal-url="{{ route('heroes.edit', $hero) }}" class="ui button">Bearbeiten</button>
    @endcan
</div>
