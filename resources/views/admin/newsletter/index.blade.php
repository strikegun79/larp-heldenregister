<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">Newsletter</h2>
                <p class="text-sm text-stone-500 mt-0.5">
                    <i class="users icon"></i> {{ $activeSubscribers }} aktive Abonnenten
                </p>
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
                            @php [$badge, $label] = statusBadge($newsletter->status) @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $newsletter->title }}</td>
                                <td class="px-4 py-3">
                                    <span class="ui {{ $badge }} label">{{ $label }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-stone-600">
                                    @if ($newsletter->isSent())
                                        {{ $newsletter->sends_count }}
                                    @else
                                        {{ $activeSubscribers }} <span class="text-stone-400">(aktuell)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-stone-500">
                                    {{ $newsletter->sent_at?->locale('de')->isoFormat('D. MMM YYYY') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($newsletter->isDraft())
                                        <a href="{{ route('admin.newsletter.edit', $newsletter) }}"
                                           class="ui small button">
                                            <i class="edit icon"></i> Bearbeiten
                                        </a>
                                    @endif
                                    <form method="POST"
                                          action="{{ route('admin.newsletter.duplicate', $newsletter) }}"
                                          class="inline">
                                        @csrf
                                        <button type="submit" class="ui small basic button">
                                            <i class="copy icon"></i> Duplizieren
                                        </button>
                                    </form>
                                    @if ($newsletter->isDraft())
                                        <form method="POST"
                                              action="{{ route('admin.newsletter.destroy', $newsletter) }}"
                                              class="inline"
                                              data-confirm="Newsletter &quot;{{ $newsletter->title }}&quot; wirklich löschen?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ui small red basic icon button">
                                                <i class="trash icon"></i>
                                            </button>
                                        </form>
                                    @endif
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
                    @php [$badge, $label] = statusBadge($newsletter->status) @endphp
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
                        <div class="flex flex-wrap gap-2 pt-1">
                            @if ($newsletter->isDraft())
                                <a href="{{ route('admin.newsletter.edit', $newsletter) }}"
                                   class="ui small button">
                                    <i class="edit icon"></i> Bearbeiten
                                </a>
                            @endif
                            <form method="POST"
                                  action="{{ route('admin.newsletter.duplicate', $newsletter) }}">
                                @csrf
                                <button type="submit" class="ui small basic button">
                                    <i class="copy icon"></i> Duplizieren
                                </button>
                            </form>
                            @if ($newsletter->isDraft())
                                <form method="POST"
                                      action="{{ route('admin.newsletter.destroy', $newsletter) }}"
                                      data-confirm="Newsletter &quot;{{ $newsletter->title }}&quot; wirklich löschen?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ui small red basic button">
                                        <i class="trash icon"></i> Löschen
                                    </button>
                                </form>
                            @endif
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
</x-app-layout>

@php
function statusBadge(string $status): array {
    return match($status) {
        'sent'      => ['grey',   'Versendet'],
        'scheduled' => ['blue',   'Geplant'],
        default     => ['yellow', 'Entwurf'],
    };
}
@endphp
