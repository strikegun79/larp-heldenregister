<x-admin.lookup-index
    title="Teilnahme-Rollen"
    :create-route="route('admin.event-roles.create')"
    create-label="Neue Rolle">

    <table class="min-w-full divide-y divide-stone-200">
        <thead class="bg-black/5">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Bezeichnung</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Teilnehmer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Teamer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Abrechnung</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Anmeldungen</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-200 text-stone-800">
            @forelse ($roles as $role)
                <tr
                                data-inspector-url="{{ route('admin.event-roles.edit', $role) }}?inspector=1" tabindex="0"
                                aria-label="{{ $role->description }} bearbeiten">
                    <td class="px-6 py-4" data-label="Bezeichnung">{{ $role->description }}</td>
                    <td class="px-6 py-4" data-label="Teilnehmer">
                        @if ($role->for_participant)
                            <span class="text-green-600" title="Ja">&#10003;</span>
                        @else
                            <span class="text-stone-400" title="Nein">&#8211;</span>
                        @endif
                    </td>
                    <td class="px-6 py-4" data-label="Teamer">
                        @if ($role->for_teamer)
                            <span class="text-green-600" title="Ja">&#10003;</span>
                        @else
                            <span class="text-stone-400" title="Nein">&#8211;</span>
                        @endif
                    </td>
                    {{-- Issue #19: abrechnungsfähig mit Standard-Pauschale --}}
                    <td class="px-6 py-4" data-label="Abrechnung">
                        @if ($role->billable)
                            <span class="wr-pill wr-pill--success wr-pill--small">
                                <i class="euro sign icon" aria-hidden="true"></i>abrechnungsfähig{{ $role->default_compensation !== null ? ' · '.number_format($role->default_compensation, 2, ',', '.').' €' : '' }}
                            </span>
                        @else
                            <span class="text-stone-400">nein</span>
                        @endif
                    </td>
                    <td class="px-6 py-4" data-label="Anmeldungen">{{ $role->bookings_count }}</td>
                    <td class="px-6 py-4 text-right">
                                    {{-- Bearbeiten und Löschen im Inspektor (Zeilenklick / Enter) --}}
                                    <i class="chevron right icon text-wr-ink-muted m-0" aria-hidden="true"></i>
                                </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-4 text-stone-500">Noch keine Rollen.</td></tr>
            @endforelse
        </tbody>
    </table>

</x-admin.lookup-index>
