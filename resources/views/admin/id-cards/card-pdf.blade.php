<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; }

    /*
     * PUB-12: Kein Seitenrand – Templates füllen die Fläche lückenlos.
     * Kachelformat: 3×2 auf A4 quer, 2px Abstand zwischen den Karten.
     * Kartenmaß 7,52 cm × 10 cm bleibt unverändert.
     */
    @page { margin: 0; size: A4 landscape; }

    .page-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
        align-content: flex-start;
    }

    .page-break { page-break-before: always; }

    /*
     * PUB-12: Karte = nur Template-Bild als Hintergrund, kein Border/Hintergrundfarbe.
     * Template-Maße: 980 × 1312 px → auf 7,52 cm × 10 cm skaliert.
     * Skalierung: sx = 7.52/980 = 0.007673 cm/px, sy = 10/1312 = 0.007622 cm/px
     */
    .card {
        width: 7.52cm;
        height: 10cm;
        position: relative;
        overflow: hidden;
        page-break-inside: avoid;
    }

    .card.has-front-template {
        background-image: url("{{ str_replace('\\', '/', resource_path('images/template_helden_ausweis_vorderseite.png')) }}");
        background-size: 100% 100%;
        background-repeat: no-repeat;
    }

    /* Fallback ohne Template: dezenter Rahmen */
    .card.no-template {
        border: 1px solid #5a3a22;
        border-radius: 0.3cm;
        background: #fdf8f0;
    }

    /*
     * QR-Code: Templateposition x:612 y:981, Größe 247×220 px
     * → left: 612×sx=4.696cm  top: 981×sy=7.477cm
     *   width: 247×sx=1.895cm  height: 220×sy=1.677cm
     */
    .card-qr {
        position: absolute;
        left: 4.696cm;
        top: 7.477cm;
        width: 1.895cm;
        height: 1.677cm;
    }

    .card-qr img {
        width: 100%;
        height: 100%;
    }

    /*
     * Helden-Siegel (Code-Text): Templateposition x:105 y:1053, Größe 293×106 px
     * → left: 105×sx=0.806cm  top: 1053×sy=8.025cm
     *   width: 293×sx=2.248cm  height: 106×sy=0.808cm
     */
    .card-siegel {
        position: absolute;
        left: 0.806cm;
        top: 8.025cm;
        width: 2.248cm;
        height: 0.808cm;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 9px;
        font-weight: bold;
        letter-spacing: 0.04cm;
        color: #1a0a00;
        overflow: hidden;
    }

    /* Fallback-Elemente wenn kein Template vorhanden */
    .card-fallback-qr {
        position: absolute;
        left: 4.696cm;
        top: 7.477cm;
        width: 1.895cm;
        height: 1.677cm;
    }

    .card-fallback-qr img { width: 100%; height: 100%; }

    .card-fallback-header {
        background: #5a3a22;
        color: #f5e8d0;
        text-align: center;
        padding: 0.2cm 0.1cm;
        font-size: 8px;
        letter-spacing: 0.04cm;
    }

    .card-fallback-siegel {
        position: absolute;
        left: 0.806cm;
        top: 8.025cm;
        width: 2.248cm;
        height: 0.808cm;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 9px;
        font-weight: bold;
        letter-spacing: 0.04cm;
        color: #2a1a0a;
    }

    /* Rückseite */
    .card-back {
        width: 7.52cm;
        height: 10cm;
        position: relative;
        overflow: hidden;
        page-break-inside: avoid;
    }

    .card-back.has-back-template {
        background-image: url("{{ str_replace('\\', '/', resource_path('images/template_helden_ausweis_rueckseite.png')) }}");
        background-size: 100% 100%;
        background-repeat: no-repeat;
    }

    .card-back.no-template {
        border: 1px solid #5a3a22;
        border-radius: 0.3cm;
        background: #fdf8f0;
    }
</style>
</head>
<body>

@php
    $hasFront = file_exists(resource_path('images/template_helden_ausweis_vorderseite.png'));
    $hasBack  = file_exists(resource_path('images/template_helden_ausweis_rueckseite.png'));

    // Rückseiten: Spalten spiegeln für Duplexdruck (je 3er-Gruppe umkehren)
    $backCards = collect($cards)
        ->chunk(3)
        ->map(fn ($chunk) => $chunk->reverse()->values())
        ->flatten(1)
        ->values()
        ->all();
@endphp

{{-- Seite 1: Vorderseiten --}}
<div class="page-wrap">
@foreach ($cards as $card)
<div class="card {{ $hasFront ? 'has-front-template' : 'no-template' }}">
    @if (! $hasFront)
        <div class="card-fallback-header">Heldenausweis – {{ $card['code'] }}</div>
    @endif

    {{-- QR-Code an Templateposition --}}
    @if ($hasFront)
        <div class="card-qr">
            <img src="{{ $card['qr'] }}" alt="QR">
        </div>
        <div class="card-siegel">{{ $card['code'] }}</div>
    @else
        <div class="card-fallback-qr">
            <img src="{{ $card['qr'] }}" alt="QR">
        </div>
        <div class="card-fallback-siegel">{{ $card['code'] }}</div>
    @endif
</div>
@endforeach
</div>

{{-- Seite 2: Rückseiten (Spalten gespiegelt für Duplexdruck) --}}
<div class="page-wrap page-break">
@foreach ($backCards as $card)
<div class="card-back {{ $hasBack ? 'has-back-template' : 'no-template' }}"></div>
@endforeach
</div>

</body>
</html>
