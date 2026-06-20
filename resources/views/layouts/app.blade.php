<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Heldenregister') }}</title>

        <link rel="icon" href="/favicon.ico">

        <!-- Fonts: Body + mittelalterliche Überschriften (wie im Legacy) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Uncial+Antiqua&family=MedievalSharp&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/heldenregister.css') }}">
        <!-- Vendor: Fomantic UI + Cropper.js (lokal via Vite/npm, UI-10) -->
        @vite(['resources/css/vendor.css', 'resources/js/vendor.js'])
        <!-- Scripts (Tailwind/Breeze danach, damit das Theme gewinnt) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- Heldenregister-Frontend-Logik (ARCH-001: aus Inline-Scripts ausgelagert) -->
        @vite(['resources/js/heldenregister.js'])

        <style>body { font-family: 'EB Garamond', serif; }</style>
    </head>
    <body class="antialiased text-stone-800">
        <div class="min-h-screen flex flex-col bg-parchment">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white/50 border-b-2 border-[#5a3a22]/40 shadow-sm">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-1" id="page-main">
                {{ $slot }}
            </main>

            <!-- Footer (Vereinskontakt, wie im Legacy) -->
            <footer class="mt-8 border-t-2 border-[#5a3a22]/40 bg-black/10 text-waldritter">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid gap-6 sm:grid-cols-3 text-sm">
                    <div>
                        <strong>Waldritter-Gießen e.V. – Heldenregister</strong><br>
                        Entwicklung &amp; Support<br>
                        <a class="hover:underline" href="mailto:richy.mueller@waldritter-giessen.de">richy.mueller@waldritter-giessen.de</a>
                    </div>
                    <div>
                        <strong>Vereinskontakt</strong><br>
                        <a class="hover:underline" href="mailto:info@waldritter-giessen.de">info@waldritter-giessen.de</a>
                    </div>
                    <div class="sm:text-right">
                        &copy; {{ date('Y') }} Waldritter-Gießen e.V.
                    </div>
                </div>
            </footer>
        </div>

        <!-- Gemeinsames Fomantic-Modal: Header + scrollender Inhalt + Footer.
             Inhalt wird per AJAX aus [data-modal-url] geladen; das Partial liefert
             [data-modal-title] (Header) und optional [data-modal-actions] (Footer). -->
        <div class="ui modal" id="app-modal"
             role="dialog" aria-modal="true" aria-labelledby="app-modal-header">
            <button type="button" class="close icon" aria-label="Schließen"></button>
            <div class="header" id="app-modal-header"></div>
            <div class="scrolling content" id="app-modal-content"></div>
            <div class="actions" id="app-modal-actions"></div>
        </div>

        <!-- Gestapeltes Modal (ADV-22): öffnet über #app-modal (z. B. Anmeldung,
             Gast-Anmeldung, Anmeldung bearbeiten). Schließ-Icon oben rechts (UI-25)
             ist immer erreichbar, unabhängig von der Scroll-Position des Inhalts. -->
        <div class="ui modal" id="app-modal-2"
             role="dialog" aria-modal="true" aria-labelledby="app-modal-2-header">
            <button type="button" class="close icon" aria-label="Schließen"></button>
            <div class="header" id="app-modal-2-header"></div>
            <div class="scrolling content" id="app-modal-2-content"></div>
            <div class="actions" id="app-modal-2-actions"></div>
        </div>

        <!-- Foto-Crop-Modal: Helden-Foto zuschneiden (HERO-22) -->
        <div class="ui modal" id="photo-crop-modal"
             role="dialog" aria-modal="true" aria-labelledby="photo-crop-modal-header">
            <div class="header" id="photo-crop-modal-header">Foto zuschneiden</div>
            <div class="scrolling content">
                <div style="max-height:400px; overflow:hidden; background:#111; border-radius:.4rem;">
                    <img id="photo-crop-img" src="" alt="Zuschnitt" style="display:block; max-width:100%;">
                </div>
                <p class="text-sm text-stone-700 mt-2">Rahmen verschieben und anpassen, dann „Übernehmen" klicken.</p>
            </div>
            <div class="actions">
                <button type="button" class="ui deny button">Abbrechen</button>
                <button type="button" id="photo-crop-save-btn" class="ui primary button">
                    <i class="check icon"></i> Übernehmen
                </button>
            </div>
        </div>

        <!-- Bestätigungs-Modal: Fertigkeit erlernen/aberkennen (HERO-14/16) -->
        <div class="ui small modal" id="skill-modal"
             role="dialog" aria-modal="true" aria-labelledby="skill-modal-title">
            <div class="header" id="skill-modal-title"></div>
            <div class="content">
                <p id="skill-modal-desc" class="text-stone-700"></p>
                <p id="skill-modal-meta"></p>
                <p id="skill-modal-warn" class="text-red-700 font-medium" style="display:none">Nicht genug EP. EP werden durch Abenteuer-Teilnahme gutgeschrieben.</p>
            </div>
            <div class="actions">
                <button type="button" class="ui deny button">Schließen</button>
                <button type="button" class="ui positive button" id="skill-modal-accept">Fertigkeit erlernen</button>
                <button type="button" class="ui negative button" id="skill-modal-revoke">Zurücknehmen</button>
            </div>
        </div>

        <!-- Unterschrift + Check-in als Multimodal (ADV-19) -->
        <div class="ui modal" id="signature-modal"
             role="dialog" aria-modal="true" aria-labelledby="signature-modal-header">
            <div class="header" id="signature-modal-header">Unterschrift &amp; Check-in</div>
            <div class="content">
                <p id="signature-modal-name" class="font-semibold"></p>
                <p class="text-sm text-stone-700">Mit Tablet &amp; Stift im Feld unterschreiben.</p>
                <canvas id="signature-pad" width="600" height="240" aria-label="Unterschriftenfeld"
                        style="border:2px solid #5a3a22; border-radius:.4rem; touch-action:none; background:#fff; max-width:100%; width:600px; height:240px;"></canvas>
            </div>
            <div class="actions">
                <button type="button" class="ui deny button">Abbrechen</button>
                <button type="button" class="ui button" onclick="clearSignaturePad()">Löschen</button>
                <button type="button" class="ui primary button" id="signature-modal-save">Check-in bestätigen</button>
            </div>
        </div>

        <!-- Abmeldung mit Grund als Multimodal (ADV-19) -->
        <div class="ui small modal" id="deregister-modal"
             role="dialog" aria-modal="true" aria-labelledby="deregister-modal-header">
            <div class="header" id="deregister-modal-header">Teilnehmer abmelden</div>
            <div class="content">
                <p id="deregister-modal-name" class="font-semibold"></p>
                <div class="ui form">
                    <div class="field">
                        <label for="deregister-reason">Grund der Abmeldung</label>
                        <select id="deregister-reason">
                            <option value="">— wählen —</option>
                            @foreach (\App\Models\Booking::ABSENCE_REASONS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions">
                <button type="button" class="ui deny button">Abbrechen</button>
                <button type="button" class="ui primary button" id="deregister-modal-save">Abmelden</button>
            </div>
        </div>

        <!-- Bestätigungs-Modal (UI-17) -->
        <div class="ui small modal" id="confirm-modal"
             role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-header">
            <div class="header" id="confirm-modal-header">Bitte bestätigen</div>
            <div class="content">
                <p id="confirm-modal-message" class="text-stone-700"></p>
            </div>
            <div class="actions">
                <button type="button" class="ui deny button"><i class="times icon"></i> Abbrechen</button>
                <button type="button" class="ui negative button" id="confirm-modal-ok">
                    <i class="check icon"></i> Bestätigen
                </button>
            </div>
        </div>

        {{-- UI-09: Session-Flash-Daten für heldenregister.js (ARCH-001).
             Profil-spezifische Status-Werte werden übersprungen. --}}
        @php
            $flashStatus   = session('status');
            $flashError    = session('error');
            $flashReserved = ['profile-updated', 'verification-link-sent', 'password-updated'];
        @endphp
        <div id="app-flash" hidden
             data-status="{{ ($flashStatus && ! in_array($flashStatus, $flashReserved)) ? $flashStatus : '' }}"
             data-error="{{ $flashError ?? '' }}"></div>


        @stack('scripts')
    </body>
</html>
