<x-app-layout>
    @php
        $stunde = (int) now()->format('H');
        $gruss = $stunde >= 6 && $stunde < 10 ? 'Guten Morgen' : ($stunde >= 10 && $stunde < 18 ? 'Guten Tag' : 'Guten Abend');
    @endphp
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            {{ $gruss }}, <em>{{ Auth::user()->name }}</em>
        </h2>
    </x-slot>



    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-6 mb-8 text-stone-800">

                <p class="mt-2">Willkommen im Heldenregister. Hier findest du alles rund um deine Spieler, Helden und Abenteuer.</p>
            </div>

            {{-- Admin-Kennzahlen (REP-06) --}}
            @if (! empty($metrics))
                <div class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-8">
                    @foreach ([
                        ['Spieler', $metrics['players']],
                        ['Helden', $metrics['heroes']],
                        ['Kommende Events', $metrics['upcoming_events']],
                        ['Offene Anmeldungen', $metrics['open_bookings']],
                    ] as [$label, $value])
                        <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 text-center">
                            <div class="text-3xl font-semibold text-waldritter">{{ $value }}</div>
                            <div class="text-sm text-stone-600">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Dein Profil --}}
                <a href="{{ route('profile.edit') }}"
                   class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                    <div class="h-44 overflow-hidden">
                        <img src="/images/dein_profil.jpg" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                    </div>
                    <div class="p-4 text-center">
                        <div class="font-uncial text-lg text-waldritter">Dein Profil</div>
                        <div class="text-sm text-stone-600">Persönlich</div>
                    </div>
                </a>

                {{-- Heldenregister (Registrar, Spielleiter, Teamer / Admin) --}}
                @can('heldenregister.view')
                    <a href="{{ route('heroes.index') }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="h-44 overflow-hidden">
                            <img src="/images/heroes_db.jpg" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">Heldenregister</div>
                            <div class="text-sm text-stone-600">Auflistung aller Helden</div>
                        </div>
                    </a>
                @endcan

                {{-- Abenteuer (Spielleiter, Teamer, Event buchen / Admin) --}}
                @can('adventure.access')
                    <a href="{{ route('adventures.index') }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="h-44 overflow-hidden">
                            <img src="/images/abenteuer_v2.jpg" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">Abenteuer</div>
                            <div class="text-sm text-stone-600">Veranstaltungen</div>
                        </div>
                    </a>
                @endcan

                {{-- Deine Spieler --}}
                <a href="{{ route('players.index') }}"
                   class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                    <div class="h-44 overflow-hidden">
                        <img src="/images/heldenarchiv.jpg" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                    </div>
                    <div class="p-4 text-center">
                        <div class="font-uncial text-lg text-waldritter">Deine Spieler</div>
                        <div class="text-sm text-stone-600">Spielerdatenbank</div>
                    </div>
                </a>

                {{-- Verwaltung (nur Admins) --}}
                @can('portal.manage')
                    <a href="{{ route('admin.index') }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="h-44 overflow-hidden">
                            <img src="/images/administration.jpg" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">Verwaltung</div>
                            <div class="text-sm text-stone-600">Portal-Administration</div>
                        </div>
                    </a>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
