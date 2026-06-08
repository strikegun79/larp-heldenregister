<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $adventure->name }}</h2>
            <a href="{{ route('adventures.edit', $adventure) }}"><x-secondary-button>Bearbeiten</x-secondary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded bg-green-100 dark:bg-green-900 px-4 py-2 text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded bg-red-100 dark:bg-red-900 px-4 py-2 text-red-800 dark:text-red-200">{{ session('error') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-800 dark:text-gray-200">
                <dl class="grid grid-cols-2 gap-4">
                    <div><dt class="text-sm text-gray-500">Beginn</dt><dd>{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Ende</dt><dd>{{ optional($adventure->end_at)->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Ort</dt><dd>{{ $adventure->location?->titel ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Status</dt><dd>{{ $adventure->status?->description }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Kategorie</dt><dd>{{ $adventure->category?->name }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Auftraggeber</dt><dd>{{ $adventure->client?->name }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Beitrag</dt><dd>{{ number_format($adventure->fee, 2, ',', '.') }} €</dd></div>
                    <div><dt class="text-sm text-gray-500">Belegung</dt><dd class="font-semibold">{{ $adventure->confirmedBookings()->count() }} / {{ $adventure->max_player }} ({{ $adventure->freeSlots() }} frei)</dd></div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-800 dark:text-gray-200">
                <h3 class="font-semibold mb-3">Anmeldungen</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-500">
                        <tr><th class="py-1">Spieler</th><th class="py-1">Rolle</th><th class="py-1">Liste</th><th class="py-1"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($adventure->bookings as $booking)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="py-1">{{ $booking->player?->full_name }}</td>
                                <td class="py-1">{{ $booking->role?->description }}</td>
                                <td class="py-1">{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                                <td class="py-1 text-right">
                                    <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                                          onsubmit="return confirm('Anmeldung stornieren?');">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">stornieren</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-2 text-gray-500">Noch keine Anmeldungen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-800 dark:text-gray-200">
                <h3 class="font-semibold mb-3">Anmelden</h3>
                @if (! $adventure->registrationOpen())
                    <p class="text-gray-500">Die Anmeldung ist für dieses Abenteuer derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
                @else
                    @if ($adventure->isFull())
                        <p class="mb-3 text-orange-600">Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.</p>
                    @endif
                    <form method="POST" action="{{ route('adventures.bookings.store', $adventure) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="player_id" value="Spieler" />
                                <select id="player_id" name="player_id" required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    <option value="">— wählen —</option>
                                    @foreach ($players as $player)
                                        <option value="{{ $player->id }}" @selected(old('player_id') == $player->id)>{{ $player->full_name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('player_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="event_role_id" value="Rolle" />
                                <select id="event_role_id" name="event_role_id" required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected(old('event_role_id') == $role->id)>{{ $role->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['fotoerlaubnis' => 'Fotoerlaubnis', 'vegetarier' => 'Vegetarier', 'leih_tunika' => 'Leih-Tunika', 'leih_waffe' => 'Leih-Waffe', 'nsc' => 'NSC'] as $field => $label)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-gray-300 text-indigo-600">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>

                        <div>
                            <x-input-label for="allergien" value="Allergien" />
                            <textarea id="allergien" name="allergien" rows="2"
                                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">{{ old('allergien') }}</textarea>
                        </div>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="agb" value="1" required class="rounded border-gray-300 text-indigo-600">
                            Ich akzeptiere die AGB
                        </label>
                        <x-input-error :messages="$errors->get('agb')" class="mt-1" />

                        <x-primary-button>Anmeldung absenden</x-primary-button>
                    </form>
                @endif
            </div>

            <a href="{{ route('adventures.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">&larr; Zurück zur Übersicht</a>
        </div>
    </div>
</x-app-layout>
