{{-- Nutzerliste mit Seitenzahlen (wird vom automatischen Filter ausgetauscht).
     Erwartet: $users, $filtered. --}}
<p class="sr-only" role="status">{{ $users->total() }} Nutzer gefunden</p>
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">E-Mail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rollen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Aktiv</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($users as $user)
                            {{-- Zeile öffnet Rollen/Aktivierung/Löschen im Inspektor; gelöschte Konten nur wiederherstellen --}}
                            <tr @unless ($user->trashed())
                                    data-inspector-url="{{ route('admin.users.edit', $user) }}?inspector=1" tabindex="0"
                                    aria-label="Nutzer {{ trim($user->name . ' ' . $user->lastname) }} bearbeiten"
                                @endunless>
                                <td class="px-6 py-4" data-label="Name">
                                    {{ trim("{$user->name} {$user->lastname}") }}
                                    @if ($user->trashed())
                                        <span class="wr-pill wr-pill--danger wr-pill--small ml-2">gelöscht</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4" data-label="E-Mail">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-sm" data-label="Rollen">{{ $user->roles->pluck('label')->implode(', ') ?: '—' }}</td>
                                <td class="px-6 py-4" data-label="Aktiv">{{ $user->activated ? 'ja' : 'nein' }}</td>
                                {{-- Links und Buttons in der Zeile öffnen nicht den Inspektor (JS prüft das Ziel) --}}
                                <td class="px-6 py-4 text-right">
                                    @if ($user->trashed())
                                        <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="inline"
                                              data-confirm="Konto von {{ trim($user->name . ' ' . $user->lastname) }} wiederherstellen?">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="ui mini basic green button">
                                                <i class="redo icon"></i> Wiederherstellen
                                            </button>
                                        </form>
                                    @else
                                        {{-- AUTH-14: Löschen steht im Inspektor; hier nur die Profil-Seite --}}
                                        <span class="inline-flex items-center gap-2">
                                            <a href="{{ route('admin.users.profile', $user) }}"
                                               class="wr-btn wr-btn--secondary wr-btn--small"
                                               aria-label="Profil von {{ trim($user->name . ' ' . $user->lastname) }} öffnen">
                                                <i class="user icon m-0" aria-hidden="true"></i> Profil
                                            </a>
                                            <i class="chevron right icon text-wr-ink-muted m-0" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-stone-400">
                                    @if ($filtered)
                                        Keine Nutzer für diese Filter gefunden.
                                    @else
                                        Noch keine Nutzer vorhanden.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </x-mobile.cards-or-table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
