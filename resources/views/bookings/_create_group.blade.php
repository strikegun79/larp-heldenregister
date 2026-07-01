<span data-modal-title hidden>Gruppe anmelden · {{ $adventure->name }}</span>

{{-- Kontext-Strip auf Mobile --}}
<div class="sm:hidden flex items-center gap-2 mb-3 text-xs text-stone-400">
    <span>Abenteuer-Detail</span>
    <span aria-hidden="true">›</span>
    <span class="font-medium text-stone-600">Gruppe anmelden</span>
</div>

{{-- Abenteuer-Kurzinfo --}}
@php($freeSlots = $adventure->freeSlots())
<div class="bg-[#fdf6e3] border border-[#5a3a22]/20 rounded-lg px-4 py-3 mb-4 text-sm">
    <p class="font-semibold text-stone-800 mb-2">{{ $adventure->name }}</p>
    <div class="flex flex-wrap gap-x-5 gap-y-1 text-stone-600">
        @if ($adventure->start_at)
            <span><span class="text-stone-400">Datum:</span> {{ $adventure->start_at->format('d.m.Y') }}</span>
        @endif
        @if ($adventure->location)
            <span><span class="text-stone-400">Ort:</span> {{ $adventure->location->titel }}</span>
        @endif
        <span>
            <span class="text-stone-400">Beitrag:</span>
            @if ($adventure->fee > 0)
                <strong>{{ number_format($adventure->fee, 2, ',', '.') }} €</strong>
            @else
                <strong class="text-green-700">kostenlos</strong>
            @endif
        </span>
        <span>
            <span class="text-stone-400">Plätze:</span>
            {{ $adventure->max_player - $freeSlots }} / {{ $adventure->max_player }}
            @if ($freeSlots > 0)
                <span class="text-green-700">({{ $freeSlots }} frei)</span>
            @else
                <span class="text-orange-600">(Warteliste)</span>
            @endif
        </span>
    </div>
</div>

@if (! $adventure->registrationOpen())
    <p class="text-stone-500">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@elseif ($groups->isEmpty())
    <p class="text-stone-500">Du bist in keiner Gruppe oder alle deine Gruppen-Mitglieder sind für dieses Abenteuer bereits angemeldet.</p>
@else
    @if ($adventure->isFull())
        <div class="ui warning message" style="display:block">
            Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.
        </div>
    @endif

    <form id="group-booking-form" data-stack-close method="POST"
          action="{{ route('adventures.group-bookings.store', $adventure) }}" class="ui form">
        @csrf

        <p class="text-xs text-stone-400 mb-3">Mit <span class="text-red-500">*</span> markierte Felder sind Pflichtfelder.</p>

        {{-- Mitglieder-Auswahl je Gruppe --}}
        <div class="field required mb-4">
            <label>Mitglieder auswählen <span class="text-red-500">*</span></label>

            @foreach ($groups as $group)
                <fieldset class="border border-stone-200 rounded-lg p-3 mb-3">
                    <legend class="px-2 text-xs font-semibold text-waldritter uppercase tracking-wide">
                        {{ $group->name }}
                    </legend>
                    <div class="space-y-2 mt-1">
                        @foreach ($group->heroes->sortBy('character_name') as $hero)
                            <div class="ui checkbox">
                                <input type="checkbox"
                                       name="player_ids[]"
                                       value="{{ $hero->player_id }}"
                                       id="grp_player_{{ $hero->player_id }}"
                                       checked>
                                <label for="grp_player_{{ $hero->player_id }}">
                                    <span class="font-medium">{{ $hero->character_name }}</span>
                                    @if ($hero->player)
                                        <span class="text-stone-400 text-xs ml-1">({{ $hero->player->full_name }})</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>

        {{-- Gemeinsame Felder --}}
        <div class="two fields">
            <div class="field required">
                <label>Rolle <span class="text-red-500">*</span></label>
                <select name="event_role_id" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field required">
                <label>Notfall-/Kontakttelefon <span class="text-red-500">*</span></label>
                <input type="tel" name="kontakt_telefon" value="{{ $userPhone }}"
                       placeholder="+49 …" required maxlength="100">
            </div>
        </div>

        <div class="field required">
            <div class="ui checkbox">
                <input type="checkbox" name="agb" id="grp_agb" value="1" required>
                <label for="grp_agb">
                    Ich bestätige, dass alle ausgewählten Mitglieder den
                    <a href="/agb" target="_blank" class="text-waldritter underline">AGB</a>
                    zustimmen und zur Teilnahme berechtigt sind. <span class="text-red-500">*</span>
                </label>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="ui primary button">
                <i class="users icon"></i>
                Gruppe anmelden
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.jQuery) {
                    window.jQuery('#group-booking-form .ui.checkbox').checkbox();
                }
            });
        </script>
    @endpush
@endif
