{{-- Spieler-Detail – Helden (Waldritter v1). Pro Held: Bild, Name, Klassen, EP, Link ins Heldenbuch
     und – getrennt davon – „Als aktiven Helden wählen“ (kein Button innerhalb eines Links).
     Erwartet: $player, $canEdit. --}}
@if ($player->heroes->isEmpty())
    <p class="text-wr-ink-muted m-0">Noch keine Helden. Der erste Held entsteht beim ersten Abenteuer – gemeinsam mit dem Bürokraten.</p>
@else
    @if ($canEdit && $player->heroes->count() > 1)
        <p class="text-sm text-wr-ink-muted mb-3">
            Der <strong class="text-wr-ink">aktive Held</strong> ist der Held, mit dem der Spieler gerade spielt.
            Er erscheint auf der Startseite.
        </p>
    @endif
    <ul class="space-y-3 m-0 p-0 list-none" role="list">
        @foreach ($player->heroes as $hero)
            @php($isActive = $player->active_hero_id === $hero->id)
            <li class="wr-card p-3 sm:p-4 {{ $isActive ? 'border-wr-moss border-2' : '' }}">
                <div class="flex flex-col min-[480px]:flex-row min-[480px]:items-center gap-3">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <img src="{{ $hero->image_url }}" alt="" aria-hidden="true" loading="lazy"
                             class="w-14 h-14 shrink-0 object-cover rounded-lg border border-wr-border">
                        <div class="min-w-0 flex flex-col gap-1">
                            <h4 class="font-semibold text-base m-0 break-words">{{ $hero->character_name ?? 'unbenannt' }}</h4>
                            @if ($hero->classes->isNotEmpty())
                                <span class="flex flex-wrap gap-1.5">
                                    @foreach ($hero->classes as $heroClass)
                                        <x-wr.class-badge :hero-class="$heroClass" />
                                    @endforeach
                                </span>
                            @endif
                            <span class="text-sm text-wr-forest font-semibold">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP verfügbar</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 min-[480px]:justify-end">
                        @if ($isActive)
                            <span class="wr-pill wr-pill--success"><i class="star icon" aria-hidden="true"></i>aktiver Held</span>
                        @elseif ($canEdit)
                            <form method="POST" action="{{ route('players.active-hero', $player) }}" data-refresh-modal>
                                @csrf @method('PATCH')
                                <input type="hidden" name="hero_id" value="{{ $hero->id }}">
                                <button type="submit" class="wr-btn wr-btn--secondary wr-btn--small">Als aktiven Helden wählen</button>
                            </form>
                        @endif
                        <a href="{{ route('heroes.show', $hero) }}" class="wr-btn wr-btn--primary wr-btn--small">
                            Heldenbuch öffnen <i class="chevron right icon m-0" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
