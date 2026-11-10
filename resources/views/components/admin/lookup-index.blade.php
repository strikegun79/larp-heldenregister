@props([
    'title',
    'createRoute',
    'createLabel' => 'Neu',
    'backUrl'     => null,
])
{{--
    Gemeinsamer Seiten-Rahmen für einfache Lookup-Verwaltungsseiten (ADM-05).
    Slot: <table>-Inhalt (thead + tbody) des jeweiligen Lookups.

    Verwendung:
        <x-admin.lookup-index
            title="Teilnahme-Rollen"
            :create-route="route('admin.event-roles.create')"
            create-label="Neue Rolle">
            <table>...</table>
        </x-admin.lookup-index>
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $title }}</h2>
            <a href="{{ $createRoute }}"
               data-modal-url="{{ $createRoute }}"
               class="ui primary button">{{ $createLabel }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                    {{ $slot }}
                </x-mobile.cards-or-table>
            </div>
            <br>
            <a href="{{ $backUrl ?? route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
