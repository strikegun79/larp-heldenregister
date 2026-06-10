<span data-modal-title hidden>{{ $player->full_name }}</span>

<dl class="grid grid-cols-2 gap-4 text-stone-800">
    <div><dt class="text-sm text-stone-500">Geburtsdatum</dt><dd>{{ optional($player->dayofbirth)->format('d.m.Y') ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Geschlecht</dt><dd>{{ $player->gender ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">E-Mail</dt><dd>{{ $player->email ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Status</dt><dd>{{ $player->active ? 'aktiv' : 'inaktiv' }}</dd></div>
</dl>

<h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Helden</h3>
<table class="ui very basic compact table">
    <thead><tr><th>Name</th><th>Klasse(n)</th><th class="right aligned">EP</th><th>Aktiver Held</th></tr></thead>
    <tbody>
        @forelse ($player->heroes as $hero)
            <tr>
                <td><a href="{{ route('heroes.show', $hero) }}" data-modal-url="{{ route('heroes.show', $hero) }}" class="text-indigo-700 hover:underline">{{ $hero->character_name ?? '—' }}</a></td>
                <td>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</td>
                <td class="right aligned">{{ number_format($hero->ep_balance, 0, ',', '.') }}</td>
                <td>
                    @if ($player->active_hero_id === $hero->id)
                        <span class="ui green label">aktiv</span>
                    @elseif (auth()->user()?->can('update', $player))
                        <form method="POST" action="{{ route('players.active-hero', $player) }}" data-refresh-modal style="display:inline">
                            @csrf @method('PATCH')
                            <input type="hidden" name="hero_id" value="{{ $hero->id }}">
                            <button type="submit" class="ui tiny button">Aktiv setzen</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-stone-500">Noch keine Helden.</td></tr>
        @endforelse
    </tbody>
</table>

<div data-modal-actions hidden>
    <a href="{{ route('players.edit', $player) }}" data-modal-url="{{ route('players.edit', $player) }}" class="ui button">Bearbeiten</a>
</div>
