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
                <div class="overflow-x-auto">
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
                            <tr class="{{ $user->trashed() ? 'opacity-50 bg-stone-50' : '' }}">
                                <td class="px-6 py-4">
                                    {{ trim("{$user->name} {$user->lastname}") }}
                                    @if ($user->trashed())
                                        <span class="ml-2 text-xs text-red-600 font-semibold">[gelöscht]</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-sm">{{ $user->roles->pluck('label')->implode(', ') ?: '—' }}</td>
                                <td class="px-6 py-4">{{ $user->activated ? 'ja' : 'nein' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if ($user->trashed())
                                        <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="inline"
                                              data-confirm="Konto von {{ trim($user->name . ' ' . $user->lastname) }} wiederherstellen?">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-green-700 hover:underline">Wiederherstellen</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           data-modal-url="{{ route('admin.users.edit', $user) }}"
                                           class="text-waldritter hover:underline">Bearbeiten</a>
                                        @if (! $user->hasRole('admin') && $user->id !== Auth::id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                                                  class="inline ms-3"
                                                  data-confirm="Konto von {{ trim($user->name . ' ' . $user->lastname) }} löschen?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
