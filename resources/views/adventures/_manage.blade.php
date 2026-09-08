<span data-modal-title hidden>Verwaltung: {{ $adventure->name }}</span>

@php
    $collectEmail = fn($b) => $b->guardian()?->email ?? $b->player?->email;

    $emailGroups = [
        'all'       => $mainBookings
            ->filter(fn($b) => ! $b->is_guest)
            ->map($collectEmail)->filter()->unique()->values()->all(),
        'confirmed' => $mainBookings
            ->where('waitlisted', false)
            ->filter(fn($b) => ! $b->is_guest)
            ->map($collectEmail)->filter()->unique()->values()->all(),
        'unpaid'    => $mainBookings
            ->where('waitlisted', false)
            ->where('paid', false)
            ->filter(fn($b) => ! $b->is_guest)
            ->map($collectEmail)->filter()->unique()->values()->all(),
        'waitlist'  => $mainBookings
            ->where('waitlisted', true)
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

    <x-mobile.accordion-section :title="'Anmeldungen (' . $mainBookings->count() . ')'">
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
                <div class="flex items-center gap-1">
                    <select id="email-filter-mob"
                            onchange="updateManageMailto('mob', this.value)"
                            style="border:1px solid rgba(34,36,38,.15);border-radius:.28571429rem;padding:.45em .7em;font-size:.85em;background:#fff;cursor:pointer">
                        <option value="all">Alle Teilnehmer ({{ count($emailGroups['all']) }})</option>
                        <option value="confirmed">Nur Angemeldete ({{ count($emailGroups['confirmed']) }})</option>
                        <option value="unpaid">Unbezahlte Teilnehmer ({{ count($emailGroups['unpaid']) }})</option>
                        <option value="waitlist">Wartelisten Teilnehmer ({{ count($emailGroups['waitlist']) }})</option>
                    </select>
                    <a id="mailto-btn-mob" href="#" class="ui small teal button">
                        <i class="mail icon"></i> E-Mail senden
                    </a>
                </div>
            @endif
        </div>
        @include('adventures._bookings', ['bookings' => $mainBookings, 'manage' => true])
    </x-mobile.accordion-section>

    <x-mobile.accordion-section :title="'Teamer/NSC (' . ($adventure->teamerSignups->count() + $nscBookings->count()) . ')'">
        @if ($hasAnyTeamerEmails)
            <div class="flex items-center gap-1 mb-3">
                <select id="email-filter-teamer-mob"
                        onchange="updateTeamerMailto('mob', this.value)"
                        style="border:1px solid rgba(34,36,38,.15);border-radius:.28571429rem;padding:.45em .7em;font-size:.85em;background:#fff;cursor:pointer">
                    <option value="all">Alle Teamer ({{ count($teamerEmailGroups['all']) }})</option>
                    <option value="approved">Bestätigte Teamer ({{ count($teamerEmailGroups['approved']) }})</option>
                    <option value="pending">Unbestätigte Teamer ({{ count($teamerEmailGroups['pending']) }})</option>
                    <option value="missing">Fehlende Teamer ({{ count($teamerEmailGroups['missing']) }})</option>
                </select>
                <a id="teamer-mailto-btn-mob" href="#" class="ui small teal button">
                    <i class="mail icon"></i> E-Mail senden
                </a>
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
</div>

{{-- Desktop: Fomantic-Tabs (sm+) --}}
<div class="hidden sm:block">
    <div class="ui top attached tabular menu" style="overflow-x: auto; flex-wrap: nowrap;">
        <a class="item active" data-tab="data" style="white-space: nowrap;">Event-Daten</a>
        <a class="item" data-tab="bookings" style="white-space: nowrap;">Anmeldungen ({{ $mainBookings->count() }})</a>
        <a class="item" data-tab="teamer-nsc" style="white-space: nowrap;">Teamer/NSC ({{ $adventure->teamerSignups->count() + $nscBookings->count() }})</a>
        <a class="item" data-tab="checkin" style="white-space: nowrap;">Check-in</a>
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
                <div class="flex items-center gap-1">
                    <select id="email-filter-desk"
                            onchange="updateManageMailto('desk', this.value)"
                            style="border:1px solid rgba(34,36,38,.15);border-radius:.28571429rem;padding:.45em .7em;font-size:.85em;background:#fff;cursor:pointer">
                        <option value="all">Alle Teilnehmer ({{ count($emailGroups['all']) }})</option>
                        <option value="confirmed">Nur Angemeldete ({{ count($emailGroups['confirmed']) }})</option>
                        <option value="unpaid">Unbezahlte Teilnehmer ({{ count($emailGroups['unpaid']) }})</option>
                        <option value="waitlist">Wartelisten Teilnehmer ({{ count($emailGroups['waitlist']) }})</option>
                    </select>
                    <a id="mailto-btn-desk" href="#" class="ui small teal button">
                        <i class="mail icon"></i> E-Mail senden
                    </a>
                </div>
            @endif
        </div>
        @include('adventures._bookings', ['bookings' => $mainBookings, 'manage' => true])
    </div>

    <div class="ui bottom attached tab segment" data-tab="teamer-nsc">
        @if ($hasAnyTeamerEmails)
            <div class="flex items-center gap-1 mb-3">
                <select id="email-filter-teamer-desk"
                        onchange="updateTeamerMailto('desk', this.value)"
                        style="border:1px solid rgba(34,36,38,.15);border-radius:.28571429rem;padding:.45em .7em;font-size:.85em;background:#fff;cursor:pointer">
                    <option value="all">Alle Teamer ({{ count($teamerEmailGroups['all']) }})</option>
                    <option value="approved">Bestätigte Teamer ({{ count($teamerEmailGroups['approved']) }})</option>
                    <option value="pending">Unbestätigte Teamer ({{ count($teamerEmailGroups['pending']) }})</option>
                    <option value="missing">Fehlende Teamer ({{ count($teamerEmailGroups['missing']) }})</option>
                </select>
                <a id="teamer-mailto-btn-desk" href="#" class="ui small teal button">
                    <i class="mail icon"></i> E-Mail senden
                </a>
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
</div>

@if ($hasAnyEmails)
<script>
(function () {
    var groups = {!! json_encode($emailGroups) !!};
    var to = {!! json_encode($manageMailTo) !!};

    window.updateManageMailto = function (suffix, key) {
        var emails = groups[key] || [];
        var btn = document.getElementById('mailto-btn-' + suffix);
        if (!btn) return;
        if (emails.length === 0) {
            btn.classList.add('disabled');
            btn.removeAttribute('href');
        } else {
            btn.classList.remove('disabled');
            btn.href = 'mailto:' + to + '?bcc=' + emails.join(',');
        }
    };

    ['mob', 'desk'].forEach(function (suffix) {
        var sel = document.getElementById('email-filter-' + suffix);
        if (sel) window.updateManageMailto(suffix, sel.value);
    });
})();
</script>
@endif

@if ($hasAnyTeamerEmails)
<script>
(function () {
    var tGroups = {!! json_encode($teamerEmailGroups) !!};
    var tTo = {!! json_encode($manageMailTo) !!};

    window.updateTeamerMailto = function (suffix, key) {
        var emails = tGroups[key] || [];
        var btn = document.getElementById('teamer-mailto-btn-' + suffix);
        if (!btn) return;
        if (emails.length === 0) {
            btn.classList.add('disabled');
            btn.removeAttribute('href');
        } else {
            btn.classList.remove('disabled');
            btn.href = 'mailto:' + tTo + '?bcc=' + emails.join(',');
        }
    };

    ['mob', 'desk'].forEach(function (suffix) {
        var sel = document.getElementById('email-filter-teamer-' + suffix);
        if (sel) window.updateTeamerMailto(suffix, sel.value);
    });
})();
</script>
@endif

<div data-modal-actions hidden>
    <button type="submit" form="manage-adventure-form" class="ui primary button">
        <i class="save icon"></i> Speichern
    </button>
</div>
