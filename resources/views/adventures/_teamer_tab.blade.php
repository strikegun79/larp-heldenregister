{{--
    Teamer-Tab: Liste der angemeldeten Teamer + Rollenzuweisung (ADV-27).
    Variablen: $adventure, $teamerSignups (Collection), $myTeamerSignup (TeamerSignup|null)
--}}

@if ($teamerSignups->isEmpty())
    <p class="text-stone-500 text-sm">Noch keine Teamer angemeldet.</p>
@else
    <table class="ui very basic compact table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Teamer-Rolle</th>
                @can('manage-attendance') <th></th> @endcan
            </tr>
        </thead>
        <tbody>
            @foreach ($teamerSignups as $signup)
                <tr>
                    <td>{{ $signup->user->name }} {{ $signup->user->lastname }}</td>
                    <td>
                        @can('events.edit')
                            <form method="POST"
                                  action="{{ route('adventures.teamer.update-role', [$adventure, $signup]) }}"
                                  class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <select name="teamer_role" class="ui compact dropdown">
                                    <option value="">— keine —</option>
                                    @foreach (\App\Models\TeamerSignup::ROLES as $role)
                                        <option value="{{ $role }}" @selected($signup->teamer_role === $role)>{{ $role }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="ui mini primary button">OK</button>
                            </form>
                        @else
                            {{ $signup->teamer_role ?? '—' }}
                        @endcan
                    </td>
                    @can('manage-attendance')
                        <td class="right aligned">
                            @if ($signup->user_id === auth()->id() || auth()->user()->hasAnyRole('project_lead', 'registrar'))
                                <form method="POST"
                                      action="{{ route('adventures.teamer.destroy', [$adventure, $signup]) }}"
                                      data-confirm="Teamer-Anmeldung stornieren?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ui mini red button">Stornieren</button>
                                </form>
                            @endif
                        </td>
                    @endcan
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
