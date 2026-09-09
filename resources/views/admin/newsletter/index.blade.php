<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">Newsletter</h2>
            </div>
            <a href="{{ route('admin.newsletter.create') }}" class="ui primary button">
                <i class="plus icon"></i> Neuer Newsletter
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="ui success message mb-4">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="ui error message mb-4">{{ session('error') }}</div>
            @endif

            {{-- Abonnenten-Accordion --}}
            <div class="ui styled fluid accordion mb-6 bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg overflow-hidden" id="subscribers-accordion">
                <div class="title text-stone-700">
                    <i class="dropdown icon"></i>
                    <i class="users icon"></i>
                    <strong>{{ $activeSubscribers }}</strong> aktive Abonnenten
                </div>
                <div class="content">
                    @if ($subscriptions->isEmpty())
                        <p class="text-stone-400 text-sm py-2">Noch keine bestätigten Abonnenten.</p>
                    @else
                        <div style="overflow-x: auto;">
                            <table class="min-w-full divide-y divide-stone-200 text-sm">
                                <thead class="bg-black/5">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">E-Mail</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Konto</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Bestätigt am</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-100">
                                    @foreach ($subscriptions as $sub)
                                        <tr class="hover:bg-black/5">
                                            <td class="px-4 py-2 font-mono text-stone-800">{{ $sub->email }}</td>
                                            <td class="px-4 py-2 text-stone-500">{{ $sub->user?->name ?? '—' }}</td>
                                            <td class="px-4 py-2 text-stone-500">{{ $sub->confirmed_at->locale('de')->isoFormat('D. MMM YYYY') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Desktop-Tabelle (sm+) --}}
            <div class="hidden sm:block bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Betreff</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Empfänger</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Versendet am</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse ($newsletters as $newsletter)
                            @php [$badge, $label] = $newsletter->statusBadge() @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $newsletter->title }}</td>
                                <td class="px-4 py-3">
                                    <span class="ui {{ $badge }} label">{{ $label }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-stone-600">
                                    @if ($newsletter->isSent())
                                        {{ $newsletter->sends_count }}
                                    @else
                                        {{ $activeSubscribers }}
                                        <span class="text-stone-400 cursor-help border-b border-dotted border-stone-400"
                                              data-tooltip="Zum Versandzeitpunkt kann die tatsächliche Empfängerzahl abweichen."
                                              data-position="top center"
                                              data-inverted>(aktuell)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-stone-500">
                                    {{ $newsletter->sent_at?->locale('de')->isoFormat('D. MMM YYYY') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    {{-- Primäraktion --}}
                                    @if ($newsletter->isDraft())
                                        <a href="{{ route('admin.newsletter.edit', $newsletter) }}"
                                           class="ui small primary button">
                                            <i class="edit icon"></i> Bearbeiten
                                        </a>
                                    @else
                                        <a href="{{ route('admin.newsletter.show', $newsletter) }}"
                                           class="ui small button">
                                            <i class="eye icon"></i> Anzeigen
                                        </a>
                                    @endif
                                    {{-- Sekundäraktionen: Icon-only mit Tooltip --}}
                                    <form method="POST"
                                          action="{{ route('admin.newsletter.duplicate', $newsletter) }}"
                                          class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="ui small basic icon button"
                                                aria-label="Duplizieren"
                                                data-tooltip="Duplizieren"
                                                data-position="top center"
                                                data-inverted>
                                            <i class="copy icon" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('admin.newsletter.destroy', $newsletter) }}"
                                          class="inline"
                                          data-confirm="Newsletter &quot;{{ $newsletter->title }}&quot; wirklich löschen?">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="ui small red basic icon button"
                                                aria-label="Löschen"
                                                data-tooltip="Löschen"
                                                data-position="top center"
                                                data-inverted>
                                            <i class="trash icon" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-stone-400">
                                    Noch kein Newsletter vorhanden. <a href="{{ route('admin.newsletter.create') }}" class="underline text-waldritter">Ersten Newsletter erstellen</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Karten (< sm) --}}
            <div class="sm:hidden space-y-3">
                @forelse ($newsletters as $newsletter)
                    @php [$badge, $label] = $newsletter->statusBadge() @endphp
                    <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-medium text-stone-800">{{ $newsletter->title }}</span>
                            <span class="ui {{ $badge }} label shrink-0">{{ $label }}</span>
                        </div>
                        <div class="text-sm text-stone-500 flex gap-4">
                            <span>
                                <i class="users icon"></i>
                                @if ($newsletter->isSent())
                                    {{ $newsletter->sends_count }} Empfänger
                                @else
                                    {{ $activeSubscribers }} Abonnenten
                                @endif
                            </span>
                            @if ($newsletter->sent_at)
                                <span><i class="calendar icon"></i> {{ $newsletter->sent_at->locale('de')->isoFormat('D. MMM YYYY') }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            {{-- Primäraktion --}}
                            @if ($newsletter->isDraft())
                                <a href="{{ route('admin.newsletter.edit', $newsletter) }}"
                                   class="ui small primary button">
                                    <i class="edit icon"></i> Bearbeiten
                                </a>
                            @else
                                <a href="{{ route('admin.newsletter.show', $newsletter) }}"
                                   class="ui small button">
                                    <i class="eye icon"></i> Anzeigen
                                </a>
                            @endif
                            {{-- Sekundäraktionen --}}
                            <div class="flex gap-1 ml-auto">
                                <form method="POST"
                                      action="{{ route('admin.newsletter.duplicate', $newsletter) }}">
                                    @csrf
                                    <button type="submit" class="ui small basic icon button" aria-label="Duplizieren">
                                        <i class="copy icon" aria-hidden="true"></i>
                                    </button>
                                </form>
                                <form method="POST"
                                      action="{{ route('admin.newsletter.destroy', $newsletter) }}"
                                      data-confirm="Newsletter &quot;{{ $newsletter->title }}&quot; wirklich löschen?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ui small red basic icon button" aria-label="Löschen">
                                        <i class="trash icon" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-stone-400">
                        Noch kein Newsletter vorhanden.
                        <a href="{{ route('admin.newsletter.create') }}" class="block mt-2 underline text-waldritter">Ersten Newsletter erstellen</a>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $newsletters->links() }}</div>

        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Accordion wird ausschliesslich ueber den Accordion-Titel gesteuert
        window.$('#subscribers-accordion').accordion();
    });
    </script>
</x-app-layout>
