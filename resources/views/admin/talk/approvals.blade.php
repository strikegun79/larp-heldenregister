{{-- Issue #31 (Phase 3): Talk-Freigaben je Spieler (Bürokrat, Admin) --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Talk-Freigaben</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="p-3 bg-red-100 text-red-800 rounded" role="alert">{{ session('error') }}</div>
            @endif

            @unless ($talkActive)
                <div class="wr-notice" role="note">
                    <i class="info circle icon m-0 mt-0.5" aria-hidden="true"></i>
                    <p>Die Talk-Integration ist nicht aktiv. Freigaben werden gespeichert, Konten aber erst eingerichtet, wenn sie eingeschaltet ist.</p>
                </div>
            @endunless

            <p class="text-sm">
                <a href="{{ route('admin.talk.logs.index', ['errors' => 1]) }}" class="underline"><i class="list icon" aria-hidden="true"></i>Talk-Protokoll (Fehler)</a>
            </p>

            <p class="text-sm text-stone-700">
                Talk ist ab {{ \App\Models\Player::TALK_MIN_AGE }} Jahren möglich. Bei Minderjährigen müssen nach der Freigabe
                die Eltern im Reiter „Talk“ des Spielers zustimmen; erst dann wird das Nextcloud-Konto eingerichtet.
            </p>

            {{-- Suche und Filter (filtern automatisch, ohne JS per Enter) --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4">
                <form method="GET" action="{{ route('admin.talk.approvals.index') }}" class="ui form"
                      data-auto-filter="#talk-results" role="search" aria-label="Spieler filtern">
                    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3">
                        <div class="ui action input w-full sm:w-auto">
                            <label for="talk-search" class="sr-only">Name oder E-Mail suchen</label>
                            <input type="search" id="talk-search" name="q" value="{{ $q }}"
                                   placeholder="Name oder E-Mail suchen…" autocomplete="off" autofocus>
                            <button type="submit" class="ui icon button" aria-label="Suchen"><i class="search icon"></i></button>
                        </div>
                        <div class="w-full sm:w-56">
                            <label for="talk-filter" class="sr-only">Status</label>
                            <select id="talk-filter" name="filter" class="w-full">
                                @foreach ($filters as $value => $label)
                                    <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="{{ route('admin.talk.approvals.index') }}" data-auto-filter-reset
                           class="text-sm text-stone-500 hover:underline shrink-0 {{ $filtered ? '' : 'hidden' }}">Filter zurücksetzen</a>
                    </div>
                </form>
            </div>

            <div id="talk-results">
                <p class="sr-only" role="status">{{ $players->total() }} Spieler gefunden</p>
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                    <x-mobile.cards-or-table>
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-black/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Spieler</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Alter</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Nextcloud</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 text-stone-800">
                            @forelse ($players as $player)
                                @php($blocker = $player->talkApprovalBlocker())
                                <tr>
                                    <td class="px-4 py-3" data-label="Spieler">{{ $player->full_name }}</td>
                                    <td class="px-4 py-3" data-label="Alter">{{ $player->age ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm" data-label="Status">
                                        {{ $player->talkStatusLabel() }}
                                        @if ($player->talk_error)
                                            <span class="block text-red-700">{{ $player->talk_error }}</span>
                                        @endif
                                        @if ($player->talk_logs_exists)
                                            <a href="{{ route('admin.talk.logs.index', ['player' => $player->id]) }}" class="text-xs underline">Protokoll</a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm" data-label="Nextcloud">{{ $player->nextcloud_user_id ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex flex-wrap justify-end gap-2">
                                            {{-- Verknüpfung lösen, z. B. nachdem das Konto in Nextcloud gelöscht wurde --}}
                                            @if ($player->nextcloud_user_id)
                                                <form method="POST" action="{{ route('admin.talk.approvals.unlink', $player) }}"
                                                      data-confirm="Verknüpfung von {{ $player->full_name }} mit dem Nextcloud-Konto „{{ $player->nextcloud_user_id }}“ lösen? In Nextcloud wird nichts gelöscht; beim nächsten Einrichten wird erneut gesucht bzw. ein Konto angelegt.">
                                                    @csrf
                                                    <button type="submit" class="ui mini basic button"
                                                            aria-label="Nextcloud-Verknüpfung von {{ $player->full_name }} lösen">
                                                        <i class="unlink icon"></i> Verknüpfung lösen
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($player->talk_approved_at === null)
                                                @if ($blocker)
                                                    <span class="text-xs text-stone-500">{{ $blocker }}</span>
                                                @else
                                                    <form method="POST" action="{{ route('admin.talk.approvals.approve', $player) }}">
                                                        @csrf
                                                        <button type="submit" class="ui mini primary button"
                                                                aria-label="Talk für {{ $player->full_name }} freigeben">
                                                            <i class="check icon"></i> Freigeben
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                @if ($player->talk_error && $player->talkReadyForAccount())
                                                    <form method="POST" action="{{ route('admin.talk.approvals.retry', $player) }}">
                                                        @csrf
                                                        <button type="submit" class="ui mini button"><i class="redo icon"></i> Erneut versuchen</button>
                                                    </form>
                                                @endif
                                                {{-- Benutzername war schon vergeben: neues Konto unter dem nächsten freien Namen --}}
                                                @if ($player->talk_error && blank($player->nextcloud_user_id) && $player->talkReadyForAccount())
                                                    <form method="POST" action="{{ route('admin.talk.approvals.new-name', $player) }}"
                                                          data-confirm="Für {{ $player->full_name }} ein neues Nextcloud-Konto mit anderem Benutzernamen (z. B. mit angehängter Zahl) anlegen? Nur wählen, wenn das vorhandene Konto einer anderen Person gehört.">
                                                        @csrf
                                                        <button type="submit" class="ui mini button"><i class="user plus icon"></i> Neues Konto mit anderem Namen anlegen</button>
                                                    </form>
                                                @endif
                                                {{-- Bewusst mit einem vorhandenen Konto verknüpfen --}}
                                                @if (blank($player->nextcloud_user_id))
                                                    <details class="text-left w-full sm:w-auto">
                                                        <summary class="cursor-pointer text-xs underline">Mit vorhandenem Konto verknüpfen</summary>
                                                        <form method="POST" action="{{ route('admin.talk.approvals.link', $player) }}" class="flex flex-wrap items-center gap-1 mt-1">
                                                            @csrf
                                                            <label for="nc-link-{{ $player->id }}" class="sr-only">Nextcloud-Benutzername für {{ $player->full_name }}</label>
                                                            <input type="text" id="nc-link-{{ $player->id }}" name="nextcloud_user_id" maxlength="64" required
                                                                   placeholder="Nextcloud-Benutzername" autocomplete="off"
                                                                   class="border border-stone-300 rounded px-2 py-1 text-sm w-44">
                                                            @unless ($player->isTalkMinor())
                                                                <label class="inline-flex items-center gap-1 text-xs w-full">
                                                                    <input type="checkbox" name="external" value="1" class="rounded border-gray-300">
                                                                    Konto wird nicht vom Heldenregister verwaltet (z. B. Nextcloud-Admin)
                                                                </label>
                                                            @endunless
                                                            <button type="submit" class="ui mini button"><i class="linkify icon"></i> Verknüpfen</button>
                                                        </form>
                                                    </details>
                                                @endif
                                                <form method="POST" action="{{ route('admin.talk.approvals.revoke', $player) }}"
                                                      data-confirm="Talk-Freigabe für {{ $player->full_name }} zurücknehmen? Ein vom Heldenregister angelegtes Konto wird deaktiviert.">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="ui mini basic red button"
                                                            aria-label="Talk-Freigabe für {{ $player->full_name }} zurücknehmen">
                                                        <i class="ban icon"></i> Zurücknehmen
                                                    </button>
                                                </form>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-stone-400">Keine Spieler für diese Filter gefunden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </x-mobile.cards-or-table>
                </div>
                <div class="mt-4">{{ $players->links() }}</div>
            </div>

            <a href="{{ route('admin.index') }}"><x-primary-button>Zurück zur Verwaltung</x-primary-button></a>
        </div>
    </div>
</x-app-layout>
