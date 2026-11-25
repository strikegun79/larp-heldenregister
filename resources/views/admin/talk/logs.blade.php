{{-- Talk-Protokoll: Anfragen an Nextcloud (Änderungen und Fehler) --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Talk-Protokoll</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <p class="text-sm text-stone-700">
                Alle Änderungen, die das Heldenregister in Nextcloud vornimmt, und alle Fehler.
                Einträge werden nach {{ \App\Models\TalkLog::RETENTION_DAYS }} Tagen gelöscht.
            </p>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4">
                <form method="GET" action="{{ route('admin.talk.logs.index') }}" class="flex flex-wrap items-center gap-4"
                      role="search" aria-label="Talk-Protokoll filtern">
                    @if ($adventure)
                        <input type="hidden" name="adventure" value="{{ $adventure->id }}">
                        <span class="text-sm">Veranstaltung: <strong>{{ $adventure->name }}</strong></span>
                    @endif
                    @if ($player)
                        <input type="hidden" name="player" value="{{ $player->id }}">
                        <span class="text-sm">Spieler: <strong>{{ $player->full_name }}</strong></span>
                    @endif
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="errors" value="1" @checked($errorsOnly) onchange="this.form.submit()"
                               class="rounded border-gray-300 text-amber-700 focus:ring-amber-600">
                        Nur Fehler
                    </label>
                    <noscript><button type="submit" class="ui mini button">Filtern</button></noscript>
                    @if ($adventure || $player || $errorsOnly)
                        <a href="{{ route('admin.talk.logs.index') }}" class="text-sm text-stone-500 hover:underline">Filter zurücksetzen</a>
                    @endif
                </form>
            </div>

            @include('admin.talk._log_list', ['logs' => $logs, 'showContext' => true])

            <div>{{ $logs->links() }}</div>

            <a href="{{ route('admin.index') }}"><x-primary-button>Zurück zur Verwaltung</x-primary-button></a>
        </div>
    </div>
</x-app-layout>
