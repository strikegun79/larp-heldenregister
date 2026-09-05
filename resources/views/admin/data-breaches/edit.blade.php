<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.data-breaches.show', $log) }}" class="ui button">← Zurück</a>
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Vorfall bearbeiten</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow rounded-lg p-6">
                <form method="POST" action="{{ route('admin.data-breaches.update', $log) }}">
                    @csrf @method('PUT')
                    @include('admin.data-breaches._form')
                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="ui primary button">Speichern</button>
                        <a href="{{ route('admin.data-breaches.show', $log) }}" class="ui button">Abbrechen</a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
