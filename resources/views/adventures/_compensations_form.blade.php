{{-- Issue #19/#38: Aufwandsentschädigung je abrechnungsfähiger Rolle im Reiter Teamer/NSC.
     Eigenes Formular (eigener Speichern-Knopf). Ohne gespeicherte Einträge werden die
     Standard-Pauschalen vorbelegt. Wird für Handy und Desktop doppelt eingebunden → $ctx als ID-Suffix.
     Erwartet: $adventure, $ctx. --}}
@php
    $billableRoles = \App\Models\EventRole::billable()->orderBy('id')->get();
    $compAmounts = $adventure->compensations()->pluck('amount', 'event_role_id');
@endphp
@if ($billableRoles->isNotEmpty())
    <form method="POST" action="{{ route('adventures.compensations.save', $adventure) }}" data-refresh-modal
          class="border border-stone-200 rounded-md p-3 mb-4 bg-white/60">
        @csrf
        @method('PUT')
        <fieldset>
            <legend class="px-1 font-medium text-stone-800">Aufwandsentschädigung für Teamer (€)</legend>
            <p class="text-sm text-stone-600 mb-3">
                Pauschale je Rolle für diese Veranstaltung. Teamer mit bestätigter Anmeldung laden damit ihre
                ausgefüllte Abrechnung herunter. Leeres Feld = für diese Rolle keine Abrechnung.
            </p>
            @if ($compAmounts->isEmpty())
                <p class="text-xs text-amber-800 mb-3">
                    <i class="info circle icon" aria-hidden="true"></i>
                    Noch nicht gespeichert – die Standard-Pauschalen sind vorbelegt.
                </p>
            @endif
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($billableRoles as $role)
                    @php
                        $default = $compAmounts->isEmpty() ? $role->default_compensation : null;
                        $value = old('compensations.'.$role->id, $compAmounts[$role->id] ?? $default);
                    @endphp
                    <div>
                        <x-input-label for="comp-{{ $role->id }}-{{ $ctx }}" :value="$role->description" />
                        <x-text-input id="comp-{{ $role->id }}-{{ $ctx }}" name="compensations[{{ $role->id }}]"
                                      type="number" step="0.01" min="0" max="9999.99" class="mt-1 block w-full"
                                      :value="$value" placeholder="keine" />
                        <x-input-error :messages="$errors->get('compensations.'.$role->id)" class="mt-2" />
                    </div>
                @endforeach
            </div>
            <button type="submit" class="ui small teal button mt-3">
                <i class="save icon" aria-hidden="true"></i> Pauschalen speichern
            </button>
        </fieldset>
    </form>
@endif
