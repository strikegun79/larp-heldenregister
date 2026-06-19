<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Rollen & Berechtigungen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rolle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Berechtigungen</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase">Nutzer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @foreach ($roles as $role)
                            <tr class="align-top">
                                <td class="px-6 py-4 font-medium">{{ $role->label }}</td>
                                <td class="px-6 py-4 font-mono text-sm text-stone-500">{{ $role->slug }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($role->permissions)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($role->permissions as $perm)
                                                <span class="inline-block bg-stone-100 text-stone-700 rounded px-2 py-0.5 text-xs font-mono">{{ $perm }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-stone-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums">{{ $role->users_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.index') }}">
                    <x-primary-button>Zurück zur Verwaltung</x-primary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
