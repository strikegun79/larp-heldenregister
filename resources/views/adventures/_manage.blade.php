<span data-modal-title hidden>Verwaltung: {{ $adventure->name }}</span>

@php
    $collectEmail = fn($b) => $b->guardian()?->email ?? $b->player?->email;

    $emailGroups = [
        'all'       => $mainBookings
            ->where('status', '!=', 'storniert')
            ->filter(fn($b) => ! $b->is_guest)
            ->map($collectEmail)->filter()->unique()->values()->all(),
        'confirmed' => $mainBookings
            ->where('waitlisted', false)
            ->where('status', '!=', 'storniert')
            ->filter(fn($b) => ! $b->is_guest)
            ->map($collectEmail)->filter()->unique()->values()->all(),
        'unpaid'    => $mainBookings
            ->where('waitlisted', false)
            ->where('status', '!=', 'storniert')
            ->where('paid', false)
            ->filter(fn($b) => ! $b->is_guest)
            ->map($collectEmail)->filter()->unique()->values()->all(),
        'waitlist'  => $mainBookings
            ->where('waitlisted', true)
            ->where('status', '!=', 'storniert')
            ->filter(fn($b) => ! $b->is_guest)
            ->map($collectEmail)->filter()->unique()->values()->all(),
    ];
    $hasAnyEmails = count($emailGroups['all']) > 0;
    $manageMailTo = config('portal.email');

    // Teamer-E-Mail-Gruppen
    $teamerCol = $adventure->teamerSignups;
    $signedUpUserIds = $teamerCol->pluck('user_id')->toArray();
    $teamerEmailGroups = [
        'all'      => $teamerCol
            ->map(fn($s) => $s->user?->email)->filter()->unique()->values()->all(),
        'approved' => $teamerCol->whereNotNull('approved_at')
            ->map(fn($s) => $s->user?->email)->filter()->unique()->values()->all(),
        'pending'  => $teamerCol->whereNull('approved_at')->whereNull('rejected_at')
            ->map(fn($s) => $s->user?->email)->filter()->unique()->values()->all(),
        'missing'  => \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('slug', ['teamer', 'lehrmeister']))
            ->whereNotIn('id', $signedUpUserIds)
            ->where('activated', true)
            ->pluck('email')->filter()->unique()->values()->all(),
    ];
    $hasAnyTeamerEmails = count($teamerEmailGroups['all']) > 0 || count($teamerEmailGroups['missing']) > 0;

    $cntActive    = $mainBookings->where('waitlisted', false)->where('status', '!=', 'storniert')->count();
    $cntWaitlist  = $mainBookings->where('waitlisted', true)->count();
    $cntCancelled = $mainBookings->where('status', 'storniert')->count();
    $bookingsTabLabel = 'Anmeldungen (<span class="text-green-700">' . $cntActive . '</span>/<span class="text-amber-600">' . $cntWaitlist . '</span>/<span class="text-red-600">' . $cntCancelled . '</span>)';
@endphp

