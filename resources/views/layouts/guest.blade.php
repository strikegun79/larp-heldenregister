<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Heldenregister') }}</title>

        <link rel="icon" href="{{ config('portal.favicon') }}">

        <!-- Fonts: lokal via Fontsource (kein Google CDN, DSGVO-konform) -->

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>body { font-family: 'EB Garamond', serif; }</style>
    </head>
    <body class="text-stone-800 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-parchment">
            <div>
                <a href="/">
                    <x-application-logo class="h-24 w-auto" />
                </a>
            </div>

            @stack('before-card')

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/70 border-2 border-[#5a3a22]/40 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
