<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    {{-- Issue #19: Abrechnung der Aufwandsentschädigung, nachgebaut nach der Vereinsvorlage
         „Aufwandsentschädigung Teamer 2026“. Unterschrift bleibt leer (händisch). --}}
    <style>
        @page { margin: 40px 50px 40px 50px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 11px; color: #111; }
        .verein { text-align: right; font-size: 22px; font-weight: bold; margin: 0 0 18px; }
        h1 { font-size: 13px; margin: 0 0 18px; }
        p { margin: 0 0 10px; }
        .amount { margin: 6px 0 12px 60px; }
        .amount strong { font-size: 12px; }
        table.box { width: 100%; border-collapse: collapse; margin: 0 0 16px; }
        table.box th { background: #fff8c4; text-align: left; font-size: 11px; padding: 5px 6px; border: 1px solid #333; }
        table.box td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        table.box td.label { width: 32%; }
        td.event { height: 60px; }
        .legal { font-size: 8px; text-align: center; margin: 4px 20px 50px; }
        .signature { border-top: 1px solid #333; width: 100%; padding-top: 2px; font-size: 9px; margin-bottom: 26px; }
        table.footer { width: 100%; font-size: 9px; }
        table.footer td { vertical-align: top; }
    </style>
</head>
<body>
    @php
        $role = $signup->eventRole?->description ?? 'Teamer';
        $start = $adventure->start_at;
        $end = $adventure->end_at;
        $dates = $start?->format('d.m.Y');
        if ($end && $start && ! $end->isSameDay($start)) {
            $dates .= ' – '.$end->format('d.m.Y');
        }
        $place = collect([$adventure->location?->titel, $adventure->location?->city])->filter()->unique()->implode(', ');
    @endphp

    <p class="verein">Waldritter-Gießen e. V.</p>
    <h1>Rechnung an Waldritter-Gießen e. V. zur Aufwandsentschädigung</h1>

    <p>Aufwandsentschädigung: {{ $adventure->name }} – {{ $start?->format('Y') }}</p>

    <p>Hiermit teile ich dem Waldritter-Gießen e. V. mit, dass ich als <strong>{{ $role }}</strong> folgenden Aufwand abrechne:</p>

    <p class="amount">
        <strong>{{ number_format((float) $amount, 2, ',', '.') }} €</strong>
        &nbsp;&nbsp; in Worten: {{ $amountWords }}
    </p>

    <p>für meine ehrenamtliche Tätigkeit als <strong>{{ $role }}</strong></p>

    <table class="box">
        <tr><th>Veranstaltung, Ort, Datum + ggf. Erläuterungen</th></tr>
        <tr><td class="event">{{ $adventure->name }}@if ($place), {{ $place }}@endif, {{ $dates }}</td></tr>
    </table>

    <table class="box">
        <tr><th colspan="2">Angaben zur Person</th></tr>
        <tr><td class="label">Name, Vorname:</td><td>{{ trim(($user->lastname ? $user->lastname.', ' : '').$user->name) }}</td></tr>
        <tr><td class="label">Straße, Hausnummer:</td><td>{{ trim($user->street.' '.$user->house_number) }}</td></tr>
        <tr><td class="label">PLZ, Ort:</td><td>{{ trim($user->zip.' '.$user->city) }}</td></tr>
        <tr><td class="label">Steuernummer:</td><td>{{ $user->teamer_tax_number }}</td></tr>
        <tr><td class="label">E-Mail (für Rückfragen):</td><td>{{ $user->email }}</td></tr>
    </table>

    <table class="box">
        <tr><th colspan="2">Erstattung auf folgendes Konto</th></tr>
        <tr><td class="label">Empfänger:</td><td>{{ $user->teamer_account_holder }}</td></tr>
        <tr><td class="label">IBAN:</td><td>{{ $iban }}</td></tr>
        <tr><td class="label">Name der Bank:</td><td>{{ $user->teamer_bank_name }}</td></tr>
    </table>

    <p class="legal">
        Ich bestätige, dass ich vom Waldritter-Gießen e. V. darüber unterrichtet worden bin, dass ich für eine ordnungsgemäße
        Versteuerung des Honorars selbst zu sorgen habe. Ich wurde von Waldritter-Gießen e. V. darauf aufmerksam gemacht, dass nach § 3
        Nr. 26 Einkommensteuergesetz (EstG) Einnahmen für nebenberufliche Tätigkeiten als Übungsleiter, Ausbilder, Erzieher oder für eine
        vergleichbare nebenberufliche Tätigkeit im Dienst oder Auftrag einer gemeinnützigen Einrichtung (nach § 5 Abs. 1 Nr. 9
        Körperschaftsteuergesetz; § 52 Abgabenordnung) bis zu einer Höhe von 3.000,00 € im Jahr steuerfreie Einnahmen sind.
    </p>

    <div class="signature">Ort, Datum, Unterschrift</div>

    <table class="footer">
        <tr>
            <td colspan="3">Waldritter-Gießen e. V</td>
        </tr>
        <tr>
            <td>Am Rinnerborn 54<br>35418 Buseck<br>www.waldritter-giessen.de</td>
            <td>IBAN DE45 5139 0000 0080 1782 04<br>BIC VBMHDE5F<br>Volksbank Mittelhessen eG</td>
            <td style="text-align:right">St.-Nr.: 020 250 63440<br>Gemeinnütziger Verein</td>
        </tr>
    </table>
</body>
</html>
