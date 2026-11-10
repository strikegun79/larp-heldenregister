<x-public-layout>
    <x-slot name="title">Newsletter bestätigt</x-slot>

    <div class="flex-1 flex items-center justify-center py-16 px-4">
        <div class="max-w-md w-full text-center space-y-6">

            <div class="ui icon message green">
                <i class="check circle icon"></i>
                <div class="content">
                    <div class="header">Du bist dabei!</div>
                    <p>Deine Newsletter-Anmeldung ist jetzt bestätigt. Wir melden uns bald.</p>
                </div>
            </div>

            <p class="text-sm text-stone-500">
                Du kannst den Newsletter jederzeit in deinem Profil oder über den Abmelde-Link in jeder E-Mail abbestellen.
            </p>

            <a href="{{ route('dashboard') }}" class="ui primary button">
                <i class="home icon"></i> Zum Portal
            </a>

        </div>
    </div>
</x-public-layout>
