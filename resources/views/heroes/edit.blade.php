<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Held bearbeiten: {{ $hero->character_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('heroes.update', $hero) }}">
                    @method('PUT')
                    @include('heroes._form')
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('heroes.destroy', $hero) }}"
                      onsubmit="return confirm('Diesen Helden wirklich löschen?');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Helden löschen</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
