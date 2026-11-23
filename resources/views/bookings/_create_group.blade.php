<span data-modal-title hidden>Gruppe anmelden · {{ $adventure->name }}</span>

{{-- Gruppe anmelden (Waldritter v1). Lädt im gestapelten Modal; eigener .wr-v1-Wrapper.
     Feldnamen bleiben (player_ids[], event_role_id, kontakt_telefon, agb, requirements). --}}
<div class="wr-v1">

{{-- Kontext-Strip auf Mobile --}}
<div class="sm:hidden flex items-center gap-2 mb-3 text-xs text-wr-ink-muted">
    <span>Abenteuer-Detail</span>
    <span aria-hidden="true">›</span>
    <span class="font-semibold text-wr-ink">Gruppe anmelden</span>
</div>

@include('bookings._summary')

@if (! $adventure->registrationOpen())
    <p class="text-wr-ink-muted">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@elseif ($groups->isEmpty())
    <p class="text-wr-ink-muted">Du bist in keiner Gruppe oder alle deine Gruppen-Mitglieder sind für dieses Abenteuer bereits angemeldet.</p>
@else
    @if ($adventure->isFull())
        <div class="wr-notice mb-4" role="note">
            <i class="hourglass half icon m-0 mt-0.5" aria-hidden="true"></i>
            <p>Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.</p>
        </div>
    @endif

    <form id="group-booking-form" data-stack-close method="POST"
          action="{{ route('adventures.group-bookings.store', $adventure) }}" class="ui form">
        @csrf

        <p class="wr-help mt-0 mb-4">Mit <span class="text-wr-danger font-bold" aria-hidden="true">*</span> markierte Felder sind Pflichtfelder.</p>

        {{-- Mitglieder-Auswahl je Gruppe --}}
        @foreach ($groups as $group)
            <fieldset class="wr-fieldset">
                <legend>{{ $group->name }}</legend>
                <p class="wr-fieldset__intro">Wer aus dieser Gruppe soll angemeldet werden?</p>
                <div class="grid gap-2 sm:grid-cols-2 mb-3">
                    @foreach ($group->heroes->sortBy('character_name') as $hero)
                        <label class="wr-check" for="grp_player_{{ $hero->player_id }}">
                            <input type="checkbox" name="player_ids[]" value="{{ $hero->player_id }}"
                                   id="grp_player_{{ $hero->player_id }}" checked>
                            <span class="wr-check__text">
                                <span class="wr-check__title">{{ $hero->character_name }}</span>
                                @if ($hero->player)
                                    <span class="wr-check__hint">{{ $hero->player->full_name }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        {{-- Gemeinsame Angaben für alle Ausgewählten --}}
        <fieldset class="wr-fieldset">
            <legend>Für alle Ausgewählten</legend>
            <div class="grid gap-x-4 sm:grid-cols-2">
                <div class="field required">
                    <label for="grp-role">Rolle</label>
                    <select id="grp-role" name="event_role_id" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field required">
                    <label for="grp-kontakt-telefon">Notfall-/Kontakttelefon</label>
                    <input type="tel" id="grp-kontakt-telefon" name="kontakt_telefon" value="{{ $userPhone }}"
                           placeholder="+49 …" required maxlength="100">
                </div>
            </div>
        </fieldset>

        <fieldset class="wr-fieldset">
            <legend>Zustimmung</legend>
            <div class="field required">
                <div class="wr-check">
                    <input type="checkbox" name="agb" id="grp_agb" value="1" required>
                    <span class="wr-check__text">
                        <label for="grp_agb" class="wr-check__title cursor-pointer" style="display:inline; font-size:1rem; margin:0;">
                            Ich bestätige, dass alle ausgewählten Mitglieder den
                        </label>
                        <a href="{{ route('agb') }}" target="_blank" rel="noopener" class="wr-interactive underline text-wr-forest font-semibold">Teilnahmebedingungen</a>
                        <label for="grp_agb" class="wr-check__title cursor-pointer" style="display:inline; font-size:1rem; margin:0;">
                            zustimmen und zur Teilnahme berechtigt sind.
                        </label>
                    </span>
                </div>
            </div>

            {{-- Zusätzliche Pflicht-Bestätigungen des Auftraggebers (Issue #14) --}}
            @include('bookings._requirements', ['idPrefix' => 'grp_req'])
        </fieldset>

        <div class="mt-4">
            <button type="submit" class="wr-btn wr-btn--primary">
                <i class="users icon m-0" aria-hidden="true"></i> Gruppe anmelden
            </button>
        </div>
    </form>
@endif
</div>
