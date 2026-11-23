<x-app-layout>
    {{-- Waldritter-Designsystem v1 --}}
    <div class="wr-v1 py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-5">
                <a href="{{ route('heroes.index') }}"
                   class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm font-semibold text-wr-forest">
                    <i class="arrow left icon m-0" aria-hidden="true"></i> Zurück zur Heldenverwaltung
                </a>
                <span class="wr-eyebrow">Heldenregister</span>
                <h1 class="wr-heading">Neuer Held</h1>
            </div>

            <form method="POST" action="{{ route('heroes.store') }}">
                @include('heroes._form')
            </form>
        </div>
    </div>
</x-app-layout>