{{-- UI-40: Mobile Accordion (< sm) --}}
<div class="sm:hidden space-y-2">
    <x-mobile.accordion-section title="Event-Daten" :open="true">
        <form id="manage-adventure-form-mobile" method="POST" action="{{ route('adventures.update', $adventure) }}" data-reload>
            @method('PUT')
            @include('adventures._form', ['inModal' => true])
        </form>
        @if ($adventure->event_status_id !== \App\Models\EventStatus::CANCELLED)
            <div class="ui red segment mt-4">
                <h5 class="ui header" style="color: #9b2c2c;">
                    <i class="ban icon"></i>
                    <div class="content">Gefahrenzone</div>
                </h5>
                <form method="POST" action="{{ route('adventures.cancel', $adventure) }}" data-refresh-modal
                      data-confirm="Abenteuer wirklich absagen? Es sind danach keine Anmeldungen mehr möglich.">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui red basic button">
                        <i class="ban icon"></i> Abenteuer absagen
                    </button>
                </form>
            </div>
        @else
            <div class="ui warning message mt-4" style="display:block">Dieses Event ist abgesagt.</div>
        @endif
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="$bookingsTabLabel">
        @include('adventures._waitlist_mode_toggle')
        <div class="flex flex-wrap gap-2 mb-3">
            <a href="{{ route('adventures.participation-xlsx', $adventure) }}" class="ui small button" target="_blank" rel="noopener"><i class="file excel outline icon"></i> Belegungsreport (Excel)</a>
            @can('book-any-player')
                <a href="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}"
                   data-modal-stack="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}"
                   class="ui small button">
                    <i class="shield alternate icon"></i> Admin-Anmeldung
                </a>
            @endcan
            @if ($hasAnyEmails)
                <div class="inline-flex rounded border border-stone-200 bg-white">
                    <select id="email-filter-mob"
                            onchange="updateManageMailto('mob', this.value)"
                            style="border:none;border-right:1px solid rgba(34,36,38,.15);border-radius:.28rem 0 0 .28rem;padding:.45em .6em;font-size:.85em;background:transparent;cursor:pointer;outline:none;">
                        <option value="all">Alle Teilnehmer ({{ count($emailGroups['all']) }})</option>
                        <option value="confirmed">Nur Angemeldete ({{ count($emailGroups['confirmed']) }})</option>
                        <option value="unpaid">Unbezahlte Teilnehmer ({{ count($emailGroups['unpaid']) }})</option>
                        <option value="waitlist">Wartelisten Teilnehmer ({{ count($emailGroups['waitlist']) }})</option>
                    </select>
                    <div class="ui small teal floating dropdown button" id="mailto-dropdown-mob"
                         tabindex="0" style="margin:0;border-radius:0 .28rem .28rem 0;box-shadow:none;">
                        <i class="mail icon"></i> E-Mail <i class="dropdown icon"></i>
                        <div class="menu transition hidden" tabindex="-1" style="white-space:nowrap;">
                            <a class="item" onclick="manageMailtoAction('client','mob'); return false;">
                                <i class="external alternate icon"></i> An E-Mail-Client senden
                            </a>
                            <a class="item" onclick="manageMailtoAction('copy','mob'); return false;">
                                <i class="copy outline icon"></i> E-Mail-Adressen kopieren
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @include('adventures._bookings', ['bookings' => $mainBookings, 'manage' => true])
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Teamer/NSC (' . ($adventure->teamerSignups->count() + $nscBookings->count()) . ')'">
        @if ($hasAnyTeamerEmails)
            <div class="inline-flex rounded border border-stone-200 bg-white mb-3">
                <select id="email-filter-teamer-mob"
                        onchange="updateTeamerMailto('mob', this.value)"
                        style="border:none;border-right:1px solid rgba(34,36,38,.15);border-radius:.28rem 0 0 .28rem;padding:.45em .6em;font-size:.85em;background:transparent;cursor:pointer;outline:none;">
                    <option value="all">Alle Teamer ({{ count($teamerEmailGroups['all']) }})</option>
                    <option value="approved">Bestätigte Teamer ({{ count($teamerEmailGroups['approved']) }})</option>
                    <option value="pending">Unbestätigte Teamer ({{ count($teamerEmailGroups['pending']) }})</option>
                    <option value="missing">Fehlende Teamer ({{ count($teamerEmailGroups['missing']) }})</option>
                </select>
                <div class="ui small teal floating dropdown button" id="teamer-mailto-dropdown-mob"
                     tabindex="0" style="margin:0;border-radius:0 .28rem .28rem 0;box-shadow:none;">
                    <i class="mail icon"></i> E-Mail <i class="dropdown icon"></i>
                    <div class="menu transition hidden" tabindex="-1" style="white-space:nowrap;">
                        <a class="item" onclick="teamerMailtoAction('client','mob'); return false;">
                            <i class="external alternate icon"></i> An E-Mail-Client senden
                        </a>
                        <a class="item" onclick="teamerMailtoAction('copy','mob'); return false;">
                            <i class="copy outline icon"></i> E-Mail-Adressen kopieren
                        </a>
                    </div>
                </div>
            </div>
        @endif
        @include('adventures._teamer_nsc_tab', [
            'teamerSignups' => $adventure->teamerSignups,
            'nscBookings'   => $nscBookings,
        ])
        <div class="mt-4 pt-4 border-t border-stone-200">
            <form method="POST" action="{{ route('adventures.teamer.invite', $adventure) }}"
                  data-confirm="Einladung an alle aktiven Teamer und Lehrmeister schicken?">
                @csrf
                <button type="submit" class="ui teal button">
                    <i class="mail icon"></i> Teamer einladen
                </button>
                <p class="text-sm text-stone-500 mt-1">Benachrichtigt alle aktiven Teamer &amp; Lehrmeister mit eingeschalteten Benachrichtigungen.</p>
            </form>
        </div>
    </x-mobile.accordion-section>

    <x-mobile.accordion-section title="Check-in">
        @include('adventures._checkin')
    </x-mobile.accordion-section>

    <x-mobile.accordion-section title="Taskmanager">
        @include('adventures._tasks', ['tasks' => $tasks])
    </x-mobile.accordion-section>
