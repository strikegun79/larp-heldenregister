<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $adventure->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                @include('adventures._detail')
            </div>

            <div class="mt-4">
                <a href="{{ route('adventures.index') }}" class="ui button">&larr; Zurück zur Übersicht</a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(function () { $('.menu .item[data-tab]').tab(); });
    </script>
    @endpush
</x-app-layout>
