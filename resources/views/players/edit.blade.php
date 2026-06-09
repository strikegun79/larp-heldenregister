<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Spieler bearbeiten: {{ $player->full_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('players.update', $player) }}">
                    @method('PUT')
                    @include('players._form')
                </form>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('players.destroy', $player) }}"
                      onsubmit="return confirm('Diesen Spieler wirklich löschen?');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Spieler löschen</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
