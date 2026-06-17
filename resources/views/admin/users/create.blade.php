<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Nutzer einladen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                @include('admin.users._create_form')
                <div class="mt-6 flex gap-3">
                    <button type="submit" form="user-create-form" class="ui primary button">Einladen</button>
                    <a href="{{ route('admin.users.index') }}" class="ui button">Abbrechen</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
