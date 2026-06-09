<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Neuer Spieler</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('players.store') }}">
                    @include('players._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
