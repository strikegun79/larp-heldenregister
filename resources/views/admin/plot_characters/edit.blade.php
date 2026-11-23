<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Plot-Charakter bearbeiten</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
            @include('admin.plot_characters._form')
            <button type="submit" form="plot-character-form" class="ui primary button mt-4">Speichern</button>
            <a href="{{ route('admin.plot-characters.index') }}" class="inline-block mt-4 ml-2 text-sm text-stone-600 hover:underline">&larr; Zurück</a>
        </div>
    </div>
</x-app-layout>
