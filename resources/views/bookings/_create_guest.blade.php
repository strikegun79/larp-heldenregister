<span data-modal-title hidden>Gast anmelden · {{ $adventure->name }}</span>

{{-- UI-37: Kontext-Strip auf Mobile --}}
<div class="sm:hidden flex items-center gap-2 mb-3 text-xs text-stone-400">
    <span>Abenteuer-Detail</span>
    <span aria-hidden="true">›</span>
    <span class="font-medium text-stone-600">Gast anmelden</span>
</div>

{{-- UI-31: Abenteuer-Kurzinfo vor dem Formular --}}
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
                @if ($adventure->fee_reduced !== null)
                    <span class="text-stone-400">/ ermäßigt</span>
                    <strong class="text-emerald-700">{{ number_format($adventure->fee_reduced, 2, ',', '.') }} €</strong>
                @endif
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

<div class="ui warning message" style="display:block">
    <strong>Hinweis:</strong> Für Gäste werden <em>keine Erfahrungspunkte</em> gesammelt.
</div>

@if (! $adventure->registrationOpen())
    <p class="text-stone-500">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@else
    @if ($adventure->isFull())
        <div class="ui warning message" style="display:block">
            Das Abenteuer ist voll – Gäste kommen auf die Warteliste.
        </div>
    @endif

    <form id="booking-guest-form" data-stack-close method="POST" action="{{ route('adventures.bookings.store-guest', $adventure) }}" class="ui form">
        @csrf

        <p class="text-xs text-stone-400 mb-3">Mit <span class="text-red-500">*</span> markierte Felder sind Pflichtfelder.</p>

        <div class="two fields">
            <div class="field required">
                <label>Vorname</label>
                <input type="text" name="guest_name" required>
            </div>
            <div class="field required">
                <label>Nachname</label>
                <input type="text" name="guest_lastname" required>
            </div>
        </div>
        <div class="two fields">
            <div class="field">
                <label>Alter</label>
                <input type="number" name="guest_age" min="0" max="120">
                <small class="text-stone-400">Optional – für altersgerechte Gruppenaufteilung.</small>
            </div>
            <div class="field">
                <label>Ort</label>
                <input type="text" name="guest_place">
                <small class="text-stone-400">Optional – für Abholkoordination und Notfallkontakt.</small>
            </div>
        </div>
        <div class="field required">
            <label>Rolle</label>
            <select name="event_role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->description }}</option>
                @endforeach
            </select>
        </div>

        <fieldset class="border border-stone-200 rounded p-3 mb-3">
            <legend class="text-sm font-medium text-stone-600 px-1">Optionale Angaben</legend>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="fotoerlaubnis" value="1"> Fotoerlaubnis
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="vegetarier" value="1"> Vegetarier
                </label>
            </div>
        </fieldset>

        @if ($adventure->fee_reduced !== null && $adventure->fee > 0)
        <div class="field mt-1">
            <label class="flex items-start gap-2 font-normal cursor-pointer">
                <input type="checkbox" name="ermaessigung" value="1" class="mt-1 shrink-0">
                <span>
                    <strong>Ermäßigung beantragen</strong>
                    <span class="text-stone-400">({{ number_format($adventure->fee_reduced, 2, ',', '.') }} € statt {{ number_format($adventure->fee, 2, ',', '.') }} €)</span>
                </span>
            </label>
            <p class="text-xs text-stone-500 mt-1 ml-6">
                Die Ermäßigung muss beim Check-in nachgewiesen werden, z.&nbsp;B. durch einen Bescheid über
                Grundsicherung (SGB II/XII), Kinderzuschlag oder Wohngeld.
                Ohne Nachweis gilt der reguläre Beitrag.
            </p>
        </div>
        @endif

        <div class="field">
            <label>Allergien / Unverträglichkeiten</label>
            <textarea name="allergien" rows="2" placeholder="z. B. Nüsse, Laktose, Bienen …"></textarea>
            <small class="text-stone-400">Optional – wird nur dem Organisationsteam angezeigt und dient ausschließlich der Sicherheit des Gastes.</small>
        </div>

        <div class="field">
            <label>Erreichbarkeit während der Veranstaltung</label>
            <textarea name="erreichbarkeit" rows="2" placeholder="z. B. Handy-Nummer vor Ort, Hotel, Zeltplatz …"></textarea>
            <small class="text-stone-400">Optional – wo kannst du kurzfristig erreicht werden, falls wir dich kontaktieren müssen?</small>
        </div>

        <div class="field required">
            <label>Kontaktrufnummer (Notfallkontakt)</label>
            <input type="tel" name="kontakt_telefon" maxlength="100" required
                   value="{{ old('kontakt_telefon') }}"
                   placeholder="z. B. +49 123 456789">
            <small class="text-stone-400">Diese Nummer wird im Notfall kontaktiert.</small>
        </div>

        <div class="field required mt-3">
            <label class="flex items-start gap-2 font-normal cursor-pointer">
                <input type="checkbox" name="agb" value="1" required class="mt-1 shrink-0">
                <span>Ich stimme den <a href="{{ route('agb') }}" target="_blank" class="text-waldritter underline">Teilnahmebedingungen</a> zu.</span>
            </label>
        </div>
    </form>

    <div data-modal-actions hidden>
        <button type="submit" form="booking-guest-form" class="ui primary button">Gast anmelden</button>
    </div>
@endif