</div>

{{-- Desktop: Fomantic-Tabs (sm+) --}}
<div class="hidden sm:block">
    <div class="ui top attached tabular menu" style="overflow-x: auto; flex-wrap: nowrap;">
        <a class="item active" data-tab="data" style="white-space: nowrap;">Event-Daten</a>
        <a class="item" data-tab="bookings" style="white-space: nowrap;">{!! $bookingsTabLabel !!}</a>
        <a class="item" data-tab="teamer-nsc" style="white-space: nowrap;">Teamer/NSC ({{ $adventure->teamerSignups->count() + $nscBookings->count() }})</a>
        <a class="item" data-tab="checkin" style="white-space: nowrap;">Check-in</a>
        <a class="item" data-tab="tasks" style="white-space: nowrap;">
            <i class="clock icon"></i> Taskmanager
            @if ($tasks->where('is_active', true)->isNotEmpty())
                <span class="ui mini teal circular label ml-1">{{ $tasks->where('is_active', true)->count() }}</span>
            @endif
        </a>
    </div>

    <div class="ui bottom attached tab segment active" data-tab="data">
        <form id="manage-adventure-form" method="POST" action="{{ route('adventures.update', $adventure) }}" data-reload>
            @method('PUT')
            @include('adventures._form', ['inModal' => true])
        </form>
        @if ($adventure->event_status_id !== \App\Models\EventStatus::CANCELLED)
            <div class="ui red segment mt-4">
                <h5 class="ui header" style="color: #9b2c2c;">
                    <i class="ban icon"></i>
                    <div class="content">Gefahrenzone</div>
                </h5>
                <form method="POST" action="{{ route('adventures.cancel', $adventure) }}" data-refresh-modal
                      data-confirm="Abenteuer wirklich absagen? Es sind danach keine Anmeldungen mehr möglich.">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui red basic button">
                        <i class="ban icon"></i> Abenteuer absagen
                    </button>
                </form>
            </div>
        @else
            <div class="ui warning message mt-4" style="display:block">Dieses Event ist abgesagt.</div>
        @endif
    </div>

    <div class="ui bottom attached tab segment" data-tab="bookings">
        @include('adventures._waitlist_mode_toggle')
        <div class="flex flex-wrap gap-2 mb-3">
            <a href="{{ route('adventures.participation-xlsx', $adventure) }}" class="ui small button" target="_blank" rel="noopener"><i class="file excel outline icon"></i> Belegungsreport (Excel)</a>
            @can('book-any-player')
                <a href="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}"
                   data-modal-stack="{{ route('adventures.bookings.create', $adventure) . '?all_players=1' }}"
                   class="ui small button">
                    <i class="shield alternate icon"></i> Admin-Anmeldung
                </a>
            @endcan
            @if ($hasAnyEmails)
                <div class="inline-flex rounded border border-stone-200 bg-white">
                    <select id="email-filter-desk"
                            onchange="updateManageMailto('desk', this.value)"
                            style="border:none;border-right:1px solid rgba(34,36,38,.15);border-radius:.28rem 0 0 .28rem;padding:.45em .6em;font-size:.85em;background:transparent;cursor:pointer;outline:none;">
                        <option value="all">Alle Teilnehmer ({{ count($emailGroups['all']) }})</option>
                        <option value="confirmed">Nur Angemeldete ({{ count($emailGroups['confirmed']) }})</option>
                        <option value="unpaid">Unbezahlte Teilnehmer ({{ count($emailGroups['unpaid']) }})</option>
                        <option value="waitlist">Wartelisten Teilnehmer ({{ count($emailGroups['waitlist']) }})</option>
                    </select>
                    <div class="ui small teal floating dropdown button" id="mailto-dropdown-desk"
                         tabindex="0" style="margin:0;border-radius:0 .28rem .28rem 0;box-shadow:none;">
                        <i class="mail icon"></i> E-Mail <i class="dropdown icon"></i>
                        <div class="menu transition hidden" tabindex="-1" style="white-space:nowrap;">
                            <a class="item" onclick="manageMailtoAction('client','desk'); return false;">
                                <i class="external alternate icon"></i> An E-Mail-Client senden
                            </a>
                            <a class="item" onclick="manageMailtoAction('copy','desk'); return false;">
                                <i class="copy outline icon"></i> E-Mail-Adressen kopieren
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @include('adventures._bookings', ['bookings' => $mainBookings, 'manage' => true])
    </div>

    <div class="ui bottom attached tab segment" data-tab="teamer-nsc">
        @if ($hasAnyTeamerEmails)
            <div class="inline-flex rounded border border-stone-200 bg-white mb-3">
                <select id="email-filter-teamer-desk"
                        onchange="updateTeamerMailto('desk', this.value)"
                        style="border:none;border-right:1px solid rgba(34,36,38,.15);border-radius:.28rem 0 0 .28rem;padding:.45em .6em;font-size:.85em;background:transparent;cursor:pointer;outline:none;">
                    <option value="all">Alle Teamer ({{ count($teamerEmailGroups['all']) }})</option>
                    <option value="approved">Bestätigte Teamer ({{ count($teamerEmailGroups['approved']) }})</option>
                    <option value="pending">Unbestätigte Teamer ({{ count($teamerEmailGroups['pending']) }})</option>
                    <option value="missing">Fehlende Teamer ({{ count($teamerEmailGroups['missing']) }})</option>
                </select>
                <div class="ui small teal floating dropdown button" id="teamer-mailto-dropdown-desk"
                     tabindex="0" style="margin:0;border-radius:0 .28rem .28rem 0;box-shadow:none;">
                    <i class="mail icon"></i> E-Mail <i class="dropdown icon"></i>
                    <div class="menu transition hidden" tabindex="-1" style="white-space:nowrap;">
                        <a class="item" onclick="teamerMailtoAction('client','desk'); return false;">
                            <i class="external alternate icon"></i> An E-Mail-Client senden
                        </a>
                        <a class="item" onclick="teamerMailtoAction('copy','desk'); return false;">
                            <i class="copy outline icon"></i> E-Mail-Adressen kopieren
                        </a>
                    </div>
                </div>
            </div>
        @endif
        @include('adventures._teamer_nsc_tab', [
            'teamerSignups' => $adventure->teamerSignups,
            'nscBookings'   => $nscBookings,
        ])
        <div class="mt-4 pt-4 border-t border-stone-200">
            <form method="POST" action="{{ route('adventures.teamer.invite', $adventure) }}"
                  data-confirm="Einladung an alle aktiven Teamer und Lehrmeister schicken?">
                @csrf
                <button type="submit" class="ui teal button">
                    <i class="mail icon"></i> Teamer einladen
                </button>
                <span class="text-sm text-stone-500 ml-2">Benachrichtigt alle aktiven Teamer &amp; Lehrmeister mit eingeschalteten Benachrichtigungen.</span>
            </form>
        </div>
    </div>

    <div class="ui bottom attached tab segment" data-tab="checkin">
        @include('adventures._checkin')
    </div>

    <div class="ui bottom attached tab segment" data-tab="tasks">
        @include('adventures._tasks', ['tasks' => $tasks])
    </div>
