{{-- Issue #31 (Phase 2): Reiter „Talk“ der Event-Verwaltung – Plot-Thread und Teamer-Raum.
     Wird für Handy und Desktop doppelt eingebunden → $ctx als ID-Suffix.
     Erwartet: $adventure, $ctx, $talkActive. --}}
@php
    $talkClient = \App\Services\NextcloudTalk\NextcloudTalkClient::fromConfig();
    $plotToken = \App\Models\Setting::get('talk_plot_room_token');
    $approvedTeamers = $adventure->teamerSignups->whereNotNull('approved_at')->filter(fn ($s) => $s->user);
    $offerPlot = ! $adventure->talk_plot_thread;
    $offerRoom = ! $adventure->talk_teamer_room;
@endphp

@if (! $talkActive)
    <div class="wr-notice" role="note">
        <i class="info circle icon m-0 mt-0.5" aria-hidden="true"></i>
        <p>
            Die Talk-Integration ist nicht aktiv.
            @can('talk.manage')
                Einrichten unter <a href="{{ route('admin.talk.index') }}" class="underline">Verwaltung → Nextcloud Talk</a>.
            @endcan
        </p>
    </div>
@else
    @if ($adventure->talk_error)
        <div class="ui negative message" role="alert">
            <div class="header">Talk konnte nicht vollständig eingerichtet werden</div>
            <p>{{ $adventure->talk_error }}</p>
            <form method="POST" action="{{ route('adventures.talk.retry', $adventure) }}" data-refresh-modal class="mt-2">
                @csrf
                <button type="submit" class="ui small button"><i class="redo icon" aria-hidden="true"></i> Erneut versuchen</button>
            </form>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 mb-4">
        {{-- Plot-Thread --}}
        <section class="border border-stone-200 rounded-md p-3 bg-white/60" aria-labelledby="talk-plot-{{ $ctx }}">
            <h3 id="talk-plot-{{ $ctx }}" class="font-medium text-stone-800 mb-1"><i class="comments icon" aria-hidden="true"></i> Plot-Thread</h3>
            @if ($adventure->talk_plot_thread_id && $plotToken)
                <p class="text-sm text-stone-600 mb-2">„{{ $adventure->talk_plot_thread_title }}“ im Plot-Team-Chat.</p>
                <a href="{{ $talkClient->roomUrl($plotToken, $adventure->talk_plot_thread_id) }}" target="_blank" rel="noopener" class="ui small teal button">
                    <i class="external alternate icon" aria-hidden="true"></i> In Talk öffnen
                </a>
            @elseif ($adventure->talk_plot_thread)
                <p class="text-sm text-stone-600"><i class="hourglass half icon" aria-hidden="true"></i> Wird eingerichtet …</p>
            @else
                <p class="text-sm text-stone-600">Noch kein Thread im Plot-Team-Chat.</p>
            @endif
        </section>

        {{-- Teamer-Raum --}}
        <section class="border border-stone-200 rounded-md p-3 bg-white/60" aria-labelledby="talk-room-{{ $ctx }}">
            <h3 id="talk-room-{{ $ctx }}" class="font-medium text-stone-800 mb-1"><i class="users icon" aria-hidden="true"></i> Teamer-Raum</h3>
            @if ($adventure->talk_teamer_room_token)
                <p class="text-sm text-stone-600 mb-2">„{{ $adventure->talk_teamer_room_name }}“ (privat).</p>
                <a href="{{ $talkClient->roomUrl($adventure->talk_teamer_room_token) }}" target="_blank" rel="noopener" class="ui small teal button">
                    <i class="external alternate icon" aria-hidden="true"></i> In Talk öffnen
                </a>
            @elseif ($adventure->talk_teamer_room)
                <p class="text-sm text-stone-600"><i class="hourglass half icon" aria-hidden="true"></i> Wird eingerichtet …</p>
            @else
                <p class="text-sm text-stone-600">Noch kein Teamer-Raum.</p>
            @endif
        </section>
    </div>

    @if ($offerPlot || $offerRoom)
        <form method="POST" action="{{ route('adventures.talk.update', $adventure) }}" data-refresh-modal
              class="border border-stone-200 rounded-md p-3 mb-4 bg-white/60">
            @csrf
            @method('PUT')
            <fieldset>
                <legend class="px-1 font-medium text-stone-800">Einrichten</legend>
                <div class="space-y-2 my-2">
                    @if ($offerPlot)
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="talk-plot-thread-{{ $ctx }}" name="talk_plot_thread" value="1"
                                   class="rounded border-gray-300 text-amber-700 focus:ring-amber-600">
                            <label for="talk-plot-thread-{{ $ctx }}">Plot-Thread erstellen</label>
                        </div>
                    @endif
                    @if ($offerRoom)
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="talk-teamer-room-{{ $ctx }}" name="talk_teamer_room" value="1"
                                   class="rounded border-gray-300 text-amber-700 focus:ring-amber-600">
                            <label for="talk-teamer-room-{{ $ctx }}">Teamer-Raum erstellen</label>
                        </div>
                    @endif
                </div>
                <button type="submit" class="ui small primary button"><i class="plus icon" aria-hidden="true"></i> Einrichten</button>
            </fieldset>
        </form>
    @endif

    {{-- Talk-Protokoll dieser Veranstaltung (letzte 20 Einträge) --}}
    @php($talkLogs = \App\Models\TalkLog::with('user:id,name,lastname')->where('adventure_id', $adventure->id)->latest('id')->limit(20)->get())
    @if ($talkLogs->isNotEmpty())
        <details class="mb-4" @if ($talkLogs->first()->success === false) open @endif>
            <summary class="cursor-pointer font-medium text-stone-800">
                Protokoll ({{ $talkLogs->count() }} {{ $talkLogs->count() === 1 ? 'Eintrag' : 'Einträge' }}{{ $talkLogs->where('success', false)->isNotEmpty() ? ', davon '.$talkLogs->where('success', false)->count().' Fehler' : '' }})
            </summary>
            <div class="mt-2">
                @include('admin.talk._log_list', ['logs' => $talkLogs])
                @can('talk.approve')
                    <a href="{{ route('admin.talk.logs.index', ['adventure' => $adventure->id]) }}" class="text-sm underline mt-1 inline-block">Ganzes Protokoll</a>
                @endcan
            </div>
        </details>
    @endif

    @if ($adventure->talk_teamer_room_token)
        <section aria-labelledby="talk-teamers-{{ $ctx }}">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h3 id="talk-teamers-{{ $ctx }}" class="font-medium text-stone-800">Bestätigte Teamer ({{ $approvedTeamers->count() }})</h3>
                <form method="POST" action="{{ route('adventures.talk.sync', $adventure) }}" data-refresh-modal>
                    @csrf
                    <button type="submit" class="ui small button"><i class="sync icon" aria-hidden="true"></i> Jetzt synchronisieren</button>
                </form>
            </div>
            @if ($approvedTeamers->isEmpty())
                <p class="text-sm text-stone-600">Noch keine bestätigten Teamer.</p>
            @else
                <ul class="divide-y divide-stone-200 border border-stone-200 rounded-md bg-white/60">
                    @foreach ($approvedTeamers as $signup)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                            <span>{{ trim($signup->user->name.' '.$signup->user->lastname) }}</span>
                            @if ($signup->user->nextcloud_user_id)
                                <span class="text-sm text-green-800"><i class="check circle icon" aria-hidden="true"></i> Nextcloud: {{ $signup->user->nextcloud_user_id }}</span>
                            @else
                                <span class="text-sm text-amber-800"><i class="exclamation triangle icon" aria-hidden="true"></i> Kein Nextcloud-Konto gefunden</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-stone-500 mt-2">
                    Das Konto wird über die E-Mail-Adresse gefunden. Abweichende Benutzernamen trägt ein Admin im Nutzerprofil ein.
                </p>
            @endif
        </section>
    @endif
@endif
