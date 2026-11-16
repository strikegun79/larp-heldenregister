{{-- Inspektor (Slide-In) einer Teamer-Anmeldung in der Event-Verwaltung.
     Wird per AJAX in #booking-inspector-content geladen. Formulare mit
     data-inspector-form werden per AJAX abgeschickt (heldenregister.js). --}}
@php
    $user  = $signup->user;
    $name  = trim($user->name.' '.$user->lastname);
    $yesNo = fn (bool $v) => $v ? 'ja' : 'nein';
@endphp

<div data-inspector-title hidden>{{ $name }}</div>

{{-- Kopf: Status auf einen Blick --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
    <span class="ui small basic label">Teamer</span>
    @if ($signup->approved_at)
        <span class="ui small green label">✓ bestätigt</span>
    @elseif ($signup->rejected_at)
        <span class="ui small red label">abgelehnt</span>
    @else
        <span class="ui small label">offen</span>
    @endif
</div>

{{-- Anmeldung --}}
<section class="mb-5" aria-labelledby="insp-t-anmeldung">
    <h3 id="insp-t-anmeldung" class="font-uncial text-waldritter text-base mb-2">Anmeldung</h3>
    <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
        <dt class="text-stone-500">Rolle</dt>
        <dd>{{ $signup->eventRole?->description ?? '—' }}</dd>

        <dt class="text-stone-500">Angemeldet</dt>
        <dd>{{ $signup->created_at->format('d.m.Y H:i') }}</dd>

        @if ($signup->approved_at)
            <dt class="text-stone-500">Bestätigt</dt>
            <dd>{{ $signup->approved_at->format('d.m.Y H:i') }}</dd>
        @elseif ($signup->rejected_at)
            <dt class="text-stone-500">Abgelehnt</dt>
            <dd>{{ $signup->rejected_at->format('d.m.Y H:i') }}</dd>
        @endif
    </dl>
</section>

{{-- Kontakt --}}
<section class="mb-5" aria-labelledby="insp-t-kontakt">
    <h3 id="insp-t-kontakt" class="font-uncial text-waldritter text-base mb-2">Kontakt</h3>
    <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
        @if ($user->email)
            <dt class="text-stone-500">E-Mail</dt>
            <dd class="break-all"><a href="mailto:{{ $user->email }}" class="text-waldritter underline">{{ $user->email }}</a></dd>
        @endif

        @if ($user->phone)
            <dt class="text-stone-500">Telefon</dt>
            <dd><a href="tel:{{ $user->phone }}" class="text-waldritter underline">{{ $user->phone }}</a></dd>
        @endif

        @if ($signup->kontakt_telefon)
            <dt class="text-stone-500">Kontakt-Tel.</dt>
            <dd><a href="tel:{{ $signup->kontakt_telefon }}" class="text-waldritter underline">{{ $signup->kontakt_telefon }}</a></dd>
        @endif
    </dl>
</section>

{{-- Optionen --}}
<section class="mb-5" aria-labelledby="insp-t-optionen">
    <h3 id="insp-t-optionen" class="font-uncial text-waldritter text-base mb-2">Optionen</h3>
    <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
        <dt class="text-stone-500">Leih-Tunika</dt><dd>{{ $yesNo($signup->leih_tunika) }}</dd>
        <dt class="text-stone-500">Leih-Waffe</dt><dd>{{ $yesNo($signup->leih_waffe) }}</dd>
        @if ($signup->anmerkung)
            <dt class="text-stone-500">Anmerkung</dt>
            <dd class="whitespace-pre-line">{{ $signup->anmerkung }}</dd>
        @endif
    </dl>
</section>

{{-- Gesundheitsdaten (Art. 9 DSGVO): nur mit derselben Berechtigung wie im Bearbeiten-Formular --}}
@can('events.edit')
    @if ($signup->allergien || $signup->medikamente)
        <section class="mb-5" aria-labelledby="insp-t-gesundheit">
            <h3 id="insp-t-gesundheit" class="font-uncial text-waldritter text-base mb-2">Gesundheit</h3>
            <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
                @if ($signup->allergien)
                    <dt class="text-stone-500">Allergien</dt>
                    <dd class="whitespace-pre-line">{{ $signup->allergien }}</dd>
                @endif
                @if ($signup->medikamente)
                    <dt class="text-stone-500">Medikamente</dt>
                    <dd class="whitespace-pre-line">{{ $signup->medikamente }}</dd>
                @endif
                <dt class="text-stone-500">Einwilligung</dt>
                <dd>
                    @if ($signup->health_data_consent_at)
                        erteilt am {{ $signup->health_data_consent_at->format('d.m.Y H:i') }}
                    @else
                        <span class="text-amber-700">nicht erteilt</span>
                    @endif
                </dd>
            </dl>
        </section>
    @endif
@endcan

{{-- Aktionen: vorhandene Teamer-Aktionen (Mails wie bisher) --}}
<section aria-labelledby="insp-t-aktionen" class="border-t border-stone-200 pt-4">
    <h3 id="insp-t-aktionen" class="font-uncial text-waldritter text-base mb-3">Aktionen</h3>
    <div class="flex flex-col gap-2">
        @can('events.edit')
            @if ($signup->approved_at)
                <form method="POST" action="{{ route('adventures.teamer.approve', [$adventure, $signup]) }}" data-inspector-form
                      data-confirm="Bestätigung für {{ $name }} zurücknehmen?">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui fluid small basic button"><i class="undo icon"></i> Bestätigung zurücknehmen</button>
                </form>
                <form method="POST" action="{{ route('adventures.teamer.resend-confirmation', [$adventure, $signup]) }}" data-inspector-form>
                    @csrf
                    <button type="submit" class="ui fluid small button"><i class="envelope icon"></i> Bestätigungsmail erneut senden</button>
                </form>
            @else
                <form method="POST" action="{{ route('adventures.teamer.approve', [$adventure, $signup]) }}" data-inspector-form
                      data-confirm="Teamer-Anmeldung von {{ $name }} bestätigen? Der Teamer wird per E-Mail informiert.">
                    @csrf @method('PATCH')
                    <button type="submit" class="ui fluid small green button"><i class="check circle icon"></i> Bestätigen</button>
                </form>
                <form method="POST" action="{{ route('adventures.teamer.reject', [$adventure, $signup]) }}" data-inspector-form
                      data-confirm="{{ $signup->rejected_at ? 'Ablehnung für '.$name.' zurücknehmen? Der Status wird wieder „offen“.' : 'Teamer-Anmeldung von '.$name.' ablehnen? Der Teamer wird per E-Mail informiert.' }}">
                    @csrf @method('PATCH')
                    @if ($signup->rejected_at)
                        <button type="submit" class="ui fluid small basic button"><i class="undo icon"></i> Ablehnung zurücknehmen</button>
                    @else
                        <button type="submit" class="ui fluid small orange button"><i class="ban icon"></i> Ablehnen</button>
                    @endif
                </form>
            @endif

            <a href="{{ route('adventures.teamer.edit', [$adventure, $signup]) }}"
               data-modal-stack="{{ route('adventures.teamer.edit', [$adventure, $signup]) }}" data-inspector-close
               class="ui fluid small button"><i class="edit icon"></i> Bearbeiten</a>
        @endcan

        {{-- Stornieren: gleiche Berechtigung wie im Controller (portal.manage) --}}
        @can('portal.manage')
            <form method="POST" action="{{ route('adventures.teamer.destroy', [$adventure, $signup]) }}" data-inspector-form data-inspector-close-on-success
                  data-confirm="Teamer-Anmeldung von {{ $name }} wirklich stornieren? Die Anmeldung wird gelöscht.">
                @csrf @method('DELETE')
                <button type="submit" class="ui fluid small red button"><i class="times icon"></i> Stornieren</button>
            </form>
        @endcan
    </div>
</section>
