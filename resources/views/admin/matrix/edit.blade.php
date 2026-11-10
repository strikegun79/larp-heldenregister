<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            Matrix-Konto: {{ $player->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <p class="text-sm text-stone-600 mb-1">Matrix-User-ID</p>
                <p class="font-mono text-stone-800 mb-4">{{ $mxid }}</p>
                @unless ($account)
                    <p class="text-sm text-stone-500 mb-4">Noch kein Konto – wird beim Speichern mit aktivem Zugang angelegt.</p>
                @endunless

                <form method="POST" action="{{ route('admin.players.matrix.update', $player) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <label class="flex items-center gap-2 text-stone-700">
                        <input type="checkbox" name="active" value="1"
                               class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                               @checked(old('active', $account?->active))>
                        Matrix-Zugang aktiv
                    </label>

                    <label class="flex items-center gap-2 text-stone-700">
                        <input type="checkbox" name="forbid_room_creation" value="1"
                               class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                               @checked(old('forbid_room_creation', $account?->forbid_room_creation ?? true))>
                        Raumerstellung verbieten
                    </label>

                    <div>
                        <x-input-label for="auth_credential" value="Passwort" />
                        <x-text-input id="auth_credential" name="auth_credential" type="text" class="mt-1 block w-full"
                                      :value="old('auth_credential', $account?->auth_credential)"
                                      placeholder="{{ $account ? 'unverändert lassen' : 'Passwort setzen' }}" />
                        <p class="text-xs text-stone-500 mt-1">Klartext (matrix-corporal nutzt authType „plain“).</p>
                    </div>

                    <div>
                        <span class="block font-medium text-stone-700 mb-2">Räume</span>
                        @unless ($account)
                            <p class="text-xs text-stone-400 mb-2">
                                Vorauswahl: Räume mit "Standard: allow". Anpassung vor dem Speichern möglich.
                            </p>
                        @endunless
                        @if ($rooms->isEmpty())
                            <p class="text-sm text-stone-500">Keine verwalteten Räume vorhanden.</p>
                        @else
                            <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto">
                                @foreach ($rooms as $room)
                                    <label class="flex items-center gap-2 text-stone-700 text-sm">
                                        <input type="checkbox" name="rooms[]" value="{{ $room->roomid }}"
                                               class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                                               @checked(in_array($room->roomid, old('rooms', $joined)))>
                                        {{ $room->roomname }}
                                        <span class="text-stone-400">({{ $room->roomtype }})</span>
                                        @if ($room->default_allow)
                                            <span class="ui green tiny label">Standard</span>
                                        @elseif ($room->default_deny)
                                            <span class="ui red tiny label">Gesperrt</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Speichern</x-primary-button>
                        <a href="{{ route('admin.players.index') }}" class="text-sm text-stone-600 hover:underline">Zurück</a>
                    </div>
                </form>
            </div>

            @if ($account && ! $account->trashed())
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                    <form method="POST" action="{{ route('admin.players.matrix.destroy', $player) }}"
                          data-confirm="Matrix-Zugang wirklich entziehen?">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Matrix-Zugang entziehen</x-danger-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
