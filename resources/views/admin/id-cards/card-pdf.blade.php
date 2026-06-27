<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #1a1a1a; }

    /*
     * PUB-10: Karte 7,52 cm × 10 cm, 3×2 Raster auf A4 quer (29,7 × 21 cm).
     * Seitenränder je 0,5 cm → nutzbarer Bereich 28,7 × 20 cm.
     * 3 Spalten × 7,52 cm = 22,56 cm; Abstand je 0,74 cm.
     * 2 Reihen × 10 cm = 20 cm (passt mit 0 cm Abstand in den nutzbaren Bereich).
     */
    @page { margin: 0.5cm; size: A4 landscape; }

    .page-wrap { display: flex; flex-wrap: wrap; gap: 0.74cm; align-content: flex-start; }

    .page-break { page-break-before: always; }

    .card {
        width: 7.52cm;
        height: 10cm;
        border: 1px solid #5a3a22;
        border-radius: 0.35cm;
        overflow: hidden;
        position: relative;
        background: #fdf8f0;
        page-break-inside: avoid;
    }

    /* Vorderseiten-Template als Hintergrundbild */
    .card.has-front-template {
        background-image: url("{{ str_replace('\\', '/', resource_path('images/template_helden_ausweis_vorderseite.png')) }}");
        background-size: cover;
        background-position: center;
    }

    /* Rückseite: Template füllt die ganze Karte */
    .card-back {
        width: 7.52cm;
        height: 10cm;
        border: 1px solid #5a3a22;
        border-radius: 0.35cm;
        overflow: hidden;
        page-break-inside: avoid;
        background: #fdf8f0;
    }

    .card-back.has-back-template {
        background-image: url("{{ str_replace('\\', '/', resource_path('images/template_helden_ausweis_rueckseite.png')) }}");
        background-size: cover;
        background-position: center;
    }

    .card-header {
        background: #5a3a22;
        color: #f5e8d0;
        text-align: center;
        padding: 0.2cm 0.15cm 0.15cm;
        font-size: 8px;
        letter-spacing: 0.05cm;
        text-transform: uppercase;
    }

    .card-header .org {
        font-size: 6px;
        opacity: 0.85;
        display: block;
        margin-bottom: 0.05cm;
    }

    .card-header .title {
        font-size: 10px;
        font-weight: bold;
    }

    .card-body {
        padding: 0.25cm 0.3cm;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: calc(10cm - 1.1cm - 1.1cm);
    }

    .qr-block {
        margin: 0.2cm 0 0.15cm;
    }

    .qr-block img {
        width: 3.2cm;
        height: 3.2cm;
    }

    .code-label {
        font-size: 7px;
        color: #5a3a22;
        letter-spacing: 0.03cm;
        margin-bottom: 0.05cm;
        text-transform: uppercase;
    }

    .code-value {
        font-size: 16px;
        font-weight: bold;
        letter-spacing: 0.18cm;
        color: #2a1a0a;
        font-family: "DejaVu Sans Mono", monospace;
        margin-bottom: 0.2cm;
    }

    .character-name {
        font-size: 8.5px;
        color: #3a200e;
        font-style: italic;
        text-align: center;
        max-width: 6.8cm;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .url-hint {
        font-size: 6px;
        color: #888;
        margin-top: 0.1cm;
        word-break: break-all;
        text-align: center;
        max-width: 6.8cm;
    }

    .card-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: #5a3a22;
        color: #f5e8d0;
        text-align: center;
        padding: 0.15cm 0;
        font-size: 6.5px;
        letter-spacing: 0.03cm;
    }

    .separator {
        border: none;
        border-top: 1px dashed #ccc;
        margin: 0.35cm 0 0;
        width: 100%;
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
<div class="card{{ $hasFront ? ' has-front-template' : '' }}">
    <div class="card-header">
        <span class="org">Waldritter-Gießen e.V.</span>
        <span class="title">Heldenausweis</span>
    </div>
    <div class="card-body">
        <div class="qr-block">
            <img src="{{ $card['qr'] }}" alt="QR-Code">
        </div>
        <div class="code-label">Helden-Code</div>
        <div class="code-value">{{ $card['code'] }}</div>
        @if (!empty($card['character_name']))
            <div class="character-name">{{ $card['character_name'] }}</div>
        @endif
        <hr class="separator">
        <div class="url-hint">{{ url('/h/'.$card['code']) }}</div>
    </div>
    <div class="card-footer">heldenregister.waldritter-giessen.de</div>
</div>
@endforeach
</div>

{{-- Seite 2: Rückseiten (Spalten gespiegelt für Duplexdruck) --}}
<div class="page-wrap page-break">
@foreach ($backCards as $card)
<div class="card-back{{ $hasBack ? ' has-back-template' : '' }}"></div>
@endforeach
</div>

</body>
</html>
