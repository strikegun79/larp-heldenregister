<span data-modal-title hidden>Mitglieder – {{ $group->name }}</span>

<div data-modal-actions hidden>
    <button type="button" class="ui deny button">Schließen</button>
</div>

{{-- Aktuelle Mitglieder --}}
@if ($group->heroes->isEmpty())
    <p class="text-stone-500 mb-4">Noch keine Mitglieder.</p>
@else
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full divide-y divide-stone-200">
            <thead class="bg-black/5">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Held</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Rolle</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Beigetreten</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 text-stone-800">
                @foreach ($group->heroes as $member)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $member->character_name }}</td>
                        <td class="px-4 py-2 text-stone-500">{{ $member->pivot->role ?: '—' }}</td>
                        <td class="px-4 py-2 text-stone-500">
                            {{ $member->pivot->joined_at ? \Carbon\Carbon::parse($member->pivot->joined_at)->format('d.m.Y') : '—' }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST"
                                  action="{{ route('admin.groups.members.destroy', [$group, $member]) }}"
                                  data-confirm="Held „{{ $member->character_name }}" aus der Gruppe entfernen?">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm">Entfernen</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Held hinzufügen --}}
<h4 class="ui dividing header">Held hinzufügen</h4>

@if ($available->isEmpty())
    <p class="text-stone-500">Alle Helden sind bereits Mitglied.</p>
@else
    <form method="POST" action="{{ route('admin.groups.members.store', $group) }}">
        @csrf
        <div class="ui form">
            <div class="two fields">
                <div class="required field">
                    <label for="hero_id">Held</label>
                    <select name="hero_id" id="hero_id" class="ui dropdown">
                        <option value="">— Held wählen —</option>
                        @foreach ($available as $hero)
                            <option value="{{ $hero->id }}">{{ $hero->character_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="role">Rolle (optional)</label>
                    <select name="role" id="role" class="ui dropdown">
                        <option value="">— keine —</option>
                        <option value="Anführer">Anführer</option>
                        <option value="Mitglied">Mitglied</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="ui primary button">Hinzufügen</button>
        </div>
    </form>
@endif
