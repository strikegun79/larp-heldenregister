<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Helden-Klassen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.hero-classes.create') }}" data-modal-url="{{ route('admin.hero-classes.create') }}" class="ui primary button">Neue Klasse</a>
            </div>

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">EP-Kosten</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Helden</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($classes as $class)
                            <tr class="{{ $class->disabled ? 'opacity-60' : '' }}">
                                <td class="px-6 py-4">{{ $class->name }}</td>
                                <td class="px-6 py-4 text-sm text-stone-500">{{ $class->slug }}</td>
                                <td class="px-6 py-4">{{ $class->ep_cost }} EP</td>
                                <td class="px-6 py-4">{{ $class->heroes_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($class->disabled)
                                        <span class="text-stone-500">deaktiviert</span>
                                    @else
                                        <span class="text-green-700">aktiv</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.hero-classes.edit', $class) }}" data-modal-url="{{ route('admin.hero-classes.edit', $class) }}" class="text-waldritter hover:underline">Bearbeiten</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-4 text-stone-500">Noch keine Klassen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
