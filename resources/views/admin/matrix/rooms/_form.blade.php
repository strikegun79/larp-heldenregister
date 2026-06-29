<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            {{ $room->exists ? 'Raum bearbeiten' : 'Raum anlegen' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="ui error message mb-4">
                        <ul class="list">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $action }}" class="space-y-5">
                    @csrf
                    @method($method)

                    {{-- Room-ID: nur bei Neuanlage editierbar --}}
                    <div>
                        <x-input-label for="roomid" value="Matrix-Room-ID" />
                        @if ($room->exists)
                            <p class="font-mono text-stone-700 mt-1">{{ $room->roomid }}</p>
                            <p class="text-xs text-stone-400 mt-0.5">Die Room-ID kann nach der Anlage nicht geändert werden.</p>
                        @else
                            <x-text-input id="roomid" name="roomid" type="text" class="mt-1 block w-full"
                                          :value="old('roomid')"
                                          placeholder="!abc123:waldritter-giessen.de"
                                          required />
                            <p class="text-xs text-stone-400 mt-0.5">Matrix-Room-ID aus dem Corporal-Dashboard (beginnt mit !).</p>
                            <x-input-error :messages="$errors->get('roomid')" class="mt-1" />
                        @endif
                    </div>

                    <div>
                        <x-input-label for="roomname" value="Anzeigename" />
                        <x-text-input id="roomname" name="roomname" type="text" class="mt-1 block w-full"
                                      :value="old('roomname', $room->roomname)"
                                      placeholder="z. B. Waldritter Allgemein"
                                      required maxlength="50" />
                        <x-input-error :messages="$errors->get('roomname')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="roomtype" value="Typ" />
                        <select id="roomtype" name="roomtype"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-600 focus:border-amber-600">
                            <option value="Raum"  @selected(old('roomtype', $room->roomtype) === 'Raum')>Raum</option>
                            <option value="Space" @selected(old('roomtype', $room->roomtype) === 'Space')>Space</option>
                        </select>
                        <x-input-error :messages="$errors->get('roomtype')" class="mt-1" />
                    </div>

                    <fieldset class="space-y-2">
                        <legend class="block font-medium text-stone-700 text-sm">Standard-Verhalten</legend>
                        <label class="flex items-center gap-2 text-stone-700 text-sm">
                            <input type="checkbox" name="default_allow" value="1"
                                   class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                                   @checked(old('default_allow', $room->default_allow))>
                            <span>Default Allow — neue Konten werden automatisch Mitglied</span>
                        </label>
                        <label class="flex items-center gap-2 text-stone-700 text-sm">
                            <input type="checkbox" name="default_deny" value="1"
                                   class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                                   @checked(old('default_deny', $room->default_deny))>
                            <span>Default Deny — neue Konten werden explizit ausgeschlossen</span>
                        </label>
                    </fieldset>

                    <div class="flex items-center gap-4 pt-2">
                        <x-primary-button>
                            {{ $room->exists ? 'Speichern' : 'Anlegen' }}
                        </x-primary-button>
                        <a href="{{ route('admin.matrix.rooms.index') }}" class="text-sm text-stone-600 hover:underline">
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
