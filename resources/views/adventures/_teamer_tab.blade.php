{{--
    Teamer-Tab: Liste der angemeldeten Teamer + Rollenzuweisung (ADV-27).
    Variablen: $adventure, $teamerSignups (Collection), $myTeamerSignup (TeamerSignup|null)
--}}

@if ($teamerSignups->isEmpty())
    <p class="text-stone-500 text-sm">Noch keine Teamer angemeldet.</p>
@else
    <x-mobile.cards-or-table>
    <table class="ui very basic compact unstackable table">
        <thead class="mob-thead" hidden>
            <tr>
                <th>Name</th>
                <th>Teamer-Rolle</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($teamerSignups as $signup)
                <tr>
                    <td data-label="Name">{{ $signup->user->name }} {{ $signup->user->lastname }}</td>
                    <td data-label="Rolle">{{ $signup->teamer_role ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </x-mobile.cards-or-table>
@endif
