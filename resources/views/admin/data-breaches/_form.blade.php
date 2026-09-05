@php($editing = isset($log->id))

<div class="space-y-6">

    {{-- Entdeckungszeitpunkt --}}
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
            Zeitpunkt der Entdeckung <span class="text-red-600">*</span>
        </label>
        <input type="datetime-local" name="discovered_at"
               value="{{ old('discovered_at', $log->discovered_at?->format('Y-m-d\TH:i')) }}"
               class="ui input w-full" required>
        @error('discovered_at')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Beschreibung --}}
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
            Beschreibung des Vorfalls <span class="text-red-600">*</span>
        </label>
        <textarea name="description" rows="4" maxlength="5000"
                  class="w-full rounded border-stone-300 border p-2 text-sm"
                  placeholder="Was ist passiert? Wie wurde die Panne entdeckt? Welche Systeme/Daten sind betroffen?"
                  required>{{ old('description', $log->description) }}</textarea>
        @error('description')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Datenkategorien --}}
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-2">
            Betroffene Datenkategorien <span class="text-red-600">*</span>
        </label>
        <div class="space-y-1">
            @foreach($categories as $key => $label)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="affected_data_categories[]" value="{{ $key }}"
                           @checked(in_array($key, old('affected_data_categories', $log->affected_data_categories ?? [])))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('affected_data_categories')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Anzahl betroffene Personen --}}
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
            Anzahl betroffener Personen (geschätzt)
        </label>
        <input type="number" name="affected_persons_count" min="0"
               value="{{ old('affected_persons_count', $log->affected_persons_count) }}"
               class="ui input" style="width:160px"
               placeholder="z.B. 12">
        @error('affected_persons_count')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Wahrscheinliche Folgen --}}
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
            Wahrscheinliche Folgen für Betroffene
        </label>
        <textarea name="likely_consequences" rows="3" maxlength="3000"
                  class="w-full rounded border-stone-300 border p-2 text-sm"
                  placeholder="Z.B. Identitätsmissbrauch, Diskriminierung, finanzieller Schaden, Imageverlust …">{{ old('likely_consequences', $log->likely_consequences) }}</textarea>
        @error('likely_consequences')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Maßnahmen --}}
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
            Getroffene Maßnahmen <span class="text-red-600">*</span>
        </label>
        <textarea name="measures_taken" rows="4" maxlength="5000"
                  class="w-full rounded border-stone-300 border p-2 text-sm"
                  placeholder="Was wurde getan, um die Panne einzudämmen und künftig zu verhindern?"
                  required>{{ old('measures_taken', $log->measures_taken) }}</textarea>
        @error('measures_taken')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Meldepflicht --}}
    <div class="bg-stone-50 border border-stone-200 rounded p-4 space-y-4">
        <p class="text-sm font-medium text-stone-700">Behördliche Meldung (Art. 33 DSGVO)</p>

        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="reportable" value="0">
            <input type="checkbox" name="reportable" value="1"
                   id="reportable"
                   @checked(old('reportable', $log->reportable))
                   onchange="document.getElementById('meldung-felder').classList.toggle('hidden', !this.checked)">
            Vorfall ist meldepflichtig (voraussichtlich Risiko für Betroffene)
        </label>

        <div id="meldung-felder" class="{{ old('reportable', $log->reportable) ? '' : 'hidden' }} space-y-4 pl-4 border-l-2 border-amber-400">
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="reported_to_authority" value="0">
                <input type="checkbox" name="reported_to_authority" value="1"
                       id="reported_cb"
                       @checked(old('reported_to_authority', $log->reported_to_authority))
                       onchange="document.getElementById('meldedatum').classList.toggle('hidden', !this.checked)">
                Bereits an Aufsichtsbehörde (HBDI) gemeldet
            </label>

            <div id="meldedatum" class="{{ old('reported_to_authority', $log->reported_to_authority) ? '' : 'hidden' }} space-y-3">
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1">Meldedatum/-uhrzeit</label>
                    <input type="datetime-local" name="reported_at"
                           value="{{ old('reported_at', $log->reported_at?->format('Y-m-d\TH:i')) }}"
                           class="ui input">
                    @error('reported_at')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1">Referenznummer der Behörde</label>
                    <input type="text" name="authority_reference" maxlength="255"
                           value="{{ old('authority_reference', $log->authority_reference) }}"
                           class="ui input" placeholder="z.B. HBDI-2026-XXXX">
                    @error('authority_reference')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Interne Notizen --}}
    <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">Interne Notizen</label>
        <textarea name="internal_notes" rows="3" maxlength="3000"
                  class="w-full rounded border-stone-300 border p-2 text-sm"
                  placeholder="Nur intern sichtbar – z.B. betroffene Systeme, Ansprechpartner, Verlauf …">{{ old('internal_notes', $log->internal_notes) }}</textarea>
        @error('internal_notes')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

</div>
