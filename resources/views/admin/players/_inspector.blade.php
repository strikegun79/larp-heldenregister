{{-- Inspektor eines Spielers in der Admin-Liste (Waldritter v1). Lädt per AJAX in #booking-inspector-content;
     Formulare mit data-inspector-form speichern per AJAX (heldenregister.js), danach werden Liste und
     Inspektor neu geladen. Routen und Validierung wie in den bisherigen Modals/Seiten. --}}
@php
    $age = $player->age;
@endphp

<div data-inspector-title hidden>{{ $player->full_name }}</div>

{{-- Kurzinfos --}}
<div class="flex flex-wrap items-center gap-1.5 mb-3">
    @if ($player->trashed())
        <span class="wr-pill wr-pill--danger"><i class="trash icon" aria-hidden="true"></i>gelöscht</span>
    @elseif ($player->active)
        <span class="wr-pill wr-pill--success"><i class="check circle icon" aria-hidden="true"></i>aktiv</span>
    @else
        <span class="wr-pill wr-pill--waitlist"><i class="pause circle icon" aria-hidden="true"></i>inaktiv</span>
    @endif
    @if ($age !== null && $age >= 18)
        <span class="wr-pill wr-pill--pending">volljährig</span>
    @elseif ($age !== null && $player->heroes_count === 0)
        <span class="wr-pill wr-pill--danger">kein Held</span>
    @endif
</div>
<dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm mb-5">
    <dt class="text-wr-ink-muted font-semibold">Alter</dt>
    <dd>{{ $age !== null ? $age.' Jahre' : '—' }}@if ($player->dayofbirth) <span class="text-wr-ink-muted">(geb. {{ $player->dayofbirth->format('d.m.Y') }})</span>@endif</dd>
    <dt class="text-wr-ink-muted font-semibold">Helden</dt>
    <dd>{{ $player->heroes_count }}</dd>
    <dt class="text-wr-ink-muted font-semibold">Geschlecht</dt>
    <dd>{{ $player->gender ?? '—' }}</dd>
</dl>

<a href="{{ route('players.show', $player) }}" data-modal-url="{{ route('players.show', $player) }}"
   class="wr-btn wr-btn--secondary wr-btn--small mb-5">
    <i class="user icon m-0" aria-hidden="true"></i> Spielerprofil öffnen
</a>

{{-- Anschrift (PLAY-13 / ORGA-01) --}}
<section class="mb-6" aria-labelledby="insp-p-anschrift">
    <h3 id="insp-p-anschrift" class="wr-section-heading__title text-base mb-2">Anschrift</h3>
    <form method="POST" action="{{ route('admin.players.update', $player) }}" data-inspector-form class="ui form">
        @csrf @method('PUT')
        <p class="wr-help mt-0 mb-2">Standardmäßig gilt die Anschrift der erziehungsberechtigten Person.</p>
        <div class="field">
            <label class="wr-check">
                <input type="hidden" name="address_same_as_guardian" value="1">
                <input type="checkbox" id="insp-address-differs" name="address_same_as_guardian" value="0"
                       @checked(! $player->address_same_as_guardian)
                       onchange="document.getElementById('insp-child-address').classList.toggle('hidden', !this.checked)">
                <span class="wr-check__text"><span class="wr-check__title">Abweichende Anschrift des Kindes</span></span>
            </label>
        </div>
        <div id="insp-child-address" class="{{ $player->address_same_as_guardian ? 'hidden' : '' }}">
            <div class="field">
                <label for="insp-street">Straße</label>
                <input type="text" id="insp-street" name="street" value="{{ $player->street }}" maxlength="100">
            </div>
            <div class="field">
                <label for="insp-house-number">Hausnummer</label>
                <input type="text" id="insp-house-number" name="house_number" value="{{ $player->house_number }}" maxlength="10">
            </div>
            <div class="field">
                <label for="insp-zip">PLZ</label>
                <input type="text" id="insp-zip" name="zip" value="{{ $player->zip }}" maxlength="10">
            </div>
            <div class="field">
                <label for="insp-city">Ort</label>
                <input type="text" id="insp-city" name="city" value="{{ $player->city }}" maxlength="100">
            </div>
        </div>
        <button type="submit" class="wr-btn wr-btn--primary wr-btn--small">Anschrift speichern</button>
    </form>
</section>

