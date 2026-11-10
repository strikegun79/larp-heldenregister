<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Abenteuer bearbeiten: {{ $adventure->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('adventures.update', $adventure) }}">
                    @method('PUT')
                    @include('adventures._form')
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('adventures.destroy', $adventure) }}"
                      data-confirm="Dieses Abenteuer wirklich löschen?">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Abenteuer löschen</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
