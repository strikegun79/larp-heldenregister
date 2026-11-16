{{-- Inspektor (Slide-In) einer Anmeldung in der Event-Verwaltung (Issue #9).
     Wird per AJAX in #booking-inspector-content geladen. Formulare mit
     data-inspector-form werden per AJAX abgeschickt (heldenregister.js). --}}
@php
    $isTeamer   = (bool) $booking->role?->is_teamer_like;
    $isCanceled = $booking->status === 'storniert';
    $guardian   = $booking->is_guest ? null : $booking->guardian();
    $player     = $booking->player;
    $name       = $booking->participant_name;
    $yesNo      = fn (bool $v) => $v ? 'ja' : 'nein';
@endphp

<div data-inspector-title hidden>{{ $name }}</div>

{{-- Kopf: Status auf einen Blick --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
    @if ($booking->waitlisted)
        <span class="ui small yellow label">⏳ Warteliste</span>
    @else
        <span class="ui small basic label">regulär</span>
    @endif

    @switch($booking->status)
        @case('bestaetigt')
            <span class="ui small green label">✓ bestätigt</span>
            @break
        @case('abgelehnt')
            <span class="ui small red label">abgelehnt</span>
            @break
        @case('abgemeldet')
            <span class="ui small orange label">abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
            @break
        @case('storniert')
            <span class="ui small grey label">storniert</span>
            @break
        @default
            <span class="ui small label">offen</span>
    @endswitch

    @if ($booking->is_guest)
        <span class="ui small label">Gast</span>
    @endif
</div>

{{-- Anmeldung --}}
<section class="mb-5" aria-labelledby="insp-anmeldung">
    <h3 id="insp-anmeldung" class="font-uncial text-waldritter text-base mb-2">Anmeldung</h3>
    <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
        <dt class="text-stone-500">Alter</dt>
        <dd>
            {{ $booking->participant_age ?? '—' }}
            @if ($player?->dayofbirth)
                <span class="text-stone-500">(geb. {{ $player->dayofbirth->format('d.m.Y') }})</span>
            @endif
        </dd>

        <dt class="text-stone-500">Rolle</dt>
        <dd>{{ $booking->role?->description ?? '—' }}</dd>

        <dt class="text-stone-500">Angemeldet</dt>
        <dd>
            {{ $booking->created_at->format('d.m.Y H:i') }}
            @if ($booking->bookedBy)
                <span class="text-stone-500">von {{ $booking->bookedBy->name }} {{ $booking->bookedBy->lastname }}</span>
            @endif
        </dd>

        @unless ($isTeamer)
            <dt class="text-stone-500">Beitrag</dt>
            <dd>
                @if ($adventure->fee > 0)
                    {{ number_format($booking->effectiveFee(), 2, ',', '.') }} €
                    @if ($booking->ermaessigung && $adventure->fee_reduced !== null)
                        <span class="ui mini label">ermäßigt</span>
                    @endif
                    ·
                @endif
                @if ($booking->paid)
                    <span class="text-green-700">✓ bezahlt</span>
                @else
                    <span class="text-stone-500">offen</span>
                @endif
            </dd>
        @endunless

        @if ($booking->is_guest && $booking->guest_place)
            <dt class="text-stone-500">Ort</dt>
            <dd>{{ $booking->guest_place }}</dd>
        @endif
    </dl>
</section>

{{-- Kontakt --}}
@unless ($booking->is_guest)
    <section class="mb-5" aria-labelledby="insp-kontakt">
        <h3 id="insp-kontakt" class="font-uncial text-waldritter text-base mb-2">Kontakt</h3>
        <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
            @if ($guardian)
                <dt class="text-stone-500">Erziehungsber.</dt>
                <dd>{{ $guardian->name }} {{ $guardian->lastname }}</dd>

                @if ($guardian->email)
                    <dt class="text-stone-500">E-Mail</dt>
                    <dd class="break-all"><a href="mailto:{{ $guardian->email }}" class="text-waldritter underline">{{ $guardian->email }}</a></dd>
                @endif

                @if ($guardian->phone)
                    <dt class="text-stone-500">Telefon</dt>
                    <dd><a href="tel:{{ $guardian->phone }}" class="text-waldritter underline">{{ $guardian->phone }}</a></dd>
                @endif
            @endif

            @if ($booking->kontakt_telefon)
                <dt class="text-stone-500">Notfall-Tel.</dt>
                <dd><a href="tel:{{ $booking->kontakt_telefon }}" class="text-waldritter underline">{{ $booking->kontakt_telefon }}</a></dd>
            @endif

            @if ($booking->erreichbarkeit)
                <dt class="text-stone-500">Erreichbarkeit</dt>
                <dd class="whitespace-pre-line">{{ $booking->erreichbarkeit }}</dd>
            @endif

            <dt class="text-stone-500">Anschrift</dt>
            <dd>
                @if (! $booking->usesGuardianAddress() && $player?->street)
                    {{ $player->street }} {{ $player->house_number }}, {{ $player->zip }} {{ $player->city }}
                    <span class="text-amber-700">(Kind, abweichend)</span>
                @elseif ($guardian?->street)
                    {{ $guardian->street }} {{ $guardian->house_number }}, {{ $guardian->zip }} {{ $guardian->city }}
                @else
                    —
                @endif
            </dd>
        </dl>
    </section>
@endunless

{{-- Optionen --}}
<section class="mb-5" aria-labelledby="insp-optionen">
    <h3 id="insp-optionen" class="font-uncial text-waldritter text-base mb-2">Optionen</h3>
    <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
        <dt class="text-stone-500">Fotoerlaubnis</dt><dd>{{ $yesNo($booking->fotoerlaubnis) }}</dd>
        <dt class="text-stone-500">Vegetarisch</dt><dd>{{ $yesNo($booking->vegetarier) }}</dd>
        <dt class="text-stone-500">Leih-Tunika</dt><dd>{{ $yesNo($booking->leih_tunika) }}</dd>
        <dt class="text-stone-500">Leih-Waffe</dt><dd>{{ $yesNo($booking->leih_waffe) }}</dd>
        <dt class="text-stone-500">NSC</dt><dd>{{ $yesNo($booking->nsc) }}</dd>
    </dl>
</section>

{{-- Gesundheitsdaten (Art. 9 DSGVO): nur mit derselben Berechtigung wie im Bearbeiten-Formular --}}
@can('adventure.modify')
    @if ($booking->allergien || $booking->medikamente)
        <section class="mb-5" aria-labelledby="insp-gesundheit">
            <h3 id="insp-gesundheit" class="font-uncial text-waldritter text-base mb-2">Gesundheit</h3>
            <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
                @if ($booking->allergien)
                    <dt class="text-stone-500">Allergien</dt>
                    <dd class="whitespace-pre-line">{{ $booking->allergien }}</dd>
                @endif
                @if ($booking->medikamente)
                    <dt class="text-stone-500">Medikamente</dt>
                    <dd class="whitespace-pre-line">{{ $booking->medikamente }}</dd>
                @endif
            </dl>
        </section>
    @endif
@endcan

{{-- Aktionen: Status ändern über die vorhandenen Aktionen (Mails + Audit-Log wie bisher) --}}
<section aria-labelledby="insp-aktionen" class="border-t border-stone-200 pt-4">
    <h3 id="insp-aktionen" class="font-uncial text-waldritter text-base mb-3">Aktionen</h3>
    <div class="flex flex-col gap-2">
        @if (! $isCanceled)
            @can('approve-bookings')
                @unless ($booking->is_guest)
                    @if ($booking->waitlisted)
                        <form method="POST" action="{{ route('adventures.bookings.resend-confirmation', [$adventure, $booking]) }}" data-inspector-form
                              data-confirm="{{ $name }} von der Warteliste auf einen regulären Platz hochstufen? Die Bestätigung wird per E-Mail gesendet.">
                            @csrf
                            <button type="submit" class="ui fluid small green button"><i class="check icon"></i> Von Warteliste bestätigen</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('adventures.bookings.move-to-waitlist', [$adventure, $booking]) }}" data-inspector-form
                              data-confirm="{{ $name }} auf die Warteliste verschieben? Der Teilnehmer wird per E-Mail informiert.">
                            @csrf @method('PATCH')
                            <button type="submit" class="ui fluid small yellow button"><i class="hourglass half icon"></i> Auf Warteliste verschieben</button>
                        </form>
                    @endif
                @endunless

                <form method="POST" action="{{ route('adventures.bookings.rejection', [$adventure, $booking]) }}" data-inspector-form
                      data-confirm="{{ $booking->status === 'abgelehnt' ? 'Ablehnung für '.$name.' zurücknehmen? Der Status wird wieder „offen“.' : $name.' ablehnen? Der Teilnehmer wird per E-Mail informiert.' }}">
                    @csrf @method('PATCH')
                    @if ($booking->status === 'abgelehnt')
                        <button type="submit" class="ui fluid small basic button"><i class="undo icon"></i> Ablehnung zurücknehmen</button>
                    @else
                        <button type="submit" class="ui fluid small orange button"><i class="ban icon"></i> Ablehnen</button>
                    @endif
                </form>
            @endcan

            @unless ($booking->is_guest || $booking->waitlisted)
                @can('approve-bookings')
                    <form method="POST" action="{{ route('adventures.bookings.resend-confirmation', [$adventure, $booking]) }}" data-inspector-form>
                        @csrf
                        <button type="submit" class="ui fluid small button"><i class="envelope icon"></i> Anmeldebestätigung erneut senden</button>
                    </form>
                @endcan
            @endunless

            @unless ($isTeamer)
                @can('manage-payments')
                    <form method="POST" action="{{ route('adventures.bookings.payment', [$adventure, $booking]) }}" data-inspector-form
                          data-confirm="{{ $booking->paid ? 'Beitrag als offen markieren?' : 'Beitrag als bezahlt markieren?' }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="ui fluid small button"><i class="coins icon"></i> {{ $booking->paid ? 'Als offen markieren' : 'Als bezahlt markieren' }}</button>
                    </form>
                @endcan
            @endunless

            @can('adventure.modify')
                <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                   data-modal-stack="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}" data-inspector-close
                   class="ui fluid small button"><i class="edit icon"></i> Bearbeiten</a>
            @endcan

            @can('adventure.cancel')
                <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}" data-inspector-form
                      data-confirm="Anmeldung von {{ $name }} wirklich stornieren? Diese Aktion kann nur der Projektleiter rückgängig machen.">
                    @csrf @method('DELETE')
                    <button type="submit" class="ui fluid small red button"><i class="times icon"></i> Stornieren</button>
                </form>
            @endcan
        @else
            @can('approve-bookings')
                <form method="POST" action="{{ route('adventures.bookings.reinstate', [$adventure, $booking]) }}" data-inspector-form
                      data-confirm="Anmeldung von {{ $name }} wiederherstellen?">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui fluid small teal button"><i class="undo icon"></i> Anmeldung wiederherstellen</button>
                </form>
            @else
                <p class="text-sm text-stone-500">Diese Anmeldung ist storniert. Nur die Projektleitung kann sie wiederherstellen.</p>
            @endcan
        @endif
    </div>
</section>
