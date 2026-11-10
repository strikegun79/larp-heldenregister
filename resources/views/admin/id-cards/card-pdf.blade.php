<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; }

    /*
     * Klappformat: Vorderseite oben + Rückseite 180° gedreht darunter.
     * Entlang der waagerechten Mittellinie klappen → Rückseite liegt korrekt auf
     * der Vorderseite. Keine Spaltenumkehrung nötig (waagerechte Klappachse
     * spiegelt nur vertikal, nicht horizontal).
     *
     * Bis zu 3 Kombos nebeneinander auf A4 quer (29,7 × 21 cm).
     * Kombo: Vorderseite (10 cm) + Rückseite 180° gedreht (10 cm) = 20 cm Höhe < 21 cm ✓
     * Breite: 3 × 7,52 cm + 2 × 1 mm = 22,76 cm < 29,7 cm ✓
     * Tabellenbasiertes Layout, da DOMPDF kein Flexbox unterstützt.
     */
    @page { margin: 0; size: A4 landscape; } /* DOMPDF ignoriert @page-margin – Zentrierung via Spacer-Div */

    .page-break { page-break-before: always; }

    /* Tabellen-Wrapper für 3 Kombos nebeneinander */
    .combo-table {
        border-collapse: collapse;
        border-spacing: 0;
        margin: 0 auto;
    }

    .combo-cell {
        vertical-align: top;
        padding: 0;
        width: 7.52cm;
    }

    .gap-cell {
        width: 1mm;
        padding: 0;
    }

    .card-combo {
        width: 7.52cm;
    }

    /*
     * Vorderseite: Template-Bild 7,52 cm × 10 cm.
     * Pixelgenaue Positionen aus dem 980 × 1312 px Template (sx=7.52/980, sy=10/1312):
     *   QR:     left 4,696 cm / top 7,477 cm / 1,895 × 1,677 cm
     *   Siegel: Mittelpunkt x:250px → 1,918 cm / y:1103px → 8,407 cm
     *           Feld 293×106 px → 2,248×0,808 cm
     *           left = 1,918 − 1,124 = 0,794 cm / top = 8,407 − 0,404 = 8,003 cm
     */
    .card {
        width: 7.52cm;
        height: 10cm;
        position: relative;
        overflow: hidden;
    }

    .card.has-front-template {
        background-image: url("{{ str_replace('\\', '/', resource_path('images/template_helden_ausweis_vorderseite.png')) }}");
        background-size: 100% 100%;
        background-repeat: no-repeat;
    }

    .card.no-template {
        border: 1px solid #5a3a22;
        border-radius: 0.3cm;
        background: #fdf8f0;
    }

    .card-qr {
        position: absolute;
        left: 4.696cm;
        top: 7.477cm;
        width: 1.895cm;
        height: 1.677cm;
    }

    .card-qr img { width: 100%; height: 100%; }

    /* Siegel-Box: pixelgenau positioniert, Inhalt per Tabelle zentriert */
    .card-siegel {
        position: absolute;
        left: 0.794cm;
        top: 8.003cm;
        width: 2.248cm;
        height: 0.808cm;
        overflow: hidden;
    }

    .card-siegel table {
        width: 100%;
        height: 100%;
        border-collapse: collapse;
    }

    .card-siegel td {
        text-align: center;
        vertical-align: middle;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 14pt; /* pt statt px: 14pt = ~5mm, unabhängig von DOMPDF-DPI */
        font-weight: bold;
        letter-spacing: 0.04cm;
        color: #1a0a00;
        padding: 0;
    }

    /*
     * Name über der Namenslinie (Template: x 270–870 px, Linie bei y 423 px)
     * → left 2,072 cm / top 2,745 cm (leicht angehoben für Unterlängen) / 4,604 × 0,381 cm (Zeilenhöhe, da DOMPDF
     *   Tabellenhöhen in absolut positionierten Boxen ignoriert).
     */
    .card-name {
        position: absolute;
        left: 2.072cm;
        top: 2.745cm;
        width: 4.604cm;
        height: 0.381cm;
        line-height: 0.381cm;
        font-weight: bold;
        color: #1a0a00;
        white-space: nowrap;
    }

    /*
     * Klassen-Kästchen (Template: x 113–174 px, je 62 × 63 px)
     * → left 0,867 cm, 0,476 × 0,480 cm; top je Klasse siehe $classBoxes.
     */
    .card-class-x {
        position: absolute;
        left: 0.867cm;
        width: 0.476cm;
        height: 0.480cm;
        line-height: 0.400cm;
        text-align: center;
        font-size: 13pt;
        font-weight: bold;
        color: #1a0a00;
    }

    /* Fallback ohne Vorderseiten-Template */
    .card-fallback-header {
        background: #5a3a22;
        color: #f5e8d0;
        text-align: center;
        padding: 0.2cm 0.1cm;
        font-size: 8px;
        letter-spacing: 0.04cm;
    }

    .card-fallback-qr {
        position: absolute;
        left: 4.696cm;
        top: 7.477cm;
        width: 1.895cm;
        height: 1.677cm;
    }

    .card-fallback-qr img { width: 100%; height: 100%; }

    .card-fallback-siegel {
        position: absolute;
        left: 0.794cm;
        top: 8.003cm;
        width: 2.248cm;
        height: 0.808cm;
        overflow: hidden;
    }

    .card-fallback-siegel table {
        width: 100%;
        height: 100%;
        border-collapse: collapse;
    }

    .card-fallback-siegel td {
        text-align: center;
        vertical-align: middle;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 14pt;
        font-weight: bold;
        letter-spacing: 0.04cm;
        color: #2a1a0a;
        padding: 0;
    }

    /*
     * Rückseite: 180° gedrehtes Template (via GD vorverarbeitet, als DataURI).
     * Liegt direkt unter der Vorderseite; beim Klappen entlang der Mittellinie
     * kommt die Rückseite korrekt ausgerichtet hinter der Vorderseite zu liegen.
     */
    .card-back {
        width: 7.52cm;
        height: 10cm;
        overflow: hidden;
    }

    .card-back.has-back-template {
        background-image: url("{{ $backTemplateUri }}");
        background-size: 100% 100%;
        background-repeat: no-repeat;
    }

    .card-back.no-template {
        border: 1px solid #5a3a22;
        border-radius: 0.3cm;
        background: #f0ece4;
    }
