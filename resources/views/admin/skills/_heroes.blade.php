<span data-modal-title hidden>Aktive Helden: {{ $skill->name }}</span>

@if ($heroes->isEmpty())
    <p class="text-stone-500">Kein aktiver Held hat diese Fertigkeit erworben.</p>
@else
    <table class="ui very basic compact table">
        <thead>
            <tr>
                <th>Held</th>
                <th>Spieler</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($heroes as $hero)
                <tr>
                    <td>
                        <a href="{{ route('heroes.show', $hero) }}"
                           data-modal-url="{{ route('heroes.show', $hero) }}"
                           class="text-waldritter hover:underline font-medium">
                            {{ $hero->character_name ?? '—' }}
                        </a>
                    </td>
                    <td class="text-stone-600">{{ $hero->player?->full_name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="text-xs text-stone-400 mt-3">{{ $heroes->count() }} aktive {{ $heroes->count() === 1 ? 'Held' : 'Helden' }}</p>
@endif
