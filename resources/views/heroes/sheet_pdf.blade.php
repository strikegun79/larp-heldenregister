<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 18px; margin: 0 0 2px; color: #5a3a22; }
        h2 { font-size: 13px; margin: 14px 0 4px; color: #5a3a22; border-bottom: 1px solid #5a3a22; padding-bottom: 2px; }
        .sub { color: #666; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        td.k { color: #666; width: 38%; padding: 2px 6px 2px 0; vertical-align: top; }
        table.list th, table.list td { border: 1px solid #999; padding: 3px 6px; text-align: left; }
        table.list th { background: #eee; font-size: 10px; text-transform: uppercase; }
        td.r { text-align: right; }
    </style>
</head>
<body>
    <div style="text-align:right; font-size:10px; color:#888;">Waldritter-Gießen e.V. · Heldenregister</div>
    <h1>{{ $hero->character_name ?? 'Held' }}</h1>
    <p class="sub">Charakterbogen</p>

    <table>
        <tr><td class="k">Spieler</td><td>{{ $hero->player?->full_name ?? '—' }}</td></tr>
        <tr><td class="k">Klassen</td><td>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</td></tr>
        <tr><td class="k">Heimatort</td><td>{{ $hero->homeplace ?: '—' }}</td></tr>
        <tr><td class="k">Erste Erblickung</td><td>{{ optional($hero->born)->format('d.m.Y') ?? '—' }}</td></tr>
        <tr><td class="k">Status</td><td>{{ $hero->died ? 'verschollen ('.$hero->died->format('d.m.Y').')' : ($hero->active ? 'aktiv' : 'inaktiv') }}</td></tr>
        <tr><td class="k">EP-Saldo</td><td>{{ number_format($hero->ep_balance, 0, ',', '.') }} EP (erworben {{ number_format($hero->ep_total, 0, ',', '.') }}, ausgegeben {{ number_format($hero->ep_spent, 0, ',', '.') }})</td></tr>
    </table>

    @if ($hero->description)
        <h2>Steckbrief</h2>
        <p>{{ $hero->description }}</p>
    @endif

    <h2>Fertigkeiten ({{ $hero->skills_count }})</h2>
    @if ($hero->skills->isEmpty())
        <p>Keine Fertigkeiten erlernt.</p>
    @else
        <table class="list">
            <thead><tr><th>Fertigkeit</th><th>Perlen</th><th class="r">EP</th></tr></thead>
            <tbody>
                @foreach ($hero->skills as $skill)
                    <tr>
                        <td>{{ $skill->name }}</td>
                        <td>{{ $skill->perl_count ? $skill->perl_count.' Perle(n)' : '—' }}</td>
                        <td class="r">{{ $skill->ep_costs }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
