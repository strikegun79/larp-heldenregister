<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Audit-Log</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Filter --}}
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-stone-500 mb-1">Aktion</label>
                    <select name="action" class="border border-stone-300 rounded px-3 py-1.5 text-sm bg-white">
                        <option value="">– alle –</option>
                        @foreach ($actions as $a)
                            <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-stone-500 mb-1">Akteur</label>
                    <input type="text" name="actor" value="{{ request('actor') }}"
                           placeholder="Name suchen…"
                           class="border border-stone-300 rounded px-3 py-1.5 text-sm bg-white">
                </div>
                <button type="submit" class="ui primary button text-sm">Filtern</button>
                @if (request('action') || request('actor'))
                    <a href="{{ route('admin.audit-logs.index') }}" class="ui button text-sm">Zurücksetzen</a>
                @endif
            </form>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Zeitpunkt</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Aktion</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Akteur</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Betreff</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Änderungen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-black/5">
                                <td class="px-4 py-3 whitespace-nowrap text-stone-500 font-mono text-xs">
                                    {{ $log->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-block bg-stone-100 text-stone-700 rounded px-2 py-0.5 font-mono text-xs">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $log->actor_name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($log->subject_label)
                                        <span>{{ $log->subject_label }}</span>
                                        @if ($log->subject_type)
                                            <span class="text-stone-400 text-xs ml-1">({{ class_basename($log->subject_type) }})</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-500 max-w-xs truncate" title="{{ $log->changes ? json_encode($log->changes, JSON_UNESCAPED_UNICODE) : '' }}">
                                    {{ $log->changes ? json_encode($log->changes, JSON_UNESCAPED_UNICODE) : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-stone-400">Keine Einträge vorhanden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>

            <div class="mt-4">
                <a href="{{ route('admin.index') }}">
                    <x-primary-button>Zurück zur Verwaltung</x-primary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
