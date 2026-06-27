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
     * der Vorderseite. Keine Spaltenumkehrung nötig (waagerechte Klappackse
     * spiegelt nur vertikal, nicht horizontal).
     *
     * 1 Kombo pro Seite, zentriert auf A4 quer (29,7 × 21 cm).
     * Kombo: Vorderseite (10 cm) + Rückseite 180° gedreht (10 cm) = 20 cm Höhe < 21 cm ✓
     * Untereinander = eine Kombo je Seite, Seiten werden im PDF-Viewer untereinander angezeigt.
     */
    @page { margin: 0; size: A4 landscape; }

    .page-wrap {
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .page-break { page-break-before: always; }

    .card-combo {
        width: 7.52cm;
    }

    /*
     * Vorderseite: Template-Bild 7,52 cm × 10 cm.
     * Pixelgenaue Positionen aus dem 980 × 1312 px Template (sx=7.52/980, sy=10/1312):
     *   QR:     left 4,696 cm / top 7,477 cm / 1,895 × 1,677 cm
     *   Siegel: left 0,806 cm / top 8,025 cm / 2,248 × 0,808 cm
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

    .card-siegel {
        position: absolute;
        left: 0.806cm;
        top: 8.025cm;
        width: 2.248cm;
        height: 0.808cm;
        text-align: center;
        line-height: 0.808cm;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 9px;
        font-weight: bold;
        letter-spacing: 0.04cm;
        color: #1a0a00;
        overflow: hidden;
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
        left: 0.806cm;
        top: 8.025cm;
        width: 2.248cm;
        height: 0.808cm;
        text-align: center;
        line-height: 0.808cm;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 9px;
        font-weight: bold;
        letter-spacing: 0.04cm;
        color: #2a1a0a;
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
    $chunks   = collect($cards)->chunk(1);
@endphp

@foreach ($chunks as $chunkIndex => $chunk)
<div class="page-wrap{{ $chunkIndex > 0 ? ' page-break' : '' }}">
    @foreach ($chunk as $card)
    <div class="card-combo">

        {{-- Vorderseite --}}
        <div class="card {{ $hasFront ? 'has-front-template' : 'no-template' }}">
            @if (! $hasFront)
                <div class="card-fallback-header">Heldenausweis – {{ $card['code'] }}</div>
            @endif

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

        {{-- Rückseite: 180° gedreht, direkt darunter --}}
        <div class="card-back {{ $hasBack ? 'has-back-template' : 'no-template' }}"></div>

    </div>
    @endforeach
</div>
@endforeach

</body>
</html>
