<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Portal-Einstellungen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.settings.update') }}" class="ui form space-y-5">
                    @csrf
                    @method('PUT')

                    @foreach ($fields as $key => $field)
                        <div class="field">
                            <label for="setting_{{ $key }}">{{ $field['label'] }}</label>
                            <input type="{{ $key === 'contact_email' ? 'email' : 'text' }}"
                                   id="setting_{{ $key }}"
                                   name="{{ $key }}"
                                   value="{{ old($key, $values[$key] ?? '') }}"
                                   maxlength="{{ $key === 'contact_email' ? 255 : 100 }}">
                            <x-input-error :messages="$errors->get($key)" class="mt-1" />
                        </div>
                    @endforeach

                    <div class="pt-2">
                        <x-primary-button>Speichern</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="mt-4">
                <a href="{{ route('admin.index') }}">
                    <x-primary-button>Zurück zur Verwaltung</x-primary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
