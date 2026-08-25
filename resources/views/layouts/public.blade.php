<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Heldenregister') }}</title>

        <link rel="icon" href="{{ config('portal.favicon') }}">

        <!-- Fonts: lokal via Fontsource (kein Google CDN, DSGVO-konform) -->

        <link rel="stylesheet" href="{{ asset('css/heldenregister.css') }}">
        @vite(['resources/css/vendor.css', 'resources/css/app.css', 'resources/js/app.js'])

        <style>body { font-family: 'EB Garamond', serif; }</style>
    </head>
    <body class="antialiased text-stone-800 bg-parchment min-h-screen flex flex-col">

        {{-- Minimale öffentliche Navigation --}}
        <nav class="bg-[#e4cea5]/80 backdrop-blur border-b-2 border-[#5a3a22]/50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
                <a href="/">
                    <x-application-logo class="block h-12 w-auto" />
                </a>
                <a href="{{ route('login') }}"
                   class="text-sm text-waldritter hover:underline font-medium">
                    Anmelden
                </a>
            </div>
        </nav>

        <main class="flex-1 py-10">
            {{ $slot }}
        </main>

        <footer class="border-t-2 border-[#5a3a22]/40 bg-black/10 text-waldritter">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid gap-6 sm:grid-cols-3 text-sm">
                <div>
                    <strong>{{ config('portal.organization') }} – {{ config('portal.name') }}</strong><br>
                    @if(config('portal.email'))
                    <a class="hover:underline" href="mailto:{{ config('portal.email') }}">{{ config('portal.email') }}</a>
                    @endif
                </div>
                <div></div>
                <div class="sm:text-right">
                    <a href="{{ route('datenschutz') }}" class="hover:underline text-waldritter">Datenschutzerklärung</a><br>
                    &copy; {{ date('Y') }} {{ config('portal.organization') }}
                </div>
            </div>
        </footer>
        @stack('scripts')
    </body>
</html>
