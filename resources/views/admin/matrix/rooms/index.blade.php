<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Matrix-Räume
            </h2>
            <a href="{{ route('admin.matrix.rooms.create') }}" class="ui mini primary button">
                + Raum anlegen
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="ui success message mb-4">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="ui error message mb-4">
                    <ul class="list">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                @if ($rooms->isEmpty())
                    <div class="p-6 text-stone-500 text-sm">
                        Noch keine Räume vorhanden.
                        <a href="{{ route('admin.matrix.rooms.create') }}" class="underline">Jetzt anlegen.</a>
                    </div>
                @else
                    <table class="ui very basic table w-full">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Typ</th>
                                <th>Room-ID</th>
                                <th class="text-center">Standard</th>
                                <th class="text-center">Mitglieder</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rooms as $room)
                                <tr>
                                    <td class="font-medium">{{ $room->roomname }}</td>
                                    <td>
                                        <span class="ui {{ $room->roomtype === 'Space' ? 'violet' : 'blue' }} tiny label">
                                            {{ $room->roomtype }}
                                        </span>
                                    </td>
                                    <td class="font-mono text-xs text-stone-500">{{ $room->roomid }}</td>
                                    <td class="text-center">
                                        @if ($room->default_allow)
                                            <span class="ui green tiny label">allow</span>
                                        @elseif ($room->default_deny)
                                            <span class="ui red tiny label">deny</span>
                                        @else
                                            <span class="text-stone-400">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-stone-600">{{ $room->accounts_count }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.matrix.rooms.edit', $room) }}"
                                           class="ui mini basic button">Bearbeiten</a>
                                        <form method="POST"
                                              action="{{ route('admin.matrix.rooms.destroy', $room) }}"
                                              class="inline-block"
                                              data-confirm="Raum „{{ $room->roomname }}" wirklich löschen?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui mini basic red button">Löschen</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
