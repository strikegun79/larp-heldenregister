<x-public-layout>
    <x-slot name="title">Link ungültig</x-slot>

    <div class="flex-1 flex items-center justify-center py-16 px-4">
        <div class="max-w-md w-full text-center space-y-6">

            <div class="ui icon message yellow">
                <i class="exclamation triangle icon"></i>
                <div class="content">
                    <div class="header">Dieser Link ist nicht mehr gültig</div>
                    <p>Der Bestätigungs-Link wurde bereits verwendet oder ist abgelaufen.</p>
                </div>
            </div>

            <p class="text-sm text-stone-500">
                Wenn du den Newsletter abonnieren möchtest, melde dich in deinem Profil an und
                starte die Anmeldung erneut.
            </p>

            <a href="{{ route('profile.edit') }}" class="ui primary button">
                <i class="user icon"></i> Zum Profil
            </a>

        </div>
    </div>
</x-public-layout>
