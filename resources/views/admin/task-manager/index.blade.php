<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Taskmanager – Standardkonfiguration</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <p class="text-stone-600 mb-6">
                Hier legst du die <strong>Standardwerte</strong> für automatische Aufgaben fest.
                Diese Werte werden vorbelegt, wenn du für eine Veranstaltung noch keine eigenen Einstellungen gespeichert hast.
                Deaktivierte Tasks werden im Taskmanager der Veranstaltungen ausgeblendet.
            </p>

            @foreach ($categories as $catKey => $cat)
                @php
                    $catDefs = $definitions->filter(fn ($d) => $d->getCategoryKey() === $catKey);
                @endphp
                @if ($catDefs->isNotEmpty())
                    <div class="mb-8">
                        <h3 class="font-uncial text-lg text-waldritter mb-3 flex items-center gap-2">
                            <i class="{{ $cat['icon'] }} icon" style="color:#5a3a22;"></i>
                            {{ $cat['label'] }}
                        </h3>

                        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                            <x-mobile.cards-or-table>
                            <table class="min-w-full divide-y divide-stone-200">
                                <thead class="bg-black/5">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Aufgabe</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase hidden sm:table-cell">Standard-Auslöser</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase hidden sm:table-cell">Aktiv</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-200 text-stone-800">
                                    @foreach ($catDefs as $def)
                                        <tr class="{{ $def->is_enabled ? '' : 'opacity-50' }}">
                                            <td class="px-4 py-3" data-label="Aufgabe">
                                                <div class="flex items-center gap-2">
                                                    <i class="{{ $def->getIcon() }} icon text-stone-400"></i>
                                                    <div>
                                                        <div class="font-medium">{{ $def->label }}</div>
                                                        <div class="text-xs text-stone-500">{{ $def->description }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-stone-600 hidden sm:table-cell" data-label="Standard-Auslöser">
                                                {{ $def->default_days }} Tage
                                                {{ $def->default_direction === 'before' ? 'vor' : 'nach' }}
                                                {{ $def->default_reference === 'start_at' ? 'Beginn' : 'Ende' }}
                                            </td>
                                            <td class="px-4 py-3 hidden sm:table-cell" data-label="Aktiv">
                                                @if ($def->is_enabled)
                                                    <span class="ui mini green label">aktiv</span>
                                                @else
                                                    <span class="ui mini label">deaktiviert</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('admin.task-manager.edit', $def) }}"
                                                   data-modal-url="{{ route('admin.task-manager.edit', $def) }}"
                                                   class="text-waldritter hover:underline text-sm">
                                                    Bearbeiten
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </x-mobile.cards-or-table>
                        </div>
                    </div>
                @endif
            @endforeach

            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
