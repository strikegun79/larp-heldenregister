<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Profil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Deine Rollen') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">Diese Rechte sind deinem Konto zugewiesen. Vergeben werden sie in der Verwaltung.</p>
                    <div class="mt-4">
                        @forelse (auth()->user()->roles as $role)
                            <span class="ui label">{{ $role->label }}</span>
                        @empty
                            <span class="text-gray-500">Keine Rolle zugewiesen.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if (auth()->user()->hasAnyRole('teamer', 'lehrmeister'))
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <h2 class="text-lg font-medium text-gray-900">Teamer-Benachrichtigungen</h2>
                        <p class="mt-1 text-sm text-gray-600">Erhalte eine Benachrichtigung, wenn du als Teamer zu einem Abenteuer eingeladen wirst.</p>
                        <form method="POST" action="{{ route('profile.update') }}" class="mt-4">
                            @csrf @method('PATCH')
                            {{-- Pflichtfelder des Profils mitschicken --}}
                            <input type="hidden" name="name" value="{{ auth()->user()->name }}">
                            <input type="hidden" name="lastname" value="{{ auth()->user()->lastname }}">
                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                            <input type="hidden" name="phone" value="{{ auth()->user()->phone }}">
                            <label class="flex items-center gap-3 mt-2">
                                <input type="checkbox" name="teamer_notifications" value="1"
                                       @checked(auth()->user()->teamer_notifications ?? true)>
                                <span class="text-sm text-gray-700">Teamer-Einladungen per E-Mail und im Portal erhalten</span>
                            </label>
                            <button type="submit" class="ui primary button mt-4">Einstellung speichern</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
