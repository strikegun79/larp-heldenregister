<x-admin.lookup-index
    title="Teilnahme-Rollen"
    :create-route="route('admin.event-roles.create')"
    create-label="Neue Rolle">

    <table class="min-w-full divide-y divide-stone-200">
        <thead class="bg-black/5">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Bezeichnung</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Anmeldungen</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-200 text-stone-800">
            @forelse ($roles as $role)
                <tr>
                    <td class="px-6 py-4" data-label="Bezeichnung">{{ $role->description }}</td>
                    <td class="px-6 py-4" data-label="Anmeldungen">{{ $role->bookings_count }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.event-roles.edit', $role) }}"
                               data-modal-url="{{ route('admin.event-roles.edit', $role) }}"
                               class="text-waldritter hover:underline">Bearbeiten</a>
                            @if (! $role->bookings_count)
                                <form method="POST" action="{{ route('admin.event-roles.destroy', $role) }}"
                                      data-confirm="Rolle „{{ $role->description }}" löschen?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-6 py-4 text-stone-500">Noch keine Rollen.</td></tr>
            @endforelse
        </tbody>
    </table>

</x-admin.lookup-index>
