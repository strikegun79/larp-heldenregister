<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Portal-Nutzer</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
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
                            <tr>
                                <td class="px-6 py-4">{{ trim("{$user->name} {$user->lastname}") }}</td>
                                <td class="px-6 py-4">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-sm">{{ $user->roles->pluck('label')->implode(', ') ?: '—' }}</td>
                                <td class="px-6 py-4">{{ $user->activated ? 'ja' : 'nein' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-700 hover:underline">Bearbeiten</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
            <a href="{{ route('admin.index') }}" class="inline-block mt-4 text-sm text-stone-600 hover:underline">&larr; Zur Verwaltung</a>
        </div>
    </div>
</x-app-layout>
