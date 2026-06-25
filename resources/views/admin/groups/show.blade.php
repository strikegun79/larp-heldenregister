<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $group->name }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.groups.members', $group) }}"
                   data-modal-url="{{ route('admin.groups.members', $group) }}"
                   class="ui small button">Mitglieder verwalten</a>
                <a href="{{ route('admin.groups.edit', $group) }}"
                   data-modal-url="{{ route('admin.groups.edit', $group) }}"
                   class="ui small button">Bearbeiten</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Kurzinfo / Kennzahlen --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
                @if ($group->description)
                    <p class="text-stone-700 mb-4">{{ $group->description }}</p>
                @endif
                <div class="flex flex-wrap gap-6 text-sm text-stone-600">
                    <div>
                        <span class="font-medium text-waldritter">{{ $group->heroes->count() }}</span>
                        Mitglied(er)
                    </div>
                    <div>
                        <span class="font-medium text-waldritter">
                            {{ $group->heroes->groupBy('pivot.role')->count() > 1
                                ? $group->heroes->pluck('pivot.role')->filter()->unique()->count().' Rollen'
                                : ($group->heroes->first()?->pivot->role ?? '—') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Mitglieder-Tabelle --}}
            @if ($group->heroes->isEmpty())
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-8 text-center text-stone-500">
                    Noch keine Mitglieder in dieser Gruppe.
                    <div class="mt-3">
                        <a href="{{ route('admin.groups.members', $group) }}"
                           data-modal-url="{{ route('admin.groups.members', $group) }}"
                           class="ui small primary button">Mitglieder hinzufügen</a>
                    </div>
                </div>
            @else
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                    <x-mobile.cards-or-table>
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-black/5">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Held</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Spieler</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Klasse(n)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">EP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rolle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Beigetreten</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 text-stone-800">
                            @foreach ($group->heroes as $hero)
                                <tr class="{{ $hero->active ? '' : 'opacity-60' }}">
                                    <td class="px-6 py-4 font-medium" data-label="Held">
                                        {{ $hero->character_name }}
                                        @unless ($hero->active)
                                            <span class="ml-1 text-xs text-stone-400">(inaktiv)</span>
                                        @endunless
                                    </td>
                                    <td class="px-6 py-4 text-sm" data-label="Spieler">
                                        {{ $hero->player?->full_name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm" data-label="Klasse(n)">
                                        {{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}
                                    </td>
                                    <td class="px-6 py-4 tabular-nums" data-label="EP">
                                        {{ number_format($hero->ep_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm" data-label="Rolle">
                                        {{ $hero->pivot->role ?: '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-stone-500" data-label="Beigetreten">
                                        {{ $hero->pivot->joined_at ? \Carbon\Carbon::parse($hero->pivot->joined_at)->format('d.m.Y') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('heroes.show', $hero) }}"
                                           class="text-waldritter hover:underline">Zum Helden</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </x-mobile.cards-or-table>
                </div>
            @endif

            <div class="mt-6 flex gap-3">
                <a href="{{ route('admin.groups.index') }}">
                    <x-primary-button>Zurück zur Übersicht</x-primary-button>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
