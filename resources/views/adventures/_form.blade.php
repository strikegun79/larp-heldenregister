@csrf
@php
    $selectClass = 'mt-1 block w-full border-gray-300 focus:border-amber-600 focus:ring-amber-600 rounded-md shadow-sm';
@endphp
<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $adventure->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="function_email" value="Funktions-E-Mail (Event-Kontakt)" />
        <x-text-input id="function_email" name="function_email" type="email" class="mt-1 block w-full"
                      :value="old('function_email', $adventure->function_email)" />
        <x-input-error :messages="$errors->get('function_email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="location_id" value="Ort" />
        <select id="location_id" name="location_id" class="{{ $selectClass }}">
            <option value="">— ohne Ort —</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected(old('location_id', $adventure->location_id) == $location->id)>
                    {{ $location->titel }}@if($location->city) ({{ $location->city }})@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('location_id')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="start_at" value="Beginn" />
            <x-date-picker name="start_at" type="datetime"
                           :value="old('start_at', optional($adventure->start_at)->format('Y-m-d\TH:i'))"
                           :required="true" />
            <x-input-error :messages="$errors->get('start_at')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="end_at" value="Ende" />
            <x-date-picker name="end_at" type="datetime"
                           :value="old('end_at', optional($adventure->end_at)->format('Y-m-d\TH:i'))"
                           :required="true" />
            <x-input-error :messages="$errors->get('end_at')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <x-input-label for="event_status_id" value="Status" />
            {{-- Geführter Workflow (ADV-05): nur erlaubte Folgestatus anbieten. --}}
            @php($allowedStatusIds = $allowedStatusIds ?? null)
            <select id="event_status_id" name="event_status_id" class="{{ $selectClass }}">
                @foreach ($statuses as $status)
                    @if (is_null($allowedStatusIds) || in_array($status->id, $allowedStatusIds))
                        <option value="{{ $status->id }}" @selected(old('event_status_id', $adventure->event_status_id) == $status->id)>
                            {{ $status->description }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="event_category_id" value="Kategorie" />
            <select id="event_category_id" name="event_category_id" class="{{ $selectClass }}">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('event_category_id', $adventure->event_category_id) == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="event_client_id" value="Auftraggeber" />
            <select id="event_client_id" name="event_client_id" class="{{ $selectClass }}">
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('event_client_id', $adventure->event_client_id) == $client->id)>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Sichtbarkeit: manuelles Ausblenden für Teilnehmer/Teamer --}}
    <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-md">
        <input type="hidden" name="is_hidden" value="0">
        <input type="checkbox" id="is_hidden" name="is_hidden" value="1"
               class="mt-0.5 rounded border-gray-300 text-amber-700 focus:ring-amber-600"
               @checked(old('is_hidden', $adventure->is_hidden ?? false))>
        <div>
            <label for="is_hidden" class="font-medium text-amber-900 cursor-pointer">
                Für Teilnehmer ausblenden
            </label>
            <p class="text-sm text-amber-700 mt-0.5">
                Das Abenteuer ist dann nur für Verwalter sichtbar – unabhängig vom Status.
            </p>
        </div>
    </div>

    {{-- Spielleiter & Eventleiter (ADV-11): berechtigte Nutzer. --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="gamemaster_id" value="Spielleiter" />
            <select id="gamemaster_id" name="gamemaster_id" class="{{ $selectClass }}">
                <option value="">— keine(r) —</option>
                @foreach ($eligibleUsers as $u)
                    <option value="{{ $u->id }}" @selected(old('gamemaster_id', $adventure->gamemaster_id) == $u->id)>{{ trim("{$u->name} {$u->lastname}") }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="eventleader_id" value="Veranstaltungsleiter" />
            <select id="eventleader_id" name="eventleader_id" class="{{ $selectClass }}">
                <option value="">— keine(r) —</option>
                @foreach ($eligibleUsers as $u)
                    <option value="{{ $u->id }}" @selected(old('eventleader_id', $adventure->eventleader_id) == $u->id)>{{ trim("{$u->name} {$u->lastname}") }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <x-input-label for="max_player" value="Max. Spieler" />
            <x-text-input id="max_player" name="max_player" type="number" min="1" class="mt-1 block w-full"
                          :value="old('max_player', $adventure->max_player)" required />
            <x-input-error :messages="$errors->get('max_player')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="loot_ep_day" value="EP pro Tag" />
            <x-text-input id="loot_ep_day" name="loot_ep_day" type="number" min="0" class="mt-1 block w-full"
                          :value="old('loot_ep_day', $adventure->loot_ep_day)" />
        </div>
        <div>
            <x-input-label for="fee" value="Teilnahmebeitrag (€)" />
            <x-text-input id="fee" name="fee" type="number" step="0.01" min="0" class="mt-1 block w-full"
                          :value="old('fee', $adventure->fee)" required />
            <x-input-error :messages="$errors->get('fee')" class="mt-2" />
        </div>
    </div>

    @unless($inModal ?? false)
    <div class="flex items-center gap-4">
        <x-primary-button>Speichern</x-primary-button>
        <a href="{{ route('adventures.index') }}" class="text-sm text-gray-600 hover:underline">Abbrechen</a>
    </div>
    @endunless
</div>
