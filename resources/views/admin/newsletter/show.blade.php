<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">{{ $newsletter->title }}</h2>
                @php [$badge, $label] = $newsletter->statusBadge(); @endphp
                <span class="ui {{ $badge }} label mt-1">{{ $label }}</span>
            </div>
            <div class="flex gap-2 flex-wrap items-center">
                {{-- Primäre Navigation --}}
                <a href="{{ route('admin.newsletter.index') }}" class="ui basic button">
                    <i class="arrow left icon"></i> Zurück
                </a>
                @if ($newsletter->isDraft())
                    <a href="{{ route('admin.newsletter.edit', $newsletter) }}" class="ui primary button">
                        <i class="edit icon"></i> Bearbeiten
                    </a>
                @endif
                {{-- Objektaktionen visuell getrennt (UX-120 / UX-121) --}}
                <div class="flex gap-2 items-center ml-1 pl-3 border-l border-stone-300/70">
                    <form method="POST" action="{{ route('admin.newsletter.duplicate', $newsletter) }}" style="display:inline">
                        @csrf
                        <button type="submit"
                                class="ui basic icon button"
                                aria-label="Duplizieren"
                                data-tooltip="Duplizieren"
                                data-position="top center"
                                data-inverted>
                            <i class="copy icon" aria-hidden="true"></i>
                        </button>
                    </form>
                    <form method="POST"
                          action="{{ route('admin.newsletter.destroy', $newsletter) }}"
                          style="display:inline"
                          data-confirm="Newsletter &quot;{{ $newsletter->title }}&quot; wirklich löschen?">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="ui red basic icon button"
                                aria-label="Löschen"
                                data-tooltip="Löschen"
                                data-position="top center"
                                data-inverted>
                            <i class="trash icon" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Metadaten --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-5">
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-stone-500 font-medium uppercase tracking-wide text-xs mb-1">Betreff</dt>
                        <dd class="text-stone-800 font-semibold">{{ $newsletter->title }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500 font-medium uppercase tracking-wide text-xs mb-1">Empfänger</dt>
                        <dd class="text-stone-800">
                            @if ($newsletter->isSent())
                                <i class="users icon text-stone-400"></i> {{ $sendsCount }} Empfänger
                            @else
                                <span class="text-stone-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-stone-500 font-medium uppercase tracking-wide text-xs mb-1">
                            {{ $newsletter->isSent() ? 'Versendet am' : 'Erstellt am' }}
                        </dt>
                        <dd class="text-stone-800">
                            {{ ($newsletter->sent_at ?? $newsletter->created_at)->locale('de')->isoFormat('D. MMMM YYYY, HH:mm') }} Uhr
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Readonly-Vorschau --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-5">
                <h3 class="text-xs font-medium text-stone-500 uppercase tracking-wide mb-4">Inhalt (Readonly)</h3>
                <div class="prose max-w-none text-stone-800 border border-stone-200 rounded p-4 bg-white newsletter-preview">
                    {!! $newsletter->body_html !!}
                </div>
            </div>

        </div>
    </div>

    <style>
        .newsletter-preview img   { max-width: 100%; height: auto; }
        .newsletter-preview table { border-collapse: collapse; width: 100%; }
        .newsletter-preview td,
        .newsletter-preview th    { border: 1px solid #d1d5db; padding: 6px 10px; }
        .newsletter-preview h2    { font-size: 1.4rem; font-weight: 700; margin: 1rem 0 .5rem; }
        .newsletter-preview h3    { font-size: 1.15rem; font-weight: 600; margin: 1rem 0 .4rem; }
        .newsletter-preview p     { margin: .5rem 0; }
        .newsletter-preview ul,
        .newsletter-preview ol    { padding-left: 1.5rem; margin: .5rem 0; }
        .newsletter-preview blockquote { border-left: 3px solid #d4a843; padding-left: 1rem; color: #6b7280; margin: .75rem 0; }
        .newsletter-preview a     { color: #2d5a27; text-decoration: underline; }
    </style>
</x-app-layout>
