{{-- Issue #19: Bereich „Teamer“ – Angaben für die Abrechnung der Aufwandsentschädigung.
     Eigenes Formular (profile.teamer.update), Werte werden verschlüsselt gespeichert. --}}
@php
    $tb = $errors->teamerBilling;
    $missing = $user->missingTeamerBillingFields();
@endphp
<section aria-labelledby="teamer-billing-title">
    <header>
        <h2 id="teamer-billing-title" class="font-uncial text-lg text-waldritter">Teamer</h2>
        <p class="mt-1 text-sm text-stone-600">
            Diese Angaben setzen wir in deine Abrechnung der Aufwandsentschädigung ein. Name, Anschrift und
            E-Mail kommen aus deinen Profildaten oben. Die Angaben sind verschlüsselt gespeichert und nur für
            dich sichtbar.
        </p>
    </header>

    @if (session('status') === 'teamer-billing-updated')
        <p class="mt-3 text-sm font-semibold text-green-800" role="status">Angaben gespeichert.</p>
    @endif

    @if ($missing)
        <p class="mt-3 text-sm text-stone-700" id="teamer-billing-missing">
            Für die Abrechnung fehlt noch: {{ implode(', ', $missing) }}.
        </p>
    @endif

    <form method="POST" action="{{ route('profile.teamer.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="teamer_tax_number" value="Steuernummer" />
            <x-text-input id="teamer_tax_number" name="teamer_tax_number" type="text" class="mt-1 block w-full"
                          :value="old('teamer_tax_number', $user->teamer_tax_number)" maxlength="30" autocomplete="off"
                          inputmode="numeric" />
            <x-input-error class="mt-2" :messages="$tb->get('teamer_tax_number')" />
        </div>

        <fieldset class="space-y-4">
            <legend class="font-medium text-stone-800">Erstattung auf folgendes Konto</legend>
            <div>
                <x-input-label for="teamer_account_holder" value="Kontoinhaber (Empfänger)" />
                <x-text-input id="teamer_account_holder" name="teamer_account_holder" type="text" class="mt-1 block w-full"
                              :value="old('teamer_account_holder', $user->teamer_account_holder)" maxlength="100" autocomplete="name" />
                <x-input-error class="mt-2" :messages="$tb->get('teamer_account_holder')" />
            </div>
            <div>
                <x-input-label for="teamer_iban" value="IBAN" />
                <x-text-input id="teamer_iban" name="teamer_iban" type="text" class="mt-1 block w-full font-mono"
                              :value="old('teamer_iban', $user->teamer_iban ? \App\Support\Iban::format($user->teamer_iban) : null)"
                              maxlength="42" autocomplete="off" spellcheck="false" placeholder="DE00 0000 0000 0000 0000 00"
                              aria-describedby="teamer_iban_help" />
                <p id="teamer_iban_help" class="text-xs text-stone-600 mt-1">Leerzeichen sind erlaubt; die IBAN wird beim Speichern geprüft.</p>
                <x-input-error class="mt-2" :messages="$tb->get('teamer_iban')" />
            </div>
            <div>
                <x-input-label for="teamer_bank_name" value="Name der Bank" />
                <x-text-input id="teamer_bank_name" name="teamer_bank_name" type="text" class="mt-1 block w-full"
                              :value="old('teamer_bank_name', $user->teamer_bank_name)" maxlength="100" autocomplete="off" />
                <x-input-error class="mt-2" :messages="$tb->get('teamer_bank_name')" />
            </div>
        </fieldset>

        <div class="flex items-center gap-4">
            <x-primary-button>Speichern</x-primary-button>
        </div>
    </form>
</section>
