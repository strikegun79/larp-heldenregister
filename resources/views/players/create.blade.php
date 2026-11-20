<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-5">
                <a href="{{ route('players.index') }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zu deinen Spielern
                </a>
                <span class="wr-eyebrow">Familie und Spieler</span>
                <h1 class="wr-heading">Neuer Spieler</h1>
            </div>

            <form method="POST" action="{{ route('players.store') }}" enctype="multipart/form-data">
                @include('players._form')
                <div class="mt-6">
                    <button type="submit" class="wr-btn wr-btn--primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
