<span data-modal-title hidden>{{ $hero->character_name ?? 'Held' }}</span>

@php($learnedIds = $hero->skills->pluck('id'))

<div id="skilltree"
     data-learn-url="{{ route('heroes.skills.store', $hero) }}"
     data-balance="{{ $hero->ep_balance }}"
     data-can-edit="{{ auth()->user()?->can('heldenregister.edit') ? 1 : 0 }}">

    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="overview">Übersicht</a>
        <a class="item" data-tab="adventures">Abenteuer</a>
        @foreach ($hero->classes as $class)
            <a class="item" data-tab="cls-{{ $class->id }}">{{ $class->name }}</a>
        @endforeach
        <a class="item" data-tab="ep">EP-Verlauf</a>
    </div>

    {{-- Tab: Übersicht --}}
    <div class="ui bottom attached tab segment active" data-tab="overview">
        <dl class="grid grid-cols-2 gap-4 text-stone-800">
            <div><dt class="text-sm text-stone-500">Spieler</dt><dd>{{ $hero->player?->full_name ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Klassen</dt><dd>{{ $hero->classes->pluck('name')->implode(', ') ?: '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Heimatort</dt><dd>{{ $hero->homeplace ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">EP-Saldo</dt><dd class="font-semibold">{{ number_format($hero->ep_balance, 0, ',', '.') }} EP</dd></div>
            <div><dt class="text-sm text-stone-500">Geboren</dt><dd>{{ optional($hero->born)->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Gestorben</dt><dd>{{ optional($hero->died)->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Status</dt><dd>{{ $hero->active ? 'aktiv' : 'inaktiv' }}</dd></div>
        </dl>

        <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Erworbene Fertigkeiten</h3>
        @forelse ($hero->skills as $skill)
            <span class="ui label">{{ $skill->name }} ({{ $skill->ep_costs }} EP)</span>
        @empty
            <p class="text-stone-500">Keine Fertigkeiten erlernt.</p>
        @endforelse
    </div>

    {{-- Tab: Abenteuer (Anmeldungen des Spielers) --}}
    <div class="ui bottom attached tab segment" data-tab="adventures">
        @php($bookings = $hero->player?->bookings?->sortByDesc(fn ($b) => optional($b->adventure)->start_at) ?? collect())
        @if ($bookings->isEmpty())
            <p class="text-stone-500">Keine Abenteuer.</p>
        @else
            <table class="ui very basic compact table">
                <thead><tr><th>Abenteuer</th><th>Beginn</th><th>Liste</th></tr></thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->adventure?->name ?? '—' }}</td>
                            <td>{{ optional($booking->adventure?->start_at)->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-xs text-stone-400 mt-2">Anmeldungen des Spielers (spielerbezogen).</p>
        @endif
    </div>

    {{-- Tabs: Fertigkeitsbaum je Klasse --}}
    @foreach ($hero->classes as $class)
        <div class="ui bottom attached tab segment" data-tab="cls-{{ $class->id }}">
            <div class="skill-map">
                <img src="{{ $class->skilltreeImage() }}" alt="Fertigkeitsbaum {{ $class->name }}" class="skill-image">
                @foreach ($class->skills as $skill)
                    @php($learned = $learnedIds->contains($skill->id))
                    @php($unset = ($skill->pivot->x_percentage == 0 && $skill->pivot->y_percentage == 0))
                    @php($px = $unset ? 6 + ($loop->index % 10) * 9 : (int) $skill->pivot->x_percentage)
                    @php($py = $unset ? 8 + intdiv($loop->index, 10) * 11 : (int) $skill->pivot->y_percentage)
                    <button type="button"
                            class="skill-marker skill-trigger {{ $learned ? 'learned' : 'unlearned' }}"
                            style="left: {{ $px }}%; top: {{ $py }}%;"
                            title="{{ $skill->name }} ({{ $skill->ep_costs }} EP)"
                            data-skill-id="{{ $skill->id }}"
                            data-skill-name="{{ $skill->name }}"
                            data-skill-desc="{{ $skill->description }}"
                            data-skill-cost="{{ $skill->ep_costs }}"
                            data-skill-learned="{{ $learned ? 1 : 0 }}"></button>
                @endforeach
            </div>

            @if ($class->skills->isEmpty())
                <p class="text-stone-500">Für diese Klasse sind keine Fertigkeiten hinterlegt.</p>
            @else
                <div class="ui middle aligned divided list skill-list">
                    @foreach ($class->skills as $skill)
                        @php($learned = $learnedIds->contains($skill->id))
                        <div class="item">
                            <div class="content">
                                <a class="skill-trigger {{ $learned ? 'text-green-700' : 'text-indigo-700' }} hover:underline" style="cursor:pointer"
                                   data-skill-id="{{ $skill->id }}"
                                   data-skill-name="{{ $skill->name }}"
                                   data-skill-desc="{{ $skill->description }}"
                                   data-skill-cost="{{ $skill->ep_costs }}"
                                   data-skill-learned="{{ $learned ? 1 : 0 }}">
                                    {{ $learned ? '✓ ' : '' }}{{ $skill->name }} ({{ $skill->ep_costs }} EP)
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @can('heldenregister.edit')
                <div class="mt-3">
                    <a href="{{ route('skilltree.edit', $class) }}" class="ui tiny basic button">Positionen bearbeiten</a>
                </div>
            @endcan
        </div>
    @endforeach

    {{-- Tab: EP-Verlauf (Buchen + Historie) --}}
    <div class="ui bottom attached tab segment" data-tab="ep">
        @can('heldenregister.edit')
            <form method="POST" action="{{ route('heroes.ep.store', $hero) }}" class="ui form" data-refresh-modal style="margin-bottom:1rem">
                @csrf
                <div class="inline fields" style="align-items:flex-end;flex-wrap:wrap">
                    <div class="field">
                        <label>EP</label>
                        <input type="number" name="ep_count" step="1" min="1" placeholder="Anzahl" required style="width:7rem">
                    </div>
                    <div class="field" style="flex:1;min-width:12rem">
                        <label>Grund</label>
                        <select name="ep_transaction_type_id" required>
                            @foreach ($epTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->description }} ({{ $type->is_credit ? '+' : '−' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <button type="submit" class="ui primary button">Buchen</button>
                    </div>
                </div>
            </form>
        @endcan

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
    </div>
</div>

<div data-modal-actions hidden>
    @can('heldenregister.edit')
        <a href="{{ route('heroes.edit', $hero) }}" data-modal-url="{{ route('heroes.edit', $hero) }}" class="ui button">Bearbeiten</a>
    @endcan
</div>
