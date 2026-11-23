<x-app-layout>
    @php
        $hasFilter = $q !== '' || $classId || $playerId || $status;
        // Status eines Helden als Pill (Text + Symbol, kein Abblenden der Zeile)
        $statusPill = function ($hero) {
            if ($hero->died) {
                return ['wr-pill--danger', 'question circle', 'verschollen'];
            }

            return $hero->active
                ? ['wr-pill--success', 'check circle', 'aktiv']
                : ['wr-pill--waitlist', 'pause circle', 'inaktiv'];
        };
    @endphp

    {{-- Waldritter-Designsystem v1 (Verwaltungsliste: ruhig, klare Spalten, Filter) --}}
    <div class="wr-v1 py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Seitenkopf --}}
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <span class="wr-eyebrow">Heldenregister</span>
                    <h1 class="wr-heading">Heldenverwaltung</h1>
                </div>
                @can('heldenregister.edit')
                    <a href="{{ route('heroes.create') }}" class="wr-btn wr-btn--primary">
                        <i class="plus icon m-0" aria-hidden="true"></i> Neuer Held
                    </a>
                @endcan
            </div>

            {{-- Filter --}}
            <form method="GET" action="{{ route('heroes.index') }}" role="search"
                  class="wr-card wr-form p-4 mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
                <div class="lg:col-span-2">
                    <label for="heroes-q" class="wr-label">Suche</label>
                    <input id="heroes-q" type="search" name="q" value="{{ $q }}" placeholder="z. B. Tilix, Müller oder Erste Hilfe" class="w-full">
                    <small class="wr-help">Spieler-, Charakter- oder Fertigkeitsname, auch Helden-Siegel.</small>
                </div>
                <div>
                    <label for="heroes-class" class="wr-label">Klasse</label>
                    <select id="heroes-class" name="class_id" class="w-full">
                        <option value="">Alle</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="heroes-player" class="wr-label">Spieler</label>
                    <select id="heroes-player" name="player_id" class="w-full">
                        <option value="">Alle</option>
                        @foreach ($players as $player)
                            <option value="{{ $player->id }}" @selected((string) $playerId === (string) $player->id)>{{ $player->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="heroes-status" class="wr-label">Status</label>
                    <select id="heroes-status" name="status" class="w-full">
                        <option value="">Alle</option>
                        <option value="active" @selected($status === 'active')>Aktiv</option>
                        <option value="inactive" @selected($status === 'inactive')>Inaktiv</option>
                        <option value="missing" @selected($status === 'missing')>Verschollen</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center gap-2">
                    <button type="submit" class="wr-btn wr-btn--primary">
                        <i class="filter icon m-0" aria-hidden="true"></i> Filtern
                    </button>
                    @if ($hasFilter)
                        <a href="{{ route('heroes.index') }}" class="wr-btn wr-btn--secondary">Zurücksetzen</a>
                    @endif
                </div>
            </form>

            <p class="text-sm text-wr-ink-muted mb-3" role="status" aria-live="polite">
                {{ $heroes->total() }} {{ $heroes->total() === 1 ? 'Held' : 'Helden' }} gefunden
            </p>

            @if ($heroes->isEmpty())
                <div class="wr-card p-8 text-center items-center">
                    @if ($hasFilter)
                        <p class="font-semibold m-0 mb-1">Kein Held passt zu den Filtern.</p>
                        <p class="wr-card__text mb-4">Passe die Suche an oder setze die Filter zurück.</p>
                        <a href="{{ route('heroes.index') }}" class="wr-btn wr-btn--secondary">Filter zurücksetzen</a>
                    @else
                        <p class="font-semibold m-0 mb-1">Noch keine Helden erfasst.</p>
                        <p class="wr-card__text mb-4">
                            Bevor ein Held angelegt werden kann, muss ein
                            <a href="{{ route('players.index') }}" class="wr-interactive underline text-wr-forest">Spieler</a>
                            vorhanden sein.
                        </p>
                        @can('heldenregister.edit')
                            <a href="{{ route('heroes.create') }}" class="wr-btn wr-btn--primary">Neuen Helden anlegen</a>
                        @endcan
                    @endif
                </div>
            @else
                {{-- Handy: Karten --}}
                <ul class="sm:hidden space-y-3 m-0 p-0 list-none" role="list">
                    @foreach ($heroes as $hero)
                        @php([$pillClass, $pillIcon, $pillText] = $statusPill($hero))
                        <li>
                            <a href="{{ route('heroes.show', $hero) }}" data-modal-url="{{ route('heroes.show', $hero) }}"
                               class="wr-card wr-card--link">
                                <div class="wr-card__body">
                                    <span class="wr-card__title break-words">
                                        {{ $hero->character_name ?? '—' }}
                                        @if ($hero->image)
                                            <i class="image icon text-wr-ink-muted text-xs" aria-hidden="true"></i><span class="sr-only">(Foto vorhanden)</span>
                                        @endif
                                        @if ($hero->gallery_images_count > 0)
                                            <i class="images icon text-wr-ink-muted text-xs" aria-hidden="true"></i><span class="sr-only">({{ $hero->gallery_images_count }} Galeriebilder)</span>
                                        @endif
                                    </span>
                                    <span class="text-sm text-wr-ink-muted">{{ $hero->player?->full_name ?? '—' }}</span>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="wr-pill wr-pill--small {{ $pillClass }}"><i class="{{ $pillIcon }} icon" aria-hidden="true"></i>{{ $pillText }}</span>
                                        <span class="wr-pill wr-pill--small">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP verfügbar</span>
                                        @foreach ($hero->classes as $heroClass)
                                            <span class="wr-pill wr-pill--small">{{ $heroClass->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: Tabelle. Charaktername ist der Link (Tastatur), die Zeile bleibt per Maus klickbar. --}}
                <div class="hidden sm:block wr-card overflow-x-auto">
                    <table class="wr-table">
                        <caption class="sr-only">Helden, Seite {{ $heroes->currentPage() }} von {{ $heroes->lastPage() }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Spieler</th>
                                <th scope="col">Charakter</th>
                                <th scope="col" class="wr-num">EP gesamt</th>
                                <th scope="col" class="wr-num">EP verfügbar</th>
                                <th scope="col">Klassen</th>
                                <th scope="col">Aktiv</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($heroes as $hero)
                                @php([$pillClass, $pillIcon, $pillText] = $statusPill($hero))
                                <tr data-modal-url="{{ route('heroes.show', $hero) }}">
                                    <td>{{ $hero->player?->full_name ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('heroes.show', $hero) }}" data-modal-url="{{ route('heroes.show', $hero) }}"
                                           class="wr-interactive font-semibold text-wr-forest underline">{{ $hero->character_name ?? '—' }}</a>
                                        @if ($hero->image)
                                            <i class="image icon text-wr-ink-muted text-xs ml-0.5" aria-hidden="true"></i><span class="sr-only">(Foto vorhanden)</span>
                                        @endif
                                        @if ($hero->gallery_images_count > 0)
                                            <i class="images icon text-wr-ink-muted text-xs ml-0.5" aria-hidden="true"></i><span class="sr-only">({{ $hero->gallery_images_count }} Galeriebilder)</span>
                                        @endif
                                    </td>
                                    <td class="wr-num">{{ number_format($hero->ep_total, 0, ',', '.') }}</td>
                                    <td class="wr-num">{{ number_format($hero->ep_balance, 0, ',', '.') }}</td>
                                    <td>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</td>
                                    <td><span class="wr-pill wr-pill--small {{ $pillClass }}"><i class="{{ $pillIcon }} icon" aria-hidden="true"></i>{{ $pillText }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $heroes->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
