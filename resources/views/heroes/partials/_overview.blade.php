{{-- Heldenbuch – Übersicht (Waldritter v1). Einmal geschrieben für Akkordeon (Handy) und Reiter (Desktop).
     $ctx ('m' oder 'd') macht IDs eindeutig, weil die Teilansicht zweimal im Dokument steht.
     Erwartet: $hero, $ctx, $canEditPhoto, $canManagePublic. --}}
@php
    $perlByClass = $hero->perl_summary_by_class;
    $adventureCount = $hero->adventure_history->count();
    $photoInputId = 'hero-photo-file-input-' . $ctx;
@endphp

<div class="space-y-6">

    {{-- Foto + Schatzkiste (verfügbare Erfahrungspunkte) --}}
    <div class="flex flex-col min-[480px]:flex-row gap-4 items-start">
        <div class="shrink-0 flex flex-col items-center gap-2">
            <img src="{{ $hero->image_url }}" alt="Bild von {{ $hero->character_name }}"
                 class="h-28 w-28 sm:h-32 sm:w-32 object-cover rounded-xl border-2 border-wr-border" loading="lazy">

            @if ($canEditPhoto)
                <div class="flex flex-wrap gap-1.5 justify-center">
                    {{-- Datei-Feld per Tastatur erreichbar; der Fokus wird am Label sichtbar (wr-file-input) --}}
                    <input type="file" id="{{ $photoInputId }}" accept="image/jpeg,image/png" class="sr-only wr-file-input"
                           data-upload-url="{{ route('heroes.photo', $hero) }}">
                    <label for="{{ $photoInputId }}" class="wr-btn wr-btn--secondary wr-btn--small cursor-pointer">
                        <i class="upload icon m-0" aria-hidden="true"></i> Foto ändern
                    </label>
                    <script>
                    (function () {
                        var inp = document.getElementById('{{ $photoInputId }}');
                        inp.addEventListener('change', function () {
                            var file = this.files[0];
                            if (!file) return;
                            this.value = '';
                            openPhotoCropper(file, inp.dataset.uploadUrl, function () {
                                if (appModal2Url) loadStackContent(appModal2Url, true);
                                else if (appModalUrl) loadModalContent(appModalUrl, true);
                                else window.location.reload();
                            });
                        });
                    })();
                    </script>
                    @if ($hero->image)
                        <form method="POST" action="{{ route('heroes.photo.destroy', $hero) }}"
                              data-refresh-modal data-confirm="Helden-Foto wirklich löschen?">
                            @csrf @method('DELETE')
                            <button type="submit" class="wr-btn wr-btn--danger wr-btn--small" aria-label="Foto löschen">
                                <i class="trash icon m-0" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex-1 min-w-0 w-full space-y-3">
            <div class="wr-treasure">
                <span class="wr-treasure__label"><i class="gem icon m-0" aria-hidden="true"></i> Verfügbare Erfahrungspunkte</span>
                <span class="wr-treasure__value">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</span>
                <span class="wr-treasure__sub">
                    {{ number_format($hero->ep_total, 0, ',', '.') }} gesammelt · {{ number_format($hero->ep_spent, 0, ',', '.') }} ausgegeben
                </span>
            </div>

            <ul class="flex flex-wrap gap-1.5 m-0 p-0 list-none" role="list" aria-label="Fortschritt">
                <li class="wr-pill"><i class="star icon" aria-hidden="true"></i>{{ $hero->skills_count }} {{ $hero->skills_count === 1 ? 'Fertigkeit' : 'Fertigkeiten' }}</li>
                <li class="wr-pill"><i class="shield alternate icon" aria-hidden="true"></i>{{ $hero->classes_count }} {{ $hero->classes_count === 1 ? 'Klasse' : 'Klassen' }}</li>
                <li class="wr-pill"><i class="map icon" aria-hidden="true"></i>{{ $adventureCount }} Abenteuer bestritten</li>
            </ul>

            <p class="text-sm text-wr-ink-muted m-0">
                <strong class="text-wr-ink">Was sind EP?</strong>
                Erfahrungspunkte sammelst du bei jedem Abenteuer. Damit kannst du neue Fertigkeiten für deinen Helden lernen –
                schau dir dafür die Fertigkeiten an.
            </p>
        </div>
    </div>

    {{-- Steckbrief --}}
    @if ($hero->description)
        <section>
            <h3 class="wr-section-heading__title text-lg mb-2">Steckbrief</h3>
            @if (mb_strlen($hero->description_plain) > 100)
                {{-- Gekürzt anzeigen, vollständig im gestapelten Modal --}}
                <p class="whitespace-pre-line break-words mb-1">{{ \Illuminate\Support\Str::limit($hero->description_plain, 100) }}</p>
                <a href="{{ route('heroes.steckbrief', $hero) }}"
                   data-modal-stack="{{ route('heroes.steckbrief', $hero) }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] font-semibold text-wr-forest underline">
                    Ganzen Steckbrief lesen <i class="angle right icon m-0" aria-hidden="true"></i>
                </a>
            @else
                <div class="wr-steckbrief break-words">{!! $hero->description_html !!}</div>
            @endif
        </section>
    @endif

    {{-- Erworbene Fertigkeiten --}}
    <section>
        <h3 class="wr-section-heading__title text-lg mb-2">Erworbene Fertigkeiten</h3>
        @if ($hero->skills->isEmpty())
            <p class="text-wr-ink-muted m-0">Noch keine Fertigkeiten erlernt.</p>
        @else
            <ul class="flex flex-wrap gap-1.5 m-0 p-0 list-none" role="list">
                @foreach ($hero->skills as $skill)
                    <li class="wr-pill wr-pill--success">
                        <i class="check icon" aria-hidden="true"></i>{{ $skill->name }} <span class="font-normal">({{ $skill->ep_costs }} EP)</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Bändchen / Perlen --}}
    @if ($perlByClass->isNotEmpty())
        <section>
            <h3 class="wr-section-heading__title text-lg mb-2">Bändchen / Perlen</h3>
            @include('partials._ribbon_perls', ['perlByClass' => $perlByClass, 'compact' => $ctx === 'm'])
        </section>
    @endif

    {{-- Heldendaten --}}
    <section class="pt-5 border-t border-wr-border">
        <h3 class="wr-section-heading__title text-lg mb-3">Heldendaten</h3>
        <dl class="wr-facts">
            <div><dt>Spieler</dt><dd>{{ $hero->player?->full_name ?? '—' }}</dd></div>
            <div>
                <dt>Klassen</dt>
                <dd>
                    @if ($hero->classes->isEmpty())
                        —
                    @else
                        <span class="flex flex-wrap gap-1.5 mt-1">
                            @foreach ($hero->classes as $heroClass)
                                <x-wr.class-badge :hero-class="$heroClass" />
                            @endforeach
                        </span>
                    @endif
                </dd>
            </div>
            @if ($hero->groups->isNotEmpty())
                <div>
                    <dt>Gruppen</dt>
                    <dd>{{ $hero->groups->map(fn ($g) => $g->name . ($g->pivot->role ? ' (' . $g->pivot->role . ')' : ''))->implode(', ') }}</dd>
                </div>
            @endif
            <div><dt>Heimatort</dt><dd>{{ $hero->homeplace ?? '—' }}</dd></div>
            <div>
                <dt>Erste Erblickung</dt>
                <dd>{{ optional($hero->born)->format('d.m.Y') ?? '—' }} <span class="text-sm text-wr-ink-muted">(Geburtstag des Helden)</span></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>
                    @if ($hero->died)
                        <span class="wr-pill wr-pill--danger"><i class="question circle icon" aria-hidden="true"></i>verschollen seit {{ $hero->died->format('d.m.Y') }}</span>
                    @elseif ($hero->active)
                        <span class="wr-pill wr-pill--success"><i class="check circle icon" aria-hidden="true"></i>aktiv</span>
                    @else
                        <span class="wr-pill wr-pill--waitlist"><i class="pause circle icon" aria-hidden="true"></i>inaktiv</span>
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    {{-- Öffentliches Profil und Ausweis (für Eltern klar abgegrenzt) --}}
    @if ($hero->public_code || $canEditPhoto)
        <section class="pt-5 border-t border-wr-border">
            <h3 class="wr-section-heading__title text-lg mb-1">Öffentliches Profil und Ausweis</h3>
            <p class="text-sm text-wr-ink-muted mb-3">
                Mit dem Helden-Siegel kann das Heldenprofil ohne Anmeldung angesehen werden – nur wenn du es freigibst.
            </p>

            <div class="space-y-3">
                @if ($hero->public_code)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-wr-ink-muted">Helden-Siegel:</span>
                        <code class="font-mono tracking-widest bg-wr-parchment rounded px-2 py-0.5">{{ $hero->public_code }}</code>
                        @if ($hero->public_visible)
                            <span class="wr-pill wr-pill--success wr-pill--small"><i class="eye icon" aria-hidden="true"></i>öffentlich</span>
                        @else
                            <span class="wr-pill wr-pill--waitlist wr-pill--small"><i class="eye slash icon" aria-hidden="true"></i>versteckt</span>
                        @endif
                    </div>

                    @if ($canManagePublic)
                        <div class="flex flex-col gap-2">
                            <form method="POST" action="{{ route('heroes.visibility', $hero) }}" data-refresh-modal class="ui form">
                                @csrf @method('PATCH')
                                {{-- Fomantic-Checkbox: Label ohne for-Attribut (sonst doppeltes Umschalten) --}}
                                <div class="ui toggle checkbox">
                                    <input type="checkbox" {{ $hero->public_visible ? 'checked' : '' }}
                                           onchange="this.form.requestSubmit()">
                                    <label>Profil öffentlich sichtbar</label>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('heroes.searchable', $hero) }}" data-refresh-modal class="ui form">
                                @csrf @method('PATCH')
                                <div class="ui toggle checkbox">
                                    <input type="checkbox" {{ $hero->public_searchable ? 'checked' : '' }}
                                           onchange="this.form.requestSubmit()">
                                    <label>In öffentlicher Heldensuche anzeigen</label>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- PUB-07: QR/URL für Ausweis – Admin/Bürokrat sehen es auch wenn versteckt. --}}
                    @if ($hero->public_visible || auth()->user()?->can('heldenregister.edit'))
                        <div class="flex flex-wrap items-end gap-3">
                            @if ($hero->public_visible)
                                <canvas data-qr-url="{{ route('public.hero', $hero->public_code) }}"
                                        title="QR-Code zum öffentlichen Profil" aria-label="QR-Code zum öffentlichen Profil" role="img"></canvas>
                            @endif
                            <div class="text-sm space-y-1 min-w-0">
                                <div class="text-wr-ink-muted">Öffentliches Profil{{ $hero->public_visible ? '' : ' (aktuell versteckt)' }}:</div>
                                <a href="{{ route('public.hero', $hero->public_code) }}" target="_blank" rel="noopener"
                                   class="wr-interactive underline text-wr-forest break-all {{ $hero->public_visible ? '' : 'opacity-70' }}">
                                    {{ url(route('public.hero', $hero->public_code)) }}
                                </a>
                            </div>
                        </div>
                    @endif
                @endif

                @if (($hero->public_code && $canManagePublic) || $canEditPhoto)
                    <div class="flex flex-wrap gap-2">
                        @if ($hero->public_code && $canManagePublic)
                            <a href="{{ route('heroes.ausweis', $hero) }}" target="_blank" rel="noopener"
                               class="wr-btn wr-btn--secondary wr-btn--small">
                                <i class="id card icon m-0" aria-hidden="true"></i> Ausweis (PDF)
                            </a>
                        @endif
                        @if ($canEditPhoto)
                            <a href="{{ route('heroes.player-edit', $hero) }}" data-modal-stack="{{ route('heroes.player-edit', $hero) }}"
                               class="wr-btn wr-btn--secondary wr-btn--small">
                                <i class="pencil alternate icon m-0" aria-hidden="true"></i> Charakter bearbeiten
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif
</div>
