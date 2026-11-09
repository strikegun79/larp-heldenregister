<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            {{ $group->exists ? 'Gruppe bearbeiten' : 'Neue Gruppe' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                @include('admin.groups._form')
                <div class="mt-4">
                    <button type="submit" form="group-form" class="ui primary button">Speichern</button>
                    <a href="{{ route('admin.groups.index') }}" class="ml-3 text-sm text-stone-600 hover:underline">Abbrechen</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
