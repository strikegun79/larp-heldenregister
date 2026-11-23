{{-- Spieler-Detail – Allgemeines (Waldritter v1). Einmal geschrieben für Akkordeon und Reiter.
     Avatar mit Hochladen/Löschen direkt darunter (wie beim Heldenbuch, kein eigener Reiter).
     Erwartet: $player, $canEdit, $ctx ('m'/'d', eindeutige IDs). --}}
@php($avatarInputId = 'avatar-file-input-' . $ctx)

<div class="flex flex-col min-[480px]:flex-row gap-5 items-start">
    <div class="shrink-0 flex flex-col items-center gap-2">
        <img src="{{ $player->avatar_url }}" alt="Avatar von {{ $player->full_name }}"
             class="w-32 h-32 sm:w-40 sm:h-40 object-cover rounded-xl border-2 border-wr-border" style="aspect-ratio:1/1;" loading="lazy">

        @if ($canEdit)
            <div class="flex flex-wrap gap-1.5 justify-center">
                {{-- Datei-Feld per Tastatur erreichbar; der Fokus wird am Label sichtbar (wr-file-input) --}}
                <input type="file" id="{{ $avatarInputId }}" accept="image/jpeg,image/png" class="sr-only wr-file-input"
                       data-upload-url="{{ route('players.avatar', $player) }}">
                <label for="{{ $avatarInputId }}" class="wr-btn wr-btn--secondary wr-btn--small cursor-pointer">
                    <i class="upload icon m-0" aria-hidden="true"></i> Avatar ändern
                </label>
                <script>
                (function () {
                    var inp = document.getElementById('{{ $avatarInputId }}');
                    inp.addEventListener('change', function () {
                        var file = this.files[0];
                        if (!file) return;
                        this.value = '';
                        openPhotoCropper(file, inp.dataset.uploadUrl, function () {
                            if (window.appModal2Url) loadStackContent(window.appModal2Url, true);
                            else if (window.appModalUrl) loadModalContent(window.appModalUrl, true);
                            else window.location.reload();
                        });
                    });
                })();
                </script>
                @if ($player->image)
                    <form method="POST" action="{{ route('players.avatar.destroy', $player) }}"
                          data-refresh-modal data-confirm="Avatar wirklich löschen?">
                        @csrf @method('DELETE')
                        <button type="submit" class="wr-btn wr-btn--danger wr-btn--small" aria-label="Avatar löschen">
                            <i class="trash icon m-0" aria-hidden="true"></i>
                        </button>
                    </form>
                @endif
            </div>
            <span class="text-xs text-wr-ink-muted">JPG oder PNG, max. 20 MB</span>
        @endif
    </div>

    <div class="flex-1 min-w-0 w-full space-y-4">
        <dl class="wr-facts">
            <div>
                <dt><i class="birthday cake icon" aria-hidden="true"></i> Geburtsdatum</dt>
                <dd>{{ optional($player->dayofbirth)->format('d.m.Y') ?? '—' }}@if ($player->dayofbirth) ({{ $player->age }} Jahre)@endif</dd>
            </div>
            <div><dt>Geschlecht</dt><dd>{{ $player->gender ?? '—' }}</dd></div>
            <div>
                <dt><i class="home icon" aria-hidden="true"></i> Anschrift</dt>
                <dd>
                    @if (! $player->address_same_as_guardian && $player->street)
                        {{ $player->street }} {{ $player->house_number }}, {{ $player->zip }} {{ $player->city }}
                        <span class="text-sm text-wr-ink-muted">(abweichend)</span>
                    @else
                        <span class="text-wr-ink-muted">wie erziehungsberechtigte Person</span>
                    @endif
                </dd>
            </div>
            <div><dt><i class="envelope icon" aria-hidden="true"></i> E-Mail</dt><dd class="break-all">{{ $player->email ?? '—' }}</dd></div>
            <div><dt>Erstellt</dt><dd>{{ optional($player->created_at)->format('d.m.Y') ?? '—' }}</dd></div>
            <div>
                <dt>Status</dt>
                <dd>
                    @if ($player->active)
                        <span class="wr-pill wr-pill--success"><i class="check circle icon" aria-hidden="true"></i>aktiv</span>
                    @else
                        <span class="wr-pill wr-pill--waitlist"><i class="pause circle icon" aria-hidden="true"></i>inaktiv</span>
                    @endif
                </dd>
            </div>
        </dl>

        @if ($canEdit)
            <a href="{{ route('players.edit', $player) }}" data-modal-url="{{ route('players.edit', $player) }}"
               class="wr-btn wr-btn--primary">
                <i class="pencil icon m-0" aria-hidden="true"></i> Spielerdaten bearbeiten
            </a>
        @endif
    </div>
</div>
