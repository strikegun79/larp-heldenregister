<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.data-breaches.index') }}" class="ui button">← Zurück</a>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                    Datenpanne vom {{ $log->discovered_at->format('d.m.Y H:i') }}
                </h2>
            </div>
            <a href="{{ route('admin.data-breaches.edit', $log) }}" class="ui button">Bearbeiten</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if(session('success'))
                <div class="ui success message">{{ session('success') }}</div>
            @endif

            {{-- 72h-Warnung --}}
            @if($log->isOverdue())
                <div class="ui error message">
                    <i class="exclamation circle icon"></i>
                    <strong>Meldung überfällig!</strong>
                    Die 72-Stunden-Frist ist abgelaufen. Bitte unverzüglich den HBDI benachrichtigen und den Eintrag aktualisieren.
                </div>
            @elseif($log->isWithin72Hours())
                @php($remaining = 72 - $log->discovered_at->diffInHours(now()))
                <div class="ui warning message">
                    <i class="clock icon"></i>
                    <strong>Achtung:</strong> Noch ca. {{ $remaining }} Stunden bis zum Ablauf der 72-Stunden-Meldefrist (Art. 33 DSGVO).
                </div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow rounded-lg divide-y divide-stone-200">

                <div class="px-6 py-4 grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                    <div class="text-stone-500">Entdeckt</div>
                    <div class="col-span-2 font-medium">{{ $log->discovered_at->format('d.m.Y H:i') }} Uhr</div>

                    <div class="text-stone-500">Erfasst von</div>
                    <div class="col-span-2">{{ $log->createdBy?->name ?? '—' }}, {{ $log->created_at->format('d.m.Y H:i') }}</div>

                    <div class="text-stone-500">Betr. Personen</div>
                    <div class="col-span-2">{{ $log->affected_persons_count !== null ? $log->affected_persons_count : '—' }}</div>
                </div>

                <div class="px-6 py-4 text-sm">
                    <p class="text-stone-500 mb-1">Beschreibung</p>
                    <p class="whitespace-pre-wrap">{{ $log->description }}</p>
                </div>

                <div class="px-6 py-4 text-sm">
                    <p class="text-stone-500 mb-1">Betroffene Datenkategorien</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($log->affected_data_categories as $key)
                            <li>{{ $categories[$key] ?? $key }}</li>
                        @endforeach
                    </ul>
                </div>

                @if($log->likely_consequences)
                    <div class="px-6 py-4 text-sm">
                        <p class="text-stone-500 mb-1">Wahrscheinliche Folgen</p>
                        <p class="whitespace-pre-wrap">{{ $log->likely_consequences }}</p>
                    </div>
                @endif

                <div class="px-6 py-4 text-sm">
                    <p class="text-stone-500 mb-1">Getroffene Maßnahmen</p>
                    <p class="whitespace-pre-wrap">{{ $log->measures_taken }}</p>
                </div>

                <div class="px-6 py-4 text-sm">
                    <p class="text-stone-500 mb-1">Behördliche Meldung</p>
                    @if(! $log->reportable)
                        <span class="text-stone-500">Nicht meldepflichtig (nur intern dokumentiert)</span>
                    @elseif($log->reported_to_authority)
                        <div class="space-y-0.5">
                            <p class="text-green-700 font-medium">✓ Gemeldet am {{ $log->reported_at?->format('d.m.Y H:i') }} Uhr</p>
                            @if($log->authority_reference)
                                <p>Referenz: <span class="font-mono">{{ $log->authority_reference }}</span></p>
                            @endif
                        </div>
                    @else
                        <span class="text-red-700 font-medium">Meldepflichtig – noch nicht gemeldet</span>
                    @endif
                </div>

                @if($log->internal_notes)
                    <div class="px-6 py-4 text-sm">
                        <p class="text-stone-500 mb-1">Interne Notizen</p>
                        <p class="whitespace-pre-wrap">{{ $log->internal_notes }}</p>
                    </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>
