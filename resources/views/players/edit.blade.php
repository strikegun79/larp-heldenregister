<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('players.show', $player) }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zum Spieler
                </a>
                <span class="wr-eyebrow">Spieler bearbeiten</span>
                <h1 class="wr-heading break-words">{{ $player->full_name }}</h1>
            </div>

            <form method="POST" action="{{ route('players.update', $player) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('players._form')
                <div class="mt-6">
                    <button type="submit" class="wr-btn wr-btn--primary">Speichern</button>
                </div>
            </form>

            {{-- Gefahrenbereich: Löschen deutlich abgesetzt, mit Rückfrage --}}
            <section class="wr-card p-4 sm:p-5 border-wr-danger" aria-labelledby="player-delete-heading">
                <h2 id="player-delete-heading" class="wr-section-heading__title text-lg mb-1">Spieler löschen</h2>
                <p class="wr-card__text mb-3">Der Spieler wird gelöscht und erscheint nicht mehr in deiner Liste. Bei Fragen hilft das Organisationsteam.</p>
                <form method="POST" action="{{ route('players.destroy', $player) }}"
                      data-confirm="Diesen Spieler wirklich löschen?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="wr-btn wr-btn--danger">
                        <i class="trash icon m-0" aria-hidden="true"></i> Spieler löschen
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
