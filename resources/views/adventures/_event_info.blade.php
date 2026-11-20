{{-- Abenteuer-Eckdaten (Waldritter v1): zuerst das Wichtigste für Teilnehmende und Eltern,
     darunter die Organisation. Status-Badge mit Datenbank-Farbe bleibt erhalten. --}}
@php
    $free = $adventure->freeSlots();
    $max = (int) $adventure->max_player;
@endphp

<div class="space-y-6">
    <section aria-labelledby="adv-facts-main-{{ $adventure->id }}">
        <h3 id="adv-facts-main-{{ $adventure->id }}" class="wr-section-heading__title text-lg mb-3">Das Wichtigste</h3>
        <dl class="wr-facts">
            <div>
                <dt><i class="calendar alternate icon" aria-hidden="true"></i> Termin</dt>
                <dd>
                    @if ($adventure->start_at)
                        <x-wr.adventure-date :adventure="$adventure" />
                    @else
                        noch offen
                    @endif
                </dd>
            </div>
            <div>
                <dt><i class="map marker alternate icon" aria-hidden="true"></i> Ort</dt>
                <dd>
                    {{ $adventure->location?->titel ?? 'noch offen' }}
                    @if ($adventure->location?->gps)
                        @php($gps = str_replace(' ', '', $adventure->location->gps))
                        <a href="https://www.google.com/maps?q={{ $gps }}"
                           target="_blank" rel="noopener noreferrer"
                           class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest underline">
                            <i class="map icon m-0" aria-hidden="true"></i>Karte öffnen
                            <span class="font-normal">(Google Maps, neues Fenster)</span>
                        </a>
                    @endif
                </dd>
            </div>
            <div>
                <dt><i class="euro sign icon" aria-hidden="true"></i> Beitrag</dt>
                <dd>
                    @if ($adventure->fee > 0)
                        {{ number_format($adventure->fee, 2, ',', '.') }} €
                        @if ($adventure->fee_reduced !== null)
                            <span class="text-sm text-wr-ink-muted">(ermäßigt {{ number_format($adventure->fee_reduced, 2, ',', '.') }} €)</span>
                        @endif
                    @else
                        kostenlos
                    @endif
                </dd>
            </div>
            @if ($adventure->min_age || $adventure->max_age)
                <div>
                    <dt><i class="child icon" aria-hidden="true"></i> Alter</dt>
                    <dd>{{ $adventure->age_range_label }}</dd>
                </div>
            @endif
            <div>
                <dt><i class="users icon" aria-hidden="true"></i> Plätze</dt>
                <dd>
                    @if ($max > 0)
                        Noch {{ $free }} von {{ $max }} frei
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt><i class="flag icon" aria-hidden="true"></i> Anmeldung</dt>
                <dd><x-wr.adventure-status :adventure="$adventure" :show-slots="false" /></dd>
            </div>
        </dl>
    </section>

    <section aria-labelledby="adv-facts-orga-{{ $adventure->id }}" class="pt-5 border-t border-wr-border">
        <h3 id="adv-facts-orga-{{ $adventure->id }}" class="wr-section-heading__title text-base mb-3">Organisation</h3>
        <dl class="wr-facts wr-facts--quiet">
            <div><dt>Status</dt><dd>@include('adventures._status_badge', ['status' => $adventure->status])</dd></div>
            <div><dt>Kategorie</dt><dd>{{ $adventure->category?->name ?? '—' }}</dd></div>
            <div><dt>Auftraggeber</dt><dd>{{ $adventure->client?->name ?? '—' }}</dd></div>
            <div><dt>Spielleiter</dt><dd>{{ $adventure->gamemaster ? trim("{$adventure->gamemaster->name} {$adventure->gamemaster->lastname}") : '—' }}</dd></div>
            <div><dt>Veranstaltungsleiter</dt><dd>{{ $adventure->eventleader ? trim("{$adventure->eventleader->name} {$adventure->eventleader->lastname}") : '—' }}</dd></div>
            @if ($adventure->function_email)
                <div>
                    <dt>Kontakt</dt>
                    <dd><a href="mailto:{{ $adventure->function_email }}" class="wr-interactive underline text-wr-forest">{{ $adventure->function_email }}</a></dd>
                </div>
            @endif
        </dl>
    </section>
</div>
