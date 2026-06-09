<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $adventure->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded bg-red-100 px-4 py-2 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                @include('adventures._detail')
            </div>

            <a href="{{ route('adventures.index') }}" class="inline-block mt-4 text-sm text-stone-600 hover:underline">&larr; Zurück zur Übersicht</a>
        </div>
    </div>
</x-app-layout>
