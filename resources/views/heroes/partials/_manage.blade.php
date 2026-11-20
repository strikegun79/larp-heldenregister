{{-- Heldenbuch – Verwalten (nur Bürokrat/Admin, Waldritter v1). Einmal geschrieben für Akkordeon und Reiter.
     Routen und Formulare (data-refresh-modal) unverändert. Erwartet: $hero, $ctx ('m'/'d', eindeutige IDs). --}}
@php($codeId = 'hero-code-' . $ctx)

<div class="grid gap-4 md:grid-cols-2">

    {{-- Helden-Siegel, Ausweis, Aufnahme-Bestätigung --}}
    <section class="wr-card p-4" aria-labelledby="hero-manage-siegel-{{ $ctx }}">
        <h3 id="hero-manage-siegel-{{ $ctx }}" class="wr-section-heading__title text-base mb-3 flex items-center gap-2">
            <i class="id badge icon m-0" aria-hidden="true"></i> Helden-Siegel
        </h3>
        <form method="POST" action="{{ route('heroes.assign-code', $hero) }}" data-refresh-modal class="wr-form">
            @csrf @method('PATCH')
            <label for="{{ $codeId }}" class="wr-label">Siegel-Code</label>
            <div class="flex items-end gap-2 flex-wrap">
                <input type="text" id="{{ $codeId }}" name="code" maxlength="6" minlength="6"
                       pattern="[A-HJ-NP-Z2-9]{6}" placeholder="XXXXXX"
                       value="{{ old('code', $hero->public_code) }}"
                       class="font-mono tracking-widest w-32 siegel-input" style="text-transform:uppercase"
                       aria-describedby="{{ $codeId }}-hint" required>
                <button type="submit" class="wr-btn wr-btn--primary">Siegel zuweisen</button>
            </div>
            <small id="{{ $codeId }}-hint" class="wr-help">6 Zeichen aus A–Z (ohne I, L, O) und 2–9.</small>
            @error('code') <p class="text-wr-danger font-semibold text-sm mt-1" role="alert">{{ $message }}</p> @enderror
        </form>

        @if ($hero->public_code)
            <div class="mt-3">
                <a href="{{ route('admin.id-cards.reprint', $hero) }}" target="_blank" rel="noopener" class="wr-btn wr-btn--secondary wr-btn--small">
                    <i class="print icon m-0" aria-hidden="true"></i> Ausweis drucken
                </a>
            </div>
        @endif

        @if ($hero->player)
            {{-- Willkommens-Mail: eigener Unterbereich --}}
            <div class="mt-4 pt-4 border-t border-wr-border">
                <h4 class="font-semibold text-base m-0 mb-1 flex items-center gap-2">
                    <i class="envelope icon m-0" aria-hidden="true"></i> Aufnahme-Bestätigung
                </h4>
                <p class="wr-help mt-0 mb-2">Sendet die Aufnahme-Bestätigung an die Eltern/Betreuer des Spielers.</p>
                <form method="POST" action="{{ route('heroes.welcome-mail', $hero) }}" data-refresh-modal>
                    @csrf
                    <button type="submit" class="wr-btn wr-btn--secondary wr-btn--small">
                        <i class="paper plane icon m-0" aria-hidden="true"></i> Willkommens-Mail senden
                    </button>
                </form>
            </div>
        @endif
    </section>

    {{-- Status: verschollen / wiedergefunden --}}
    <section class="wr-card p-4 {{ $hero->died ? 'border-wr-danger' : '' }}" aria-labelledby="hero-manage-status-{{ $ctx }}">
        <h3 id="hero-manage-status-{{ $ctx }}" class="wr-section-heading__title text-base mb-3 flex items-center gap-2">
            <i class="user icon m-0" aria-hidden="true"></i> Status im Spiel
        </h3>
        <p class="mb-3">
            @if ($hero->died)
                <span class="wr-pill wr-pill--danger"><i class="question circle icon" aria-hidden="true"></i>verschollen seit {{ $hero->died->format('d.m.Y') }}</span>
            @else
                <span class="wr-pill wr-pill--success"><i class="check circle icon" aria-hidden="true"></i>aktiv im Spiel</span>
            @endif
        </p>
        <p class="wr-help mt-0 mb-3">
            {{ $hero->died
                ? 'Dieser Held gilt derzeit als verschollen und ist nicht aktiv im Spiel.'
                : 'Dieser Held ist im Heldenregister aktiv und nimmt am Spielgeschehen teil.' }}
        </p>
        <form method="POST" action="{{ route('heroes.missing', $hero) }}" data-refresh-modal>
            @csrf @method('PATCH')
            @if ($hero->died)
                <button type="submit" class="wr-btn wr-btn--primary wr-btn--small">
                    <i class="undo icon m-0" aria-hidden="true"></i> Als wiedergefunden markieren
                </button>
            @else
                <button type="submit" class="wr-btn wr-btn--danger wr-btn--small">
                    <i class="user times icon m-0" aria-hidden="true"></i> Als verschollen markieren
                </button>
            @endif
        </form>
    </section>
</div>