</div>

@if ($hasAnyEmails)
<script>
(function () {
    var groups = {!! json_encode($emailGroups) !!};
    var to = {!! json_encode($manageMailTo) !!};
    var currentEmails = {};

    window.updateManageMailto = function (suffix, key) {
        var emails = groups[key] || [];
        currentEmails[suffix] = emails;
        var btn = document.getElementById('mailto-dropdown-' + suffix);
        if (!btn) return;
        btn.classList.toggle('disabled', emails.length === 0);
    };

    window.manageMailtoAction = function (action, suffix) {
        var emails = currentEmails[suffix] || [];
        if (!emails.length) return;
        if (action === 'client') {
            window.location.href = 'mailto:' + to + '?bcc=' + emails.join(',');
        } else if (action === 'copy') {
            navigator.clipboard.writeText(emails.join(', ')).then(function () {
                if (window.showToast) showToast(emails.length + ' E-Mail-Adressen kopiert.', 'success');
            }).catch(function () {
                if (window.showToast) showToast('Kopieren fehlgeschlagen – bitte manuell kopieren.', 'error');
            });
        }
    };

    ['mob', 'desk'].forEach(function (suffix) {
        var sel = document.getElementById('email-filter-' + suffix);
        if (sel) window.updateManageMailto(suffix, sel.value);
        // Dropdown-Init übernimmt DOMContentLoaded in manage.blade.php
    });
})();
</script>
@endif

