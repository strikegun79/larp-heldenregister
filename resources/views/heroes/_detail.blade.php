<div class="flex items-center justify-between mb-4">
    <h2 class="font-uncial text-2xl text-waldritter">{{ $hero->character_name ?? 'Held' }}</h2>
    @can('heldenregister.edit')
        <a href="{{ route('heroes.edit', $hero) }}" class="ui small button">Bearbeiten</a>
    @endcan
</div>

<dl class="grid grid-cols-2 gap-4 text-stone-800">
    <div><dt class="text-sm text-stone-500">Spieler</dt><dd>{{ $hero->player?->full_name ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Klassen</dt><dd>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Heimatort</dt><dd>{{ $hero->homeplace ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">EP-Saldo</dt><dd class="font-semibold">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</dd></div>
    <div><dt class="text-sm text-stone-500">Geboren</dt><dd>{{ optional($hero->born)->format('d.m.Y') ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Gestorben</dt><dd>{{ optional($hero->died)->format('d.m.Y') ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Status</dt><dd>{{ $hero->active ? 'aktiv' : 'inaktiv' }}</dd></div>
</dl>

<h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Fertigkeiten</h3>
@forelse ($hero->skills as $skill)
    <span class="ui label">{{ $skill->name }} ({{ $skill->ep_costs }} EP)</span>
@empty
    <p class="text-stone-500">Keine Fertigkeiten erlernt.</p>
@endforelse

<h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">EP-Verlauf</h3>
<table class="ui very basic compact table">
    <thead><tr><th>Datum</th><th>Art</th><th class="right aligned">EP</th></tr></thead>
    <tbody>
        @forelse ($hero->epTransactions->sortByDesc('transacted_at') as $tx)
            <tr>
                <td>{{ optional($tx->transacted_at)->format('d.m.Y') }}</td>
                <td>{{ $tx->type?->description }}</td>
                <td class="right aligned {{ $tx->type?->is_credit ? 'text-green-600' : 'text-red-600' }}">
                    {{ $tx->type?->is_credit ? '+' : '−' }}{{ number_format($tx->ep_count, 0, ',', '.') }}
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-stone-500">Keine EP-Buchungen.</td></tr>
        @endforelse
    </tbody>
</table>
