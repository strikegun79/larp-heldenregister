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

        <!-- Fomantic UI (wie im Legacy) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">

        <!-- Scripts (Tailwind/Breeze danach, damit das Theme gewinnt) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

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
            <main class="flex-1">
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

        <!-- Gemeinsames Fomantic-Modal: Inhalt wird per AJAX aus [data-modal-url] geladen -->
        <div class="ui modal" id="app-modal">
            <i class="close icon"></i>
            <div class="scrolling content" id="app-modal-content"></div>
        </div>

        <!-- jQuery + Fomantic JS -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
        <script>
            // Klick auf ein Element mit data-modal-url -> Inhalt per AJAX ins Modal laden.
            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-modal-url]');
                if (!trigger) return;
                e.preventDefault();
                const url = trigger.getAttribute('data-modal-url');
                const $content = $('#app-modal-content');
                $content.html('<div class="ui active centered inline loader" style="display:block;margin:2rem auto"></div>');
                $('#app-modal').modal({ autofocus: false, observeChanges: true }).modal('show');
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => { $content.html(html); $('#app-modal').modal('refresh'); })
                    .catch(() => $content.html('<div class="ui error message">Konnte nicht geladen werden.</div>'));
            });
        </script>
    </body>
</html>