@if ($hasAnyTeamerEmails)
<script>
(function () {
    var tGroups = {!! json_encode($teamerEmailGroups) !!};
    var tTo = {!! json_encode($manageMailTo) !!};
    var currentTeamerEmails = {};

    window.updateTeamerMailto = function (suffix, key) {
        var emails = tGroups[key] || [];
        currentTeamerEmails[suffix] = emails;
        var btn = document.getElementById('teamer-mailto-dropdown-' + suffix);
        if (!btn) return;
        btn.classList.toggle('disabled', emails.length === 0);
    };

    window.teamerMailtoAction = function (action, suffix) {
        var emails = currentTeamerEmails[suffix] || [];
        if (!emails.length) return;
        if (action === 'client') {
            window.location.href = 'mailto:' + tTo + '?bcc=' + emails.join(',');
        } else if (action === 'copy') {
            navigator.clipboard.writeText(emails.join(', ')).then(function () {
                if (window.showToast) showToast(emails.length + ' E-Mail-Adressen kopiert.', 'success');
            }).catch(function () {
                if (window.showToast) showToast('Kopieren fehlgeschlagen – bitte manuell kopieren.', 'error');
            });
        }
    };

    ['mob', 'desk'].forEach(function (suffix) {
        var sel = document.getElementById('email-filter-teamer-' + suffix);
        if (sel) window.updateTeamerMailto(suffix, sel.value);
        // Dropdown-Init übernimmt DOMContentLoaded in manage.blade.php
    });
})();
</script>
@endif

<div data-modal-actions hidden>
    <button type="submit" form="manage-adventure-form" class="ui primary button">
        <i class="save icon"></i> Speichern
    </button>
</div>
