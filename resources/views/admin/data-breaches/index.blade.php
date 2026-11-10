<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Datenpannen-Protokoll</h2>
            <a href="{{ route('admin.data-breaches.create') }}" class="ui primary button">
                + Vorfall dokumentieren
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="ui success message">{{ session('success') }}</div>
            @endif

            {{-- Prozess-Dokumentation Art. 33 --}}
            <div class="bg-amber-50 border-2 border-amber-400 rounded-lg p-5 text-sm text-stone-800">
                <div class="font-semibold text-amber-800 mb-2 flex items-center gap-2">
                    <i class="exclamation triangle icon text-amber-600"></i>
                    Meldeprozess bei Datenpannen (DSGVO Art. 33)
                </div>
                <ol class="list-decimal list-inside space-y-1 text-stone-700">
                    <li><strong>Sofort:</strong> Vorfall intern melden und dieses Protokoll anlegen.</li>
                    <li><strong>Innerhalb von 72 Stunden:</strong> Bei meldepflichtigen Pannen den Hessischen Beauftragten für Datenschutz und Informationsfreiheit (HBDI) über das Online-Portal benachrichtigen.</li>
                    <li><strong>Betroffene informieren</strong> (Art. 34), wenn die Panne voraussichtlich hohe Risiken für ihre Rechte und Freiheiten mit sich bringt.</li>
                    <li><strong>Referenznummer</strong> der Behörde im Protokoll eintragen.</li>
                </ol>
                <p class="mt-2 text-xs text-stone-500">
                    Nicht jede Panne ist meldepflichtig – aber jede muss dokumentiert werden.
                    Meldepflicht besteht, wenn voraussichtlich Risiken für natürliche Personen entstehen.
                </p>
            </div>

            {{-- Tabelle --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow rounded-lg overflow-hidden">
                @if($logs->isEmpty())
                    <p class="p-6 text-stone-500">Noch keine Datenpannen dokumentiert.</p>
                @else
                    <x-mobile.cards-or-table>
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-black/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Entdeckt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Beschreibung</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Meldepflichtig</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Erfasst von</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200">
                            @foreach($logs as $log)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium" data-label="Entdeckt">
                                        {{ $log->discovered_at->format('d.m.Y H:i') }}
                                        @if($log->isOverdue())
                                            <span class="ui mini red label ml-1">Überfällig</span>
                                        @elseif($log->isWithin72Hours())
                                            <span class="ui mini orange label ml-1">
                                                {{ 72 - $log->discovered_at->diffInHours(now()) }} h verbleibend
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 max-w-xs truncate" data-label="Beschreibung">{{ $log->description }}</td>
                                    <td class="px-4 py-3" data-label="Meldepflichtig">
                                        @if($log->reportable)
                                            <span class="ui mini orange label">Ja</span>
                                        @else
                                            <span class="ui mini label">Nein</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3" data-label="Status">
                                        @if(! $log->reportable)
                                            <span class="text-stone-400">Nur intern</span>
                                        @elseif($log->reported_to_authority)
                                            <span class="text-green-700">✓ Gemeldet {{ $log->reported_at?->format('d.m.Y') }}</span>
                                        @else
                                            <span class="text-red-700">Nicht gemeldet</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-stone-500" data-label="Erfasst von">{{ $log->createdBy?->name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.data-breaches.show', $log) }}"
                                           class="ui mini button">Details</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </x-mobile.cards-or-table>
                    <div class="p-4">{{ $logs->links() }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
