<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $hero->character_name ?? 'Held' }}
            </h2>
            @can('manage-heldenregister')
                <a href="{{ route('heroes.edit', $hero) }}">
                    <x-secondary-button>Bearbeiten</x-secondary-button>
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded bg-green-100 dark:bg-green-900 px-4 py-2 text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-800 dark:text-gray-200">
                <dl class="grid grid-cols-2 gap-4">
                    <div><dt class="text-sm text-gray-500">Spieler</dt><dd>{{ $hero->player?->full_name ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Klassen</dt><dd>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Heimatort</dt><dd>{{ $hero->homeplace ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">EP-Saldo</dt><dd class="font-semibold">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</dd></div>
                    <div><dt class="text-sm text-gray-500">Geboren</dt><dd>{{ optional($hero->born)->format('d.m.Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Gestorben</dt><dd>{{ optional($hero->died)->format('d.m.Y') ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Status</dt><dd>{{ $hero->active ? 'aktiv' : 'inaktiv' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-800 dark:text-gray-200">
                <h3 class="font-semibold mb-3">Fertigkeiten</h3>
                @forelse ($hero->skills as $skill)
                    <span class="inline-block rounded bg-gray-100 dark:bg-gray-700 px-2 py-1 text-sm mr-2 mb-2">
                        {{ $skill->name }} ({{ $skill->ep_costs }} EP)
                    </span>
                @empty
                    <p class="text-gray-500">Keine Fertigkeiten erlernt.</p>
                @endforelse
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-800 dark:text-gray-200">
                <h3 class="font-semibold mb-3">EP-Verlauf</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-500">
                        <tr><th class="py-1">Datum</th><th class="py-1">Art</th><th class="py-1 text-right">EP</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($hero->epTransactions->sortByDesc('transacted_at') as $tx)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="py-1">{{ optional($tx->transacted_at)->format('d.m.Y') }}</td>
                                <td class="py-1">{{ $tx->type?->description }}</td>
                                <td class="py-1 text-right {{ $tx->type?->is_credit ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $tx->type?->is_credit ? '+' : '−' }}{{ number_format($tx->ep_count, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-gray-500">Keine EP-Buchungen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('heroes.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">&larr; Zurück zum Register</a>
        </div>
    </div>
</x-app-layout>
