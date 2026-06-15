<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { margin: 0 0 12px; font-size: 11px; }
        .meta td { padding: 1px 8px 1px 0; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th, table.list td { border: 1px solid #888; padding: 4px 6px; text-align: left; vertical-align: middle; }
        table.list th { background: #eee; font-size: 10px; text-transform: uppercase; }
        td.nr { width: 28px; text-align: right; }
        td.sig img { height: 38px; }
    </style>
</head>
<body>
    <h1>Teilnehmerliste – {{ $adventure->name }}</h1>

    <table class="meta">
        <tr>
            <td><strong>Datum:</strong> {{ optional($adventure->start_at)->format('d.m.Y H:i') }}@if ($adventure->end_at)–{{ $adventure->end_at->format('d.m.Y H:i') }}@endif</td>
            <td><strong>Ort:</strong> {{ $adventure->location?->titel ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Typ:</strong> {{ $adventure->category?->name ?? '—' }}</td>
            <td><strong>Männlich:</strong> {{ $male }} &nbsp;·&nbsp; <strong>Weiblich:</strong> {{ $female }} &nbsp;·&nbsp; <strong>Divers:</strong> {{ $diverse }} &nbsp;·&nbsp; <strong>Gesamt:</strong> {{ $bookings->count() }}</td>
        </tr>
    </table>

    <table class="list">
        <thead>
            <tr>
                <th>Nr.</th>
                <th>Nachname</th>
                <th>Vorname</th>
                <th>Alter</th>
                <th>Ort</th>
                <th>Kontaktrufnummer</th>
                <th>Unterschrift</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $i => $booking)
                <tr>
                    <td class="nr">{{ $i + 1 }}</td>
                    <td>{{ $booking->is_guest ? $booking->guest_lastname : $booking->player?->lastname }}@if ($booking->is_guest) <strong>(Gast)</strong>@endif</td>
                    <td>{{ $booking->is_guest ? $booking->guest_name : $booking->player?->name }}</td>
                    <td>{{ $booking->participant_age ?? '' }}</td>
                    <td>{{ $booking->is_guest ? $booking->guest_place : $booking->player?->place }}</td>
                    <td>{{ $booking->erreichbarkeit }}</td>
                    <td class="sig">
                        @if ($booking->signature)
                            <img src="{{ $booking->signature }}" alt="">
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Keine Anmeldungen.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
