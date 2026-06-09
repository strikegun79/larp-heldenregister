<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $player->full_name }}</h2>
            <a href="{{ route('players.edit', $player) }}"><x-secondary-button>Bearbeiten</x-secondary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded bg-green-100 px-4 py-2 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6 text-stone-800">
                <dl class="grid grid-cols-2 gap-4">
                    <div><dt class="text-sm text-stone-500">Geburtsdatum</dt><dd>{{ optional($player->dayofbirth)->format('d.m.Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-stone-500">Geschlecht</dt><dd>{{ $player->gender ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-stone-500">E-Mail</dt><dd>{{ $player->email ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-stone-500">Status</dt><dd>{{ $player->active ? 'aktiv' : 'inaktiv' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6 text-stone-800">
                <h3 class="font-uncial text-lg text-waldritter mb-3">Helden</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-stone-500">
                        <tr><th class="py-1">Name</th><th class="py-1">Klasse(n)</th><th class="py-1 text-right">EP</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($player->heroes as $hero)
                            <tr class="border-t border-stone-200">
                                <td class="py-1">
                                    <a href="{{ route('heroes.show', $hero) }}" class="text-indigo-700 hover:underline">{{ $hero->character_name ?? '—' }}</a>
                                </td>
                                <td class="py-1">{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</td>
                                <td class="py-1 text-right">{{ number_format($hero->ep_balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-stone-500">Noch keine Helden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('players.index') }}" class="text-sm text-stone-600 hover:underline">&larr; Zurück zu deinen Spielern</a>
        </div>
    </div>
</x-app-layout>
