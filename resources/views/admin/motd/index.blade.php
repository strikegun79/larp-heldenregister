<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Startseiten-Nachrichten</h2>
            <a href="{{ route('admin.motd.create') }}" class="ui primary button">Neue Nachricht</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-stone-600 mb-4">
                Es ist immer höchstens eine Nachricht aktiv. Sie erscheint auf der Startseite, solange der Zeitraum passt.
                Alte Nachrichten bleiben hier erhalten und können bearbeitet, dupliziert oder erneut aktiviert werden.
            </p>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-black/5">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Titel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Farbe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Zeitraum</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Zuletzt geändert</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 text-stone-800">
                            @forelse ($motds as $motd)
                                @php($status = \App\Models\Motd::STATUS_LABELS[$motd->status])
                                <tr>
                                    <td class="px-6 py-4 font-medium" data-label="Titel">
                                        {{ $motd->title }}
                                        @if ($motd->image)
                                            <i class="image icon text-stone-400" title="mit Banner-Bild"></i>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4" data-label="Status">
                                        <span class="{{ $status['color'] }}">{{ $status['label'] }}</span>
                                    </td>
                                    <td class="px-6 py-4" data-label="Farbe">{{ $motd->variant_label }}</td>
                                    <td class="px-6 py-4" data-label="Zeitraum">
                                        @if ($motd->start_at || $motd->end_at)
                                            {{ $motd->start_at?->format('d.m.Y H:i') ?? '…' }} – {{ $motd->end_at?->format('d.m.Y H:i') ?? '…' }}
                                        @else
                                            <span class="text-stone-400">unbegrenzt</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4" data-label="Zuletzt geändert">
                                        {{ $motd->updated_at?->format('d.m.Y H:i') }}
                                        @if ($motd->updater)
                                            <span class="text-stone-500 text-sm">({{ $motd->updater->name }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3 flex-wrap">
                                            <a href="{{ route('admin.motd.edit', $motd) }}" class="text-waldritter hover:underline">Bearbeiten</a>
                                            @if ($motd->is_active)
                                                <form method="POST" action="{{ route('admin.motd.deactivate', $motd) }}">
                                                    @csrf
                                                    <button type="submit" class="text-waldritter hover:underline">Deaktivieren</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.motd.activate', $motd) }}"
                                                      data-confirm="Nachricht „{{ $motd->title }}" aktivieren? Die bisher aktive Nachricht wird deaktiviert.">
                                                    @csrf
                                                    <button type="submit" class="text-waldritter hover:underline">Aktivieren</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.motd.duplicate', $motd) }}">
                                                @csrf
                                                <button type="submit" class="text-waldritter hover:underline">Duplizieren</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.motd.destroy', $motd) }}"
                                                  data-confirm="Nachricht „{{ $motd->title }}" unwiderruflich löschen?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-4 text-stone-500">Noch keine Nachrichten angelegt.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-mobile.cards-or-table>
            </div>

            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
