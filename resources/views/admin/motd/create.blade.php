<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Neue Startseiten-Nachricht</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.motd.store') }}" enctype="multipart/form-data" class="ui form space-y-5">
                    @csrf

                    @include('admin.motd._form')

                    <div class="pt-2">
                        <x-primary-button>Anlegen</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="mt-4">
                <a href="{{ route('admin.motd.index') }}">
                    <x-primary-button>Zurück zur Übersicht</x-primary-button>
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.ui.checkbox').checkbox();
            $('.ui.dropdown').dropdown();
        });
    </script>
    @endpush
</x-app-layout>
