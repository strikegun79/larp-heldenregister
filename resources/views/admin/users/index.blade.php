<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Portal-Nutzer</h2>
            <a href="{{ route('admin.users.create') }}"
               data-modal-url="{{ route('admin.users.create') }}"
               class="ui primary button">Nutzer einladen</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">E-Mail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rollen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Aktiv</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @foreach ($users as $user)
                            <tr @if (! $user->trashed())
                                    data-modal-url="{{ route('admin.users.edit', $user) }}"
                                    role="button" tabindex="0"
                                    aria-label="Nutzer {{ trim($user->name . ' ' . $user->lastname) }} bearbeiten"
                                    class="cursor-pointer hover:bg-black/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 focus-visible:outline-offset-[-2px]"
                                @else
                                    class="opacity-50 bg-stone-50"
                                @endif>
                                <td class="px-6 py-4" data-label="Name">
                                    {{ trim("{$user->name} {$user->lastname}") }}
                                    @if ($user->trashed())
                                        <span class="ml-2 text-xs text-red-600 font-semibold">[gelöscht]</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4" data-label="E-Mail">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-sm" data-label="Rollen">{{ $user->roles->pluck('label')->implode(', ') ?: '—' }}</td>
                                <td class="px-6 py-4" data-label="Aktiv">{{ $user->activated ? 'ja' : 'nein' }}</td>
                                {{-- stopPropagation verhindert, dass Klick auf Aktions-Buttons den Row-Modal-Trigger auslöst --}}
                                <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                                    @if ($user->trashed())
                                        <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="inline"
                                              data-confirm="Konto von {{ trim($user->name . ' ' . $user->lastname) }} wiederherstellen?">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ui mini basic green button">
                                                <i class="redo icon"></i> Wiederherstellen
                                            </button>
                                        </form>
                                    @else
                                        {{-- AUTH-14: Lösch-Button ist ins Bearbeitungs-Modal gewandert;
                                             stattdessen Profil-Seite öffnen --}}
                                        <a href="{{ route('admin.users.profile', $user) }}"
                                           class="ui mini basic icon button" title="Profil öffnen">
                                            <i class="user icon"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </x-mobile.cards-or-table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