</style>
</head>
<body>

@php
    $hasFront = file_exists(resource_path('images/template_helden_ausweis_vorderseite.png'));
    $hasBack  = $backTemplateUri !== null;
    $chunks   = collect($cards)->chunk(3);

    // Oberkante der aufgedruckten Klassen-Kästchen (y-Pixel / 1312 px × 10 cm).
    $classBoxes = [
        'Krieger'    => 3.651,
        'Magier'     => 4.360,
        'Waldläufer' => 5.069,
        'Heiler'     => 5.777,
        'Alchemist'  => 6.486,
    ];
@endphp

@foreach ($chunks as $chunkIndex => $chunk)
@if ($chunkIndex > 0)<div class="page-break"></div>@endif
@php
    /*
     * Vertikale Zentrierung: DOMPDF ignoriert @page margin, daher Spacer-Div.
     * A4 quer = 21 cm, Kombo = 20 cm → (21−20)/2 = 0,5 cm oben.
     *
     * Horizontale Zentrierung: DOMPDF ignoriert margin:auto auf Tabellen.
     * A4 quer = 29,7 cm. Tabellenbreite = n×7,52 cm + (n-1)×1 mm Abstand.
     */
    $n          = $chunk->count();
    $tableW     = $n * 7.52 + max(0, $n - 1) * 0.1;
    $marginLeft = round((29.7 - $tableW) / 2, 3);
@endphp
<div style="height: 0.5cm;"></div>
<table class="combo-table" style="margin-left: {{ $marginLeft }}cm;">
<tr>
    @foreach ($chunk as $card)
    @if (! $loop->first)<td class="gap-cell"></td>@endif
    <td class="combo-cell">
    <div class="card-combo">

        {{-- Vorderseite --}}
        <div class="card {{ $hasFront ? 'has-front-template' : 'no-template' }}">
            @if (! $hasFront)
                <div class="card-fallback-header">Heldenausweis – {{ $card['code'] }}</div>
            @endif

            {{-- Name (Charakter- oder Spielername); leer bei Blanko-Ausweisen --}}
            @if (filled($card['name'] ?? null))
                @php($nameLen = mb_strlen($card['name']))
                <div class="card-name" style="font-size: {{ $nameLen <= 16 ? 11 : ($nameLen <= 24 ? 9 : 7) }}pt;">{{ $card['name'] }}</div>
            @endif

            {{-- X in die Kästchen der Klassen des Helden --}}
            @foreach ($card['classes'] ?? [] as $className)
                @if (isset($classBoxes[$className]))
                    <div class="card-class-x" style="top: {{ $classBoxes[$className] }}cm;">X</div>
                @endif
            @endforeach

            @if ($hasFront)
                <div class="card-qr">
                    <img src="{{ $card['qr'] }}" alt="QR">
                </div>
                <div class="card-siegel">
                    <table><tr><td>{{ $card['code'] }}</td></tr></table>
                </div>
            @else
                <div class="card-fallback-qr">
                    <img src="{{ $card['qr'] }}" alt="QR">
                </div>
                <div class="card-fallback-siegel">
                    <table><tr><td>{{ $card['code'] }}</td></tr></table>
                </div>
            @endif
        </div>

        {{-- Rückseite: 180° gedreht, direkt darunter --}}
        <div class="card-back {{ $hasBack ? 'has-back-template' : 'no-template' }}"></div>

    </div>
    </td>
    @endforeach
</tr>
</table>
@endforeach

</body>
</html>