{{-- Betreuer (PLAY-06) --}}
<section class="mb-6" aria-labelledby="insp-p-betreuer">
    <h3 id="insp-p-betreuer" class="wr-section-heading__title text-base mb-2">Betreuer</h3>
    @if ($player->users->isEmpty())
        <p class="text-wr-ink-muted text-sm m-0 mb-3">Noch keine Betreuer zugeordnet.</p>
    @else
        <ul class="wr-rows mb-3" role="list">
            @foreach ($player->users as $user)
                <li class="wr-row">
                    <div class="min-w-0 text-sm">
                        <span class="font-semibold">{{ trim("{$user->name} {$user->lastname}") }}</span>
                        @if ($user->pivot->self)<span class="wr-pill wr-pill--small wr-pill--info ml-1">selbst</span>@endif
                        <span class="block text-wr-ink-muted break-all">{{ $user->email }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.players.caretakers.destroy', [$player, $user]) }}" data-inspector-form
                          data-confirm="Betreuer {{ $user->name }} {{ $user->lastname }} entfernen?">
                        @csrf @method('DELETE')
                        <button type="submit" class="wr-btn wr-btn--danger wr-btn--small"
                                aria-label="Betreuer {{ $user->name }} {{ $user->lastname }} entfernen">
                            <i class="times icon m-0" aria-hidden="true"></i> Entfernen
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($available->isNotEmpty())
        <form method="POST" action="{{ route('admin.players.caretakers.store', $player) }}" data-inspector-form class="ui form">
            @csrf
            <div class="field">
                <label for="insp-caretaker">Betreuer hinzufügen</label>
                <select id="insp-caretaker" name="user_id" required>
                    <option value="">— Nutzer wählen —</option>
                    @foreach ($available as $user)
                        <option value="{{ $user->id }}">{{ trim("{$user->name} {$user->lastname}") }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="wr-btn wr-btn--primary wr-btn--small">Hinzufügen</button>
        </form>
    @endif
</section>

{{-- Matrix-Konto (corporal User-DB) --}}
<section class="mb-6" aria-labelledby="insp-p-matrix">
    <h3 id="insp-p-matrix" class="wr-section-heading__title text-base mb-2">Matrix</h3>
    <p class="text-sm m-0">Matrix-User-ID</p>
    <p class="font-mono text-sm mb-2 break-all">{{ $mxid }}</p>
    @unless ($account)
        <p class="wr-help mt-0 mb-2">Noch kein Konto – wird beim Speichern mit aktivem Zugang angelegt.</p>
    @endunless

    <form method="POST" action="{{ route('admin.players.matrix.update', $player) }}" data-inspector-form class="ui form">
        @csrf @method('PUT')
        <div class="grid gap-2 mb-3">
            <label class="wr-check">
                <input type="checkbox" name="active" value="1" @checked($account?->active)>
                <span class="wr-check__text"><span class="wr-check__title">Matrix-Zugang aktiv</span></span>
            </label>
            <label class="wr-check">
                <input type="checkbox" name="forbid_room_creation" value="1" @checked($account?->forbid_room_creation ?? true)>
                <span class="wr-check__text"><span class="wr-check__title">Raumerstellung verbieten</span></span>
            </label>
        </div>
        <div class="field">
            <label for="insp-matrix-password">Passwort</label>
            <input type="text" id="insp-matrix-password" name="auth_credential" value="{{ $account?->auth_credential }}"
                   placeholder="{{ $account ? 'unverändert lassen' : 'Passwort setzen' }}" autocomplete="off">
            <small class="wr-help">Klartext (matrix-corporal nutzt authType „plain“).</small>
        </div>
        <fieldset class="mb-3">
            <legend class="font-semibold text-sm mb-1">Räume</legend>
            @if ($rooms->isEmpty())
                <p class="text-wr-ink-muted text-sm m-0">Keine verwalteten Räume vorhanden.</p>
            @else
                @unless ($account)
                    <p class="wr-help mt-0 mb-1">Vorauswahl: Räume mit „Standard: allow“.</p>
                @endunless
                <div class="grid gap-1.5 max-h-64 overflow-y-auto pr-1">
                    @foreach ($rooms as $room)
                        <label class="wr-check !min-h-0 !py-1.5 text-sm">
                            <input type="checkbox" name="rooms[]" value="{{ $room->roomid }}" @checked(in_array($room->roomid, $joined))>
                            <span class="wr-check__text">
                                <span class="wr-check__title text-sm">{{ $room->roomname }}</span>
                                <span class="wr-check__hint">
                                    {{ $room->roomtype }}@if ($room->default_allow) · Standard @elseif ($room->default_deny) · gesperrt @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </fieldset>
        <button type="submit" class="wr-btn wr-btn--primary wr-btn--small">Matrix speichern</button>
    </form>

    @if ($account && ! $account->trashed())
        <form method="POST" action="{{ route('admin.players.matrix.destroy', $player) }}" data-inspector-form class="mt-2"
              data-confirm="Matrix-Zugang wirklich entziehen?">
            @csrf @method('DELETE')
            <button type="submit" class="wr-btn wr-btn--danger wr-btn--small">Matrix-Zugang entziehen</button>
        </form>
    @endif
</section>

{{-- Gefahrenbereich: Spieler löschen (PLAY-08, Soft-Delete) --}}
@unless ($player->trashed())
    <section class="wr-danger-zone" aria-labelledby="insp-p-delete">
        <h3 id="insp-p-delete" class="wr-danger-zone__title">Spieler löschen</h3>
        @if ($blockers !== [])
            <p class="text-sm mb-2">{{ implode(' ', $blockers) }}</p>
            <form method="POST" action="{{ route('admin.players.destroy', $player->id) }}" data-inspector-form data-inspector-close-on-success
                  data-confirm="Spieler {{ $player->full_name }} wirklich trotzdem löschen?">
                @csrf @method('DELETE')
                <input type="hidden" name="force" value="1">
                <button type="submit" class="wr-btn wr-btn--danger wr-btn--small"><i class="trash icon m-0" aria-hidden="true"></i> Trotzdem löschen</button>
            </form>
        @else
            <p class="text-sm mb-2">Der Spieler wird gelöscht und kann über die Liste wiederhergestellt werden.</p>
            <form method="POST" action="{{ route('admin.players.destroy', $player->id) }}" data-inspector-form data-inspector-close-on-success
                  data-confirm="Spieler {{ $player->full_name }} löschen?">
                @csrf @method('DELETE')
                <button type="submit" class="wr-btn wr-btn--danger wr-btn--small"><i class="trash icon m-0" aria-hidden="true"></i> Löschen</button>
            </form>
        @endif
    </section>
@endunless
