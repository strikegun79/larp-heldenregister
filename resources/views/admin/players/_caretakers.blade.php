<span data-modal-title hidden>Betreuer: {{ $player->full_name }}</span>

<h4 class="font-semibold mb-2">Zugeordnete Betreuer</h4>
@forelse ($player->users as $user)
    <div class="flex items-center justify-between border-b border-stone-200 py-2">
        <div>
            {{ trim("{$user->name} {$user->lastname}") }}
            <span class="text-stone-500 text-sm">{{ $user->email }}</span>
            @if ($user->pivot->self)<span class="ui mini label">self</span>@endif
        </div>
        <form method="POST" action="{{ route('admin.players.caretakers.destroy', [$player, $user]) }}" data-refresh-modal
              onsubmit="return confirm('Betreuer „{{ trim("{$user->name} {$user->lastname}") }}“ entfernen?');">
            @csrf @method('DELETE')
            <button type="submit" class="ui mini red icon button" data-tooltip="Entfernen" data-position="top center"><i class="times icon"></i></button>
        </form>
    </div>
@empty
    <p class="text-stone-500">Noch keine Betreuer zugeordnet.</p>
@endforelse

@if ($available->isNotEmpty())
    <h4 class="font-semibold mt-4 mb-2">Betreuer hinzufügen</h4>
    <form method="POST" action="{{ route('admin.players.caretakers.store', $player) }}" data-refresh-modal class="ui form">
        @csrf
        <div class="flex items-end gap-2">
            <div class="field !mb-0 flex-1">
                <select name="user_id" required>
                    <option value="">— Nutzer wählen —</option>
                    @foreach ($available as $user)
                        <option value="{{ $user->id }}">{{ trim("{$user->name} {$user->lastname}") }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ui primary button">Hinzufügen</button>
        </div>
    </form>
@endif
