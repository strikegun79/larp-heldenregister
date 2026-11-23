<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('heroes.show', $hero) }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zum Heldenbuch
                </a>
                <span class="wr-eyebrow">Held bearbeiten</span>
                <h1 class="wr-heading break-words">{{ $hero->character_name }}</h1>
            </div>

            <form method="POST" action="{{ route('heroes.update', $hero) }}">
                @method('PUT')
                @include('heroes._form')
            </form>

            {{-- Gefahrenbereich: Löschen deutlich abgesetzt, mit Rückfrage --}}
            <section class="wr-card p-4 sm:p-5 border-wr-danger" aria-labelledby="hero-delete-heading">
                <h2 id="hero-delete-heading" class="wr-section-heading__title text-lg mb-1">Helden löschen</h2>
                <p class="wr-card__text mb-3">Der Held wird aus dem Register entfernt.</p>
                <form method="POST" action="{{ route('heroes.destroy', $hero) }}"
                      data-confirm="Diesen Helden wirklich löschen?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="wr-btn wr-btn--danger">
                        <i class="trash icon m-0" aria-hidden="true"></i> Helden löschen
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
