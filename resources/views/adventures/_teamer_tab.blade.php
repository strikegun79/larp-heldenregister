{{--
    Teamer-Tab: Liste der angemeldeten Teamer + Rollenzuweisung (ADV-27).
    Variablen: $adventure, $teamerSignups (Collection), $myTeamerSignup (TeamerSignup|null)
--}}

{{-- Issue #31: Links zum Teamer-Raum und Plot-Thread in Talk, wenn vorhanden --}}
@php
    $talkClient = \App\Services\NextcloudTalk\NextcloudTalkClient::fromConfig();
    $talkPlotToken = \App\Models\Setting::get('talk_plot_room_token');
    $talkRoomUrl = $talkClient->isActive() && $adventure->talk_teamer_room_token
        ? $talkClient->roomUrl($adventure->talk_teamer_room_token) : null;
    $talkThreadUrl = $talkClient->isActive() && $adventure->talk_plot_thread_id && $talkPlotToken
        ? $talkClient->roomUrl($talkPlotToken, (int) $adventure->talk_plot_thread_id) : null;
@endphp
@if ($talkRoomUrl || $talkThreadUrl)
    <div class="flex flex-wrap gap-2 mb-3">
        @if ($talkRoomUrl)
            <a href="{{ $talkRoomUrl }}" target="_blank" rel="noopener" class="ui small teal button">
                <i class="users icon" aria-hidden="true"></i> Teamer-Raum in Talk öffnen
            </a>
        @endif
        @if ($talkThreadUrl)
            <a href="{{ $talkThreadUrl }}" target="_blank" rel="noopener" class="ui small teal button">
                <i class="comments icon" aria-hidden="true"></i> Plot-Thread in Talk öffnen
            </a>
        @endif
    </div>
@endif

@if ($teamerSignups->isEmpty())
    <p class="text-stone-500 text-sm">Noch keine Teamer angemeldet.</p>
@else
    <x-mobile.cards-or-table>
    <table class="ui very basic compact unstackable table">
        <thead class="mob-thead" hidden>
            <tr>
                <th>Name</th>
                <th>Teamer-Rolle</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($teamerSignups as $signup)
                <tr>
                    <td data-label="Name">{{ $signup->user->name }} {{ $signup->user->lastname }}</td>
                    <td data-label="Rolle">{{ $signup->eventRole?->description ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </x-mobile.cards-or-table>
@endif
