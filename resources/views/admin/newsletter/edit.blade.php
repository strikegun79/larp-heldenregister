<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Newsletter bearbeiten</h2>
            <a href="{{ route('admin.newsletter.index') }}" class="ui basic button">
                <i class="arrow left icon"></i> Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="ui success message mb-4">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="ui error message mb-4">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.newsletter.update', $newsletter) }}" id="newsletter-form">
                @csrf @method('PATCH')

                {{-- Betreff --}}
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-6 mb-4">
                    <x-input-label for="title" value="Betreff" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                  :value="old('title', $newsletter->title)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                {{-- Editor --}}
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-6 mb-4">
                    <x-input-label for="body_html" value="Inhalt" />
                    <div class="mt-2">
                        <textarea id="body_html" name="body_html">{{ old('body_html', $newsletter->body_html) }}</textarea>
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('body_html')" />
                    <p id="editor-save-status" class="text-xs text-stone-400 mt-2 h-4"></p>
                </div>

                {{-- Aktionsleiste --}}
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-stone-600">
                        <i class="users icon"></i>
                        <strong>{{ $activeSubscribers }}</strong> aktive Abonnenten würden diesen Newsletter erhalten
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('admin.newsletter.index') }}" class="ui basic button">Abbrechen</a>
                        <button type="submit" class="ui primary button">
                            <i class="save icon"></i> Speichern
                        </button>
                        @if ($activeSubscribers > 0)
                            <button type="button" class="ui green button" id="open-send-modal">
                                <i class="paper plane icon"></i> Versenden
                            </button>
                        @endif
                    </div>
                </div>
            </form>

        </div>
    </div>

    {{-- Versand-Bestätigungs-Modal --}}
    <div class="ui modal" id="send-confirm-modal">
        <div class="header">
            <i class="paper plane icon"></i> Newsletter wirklich versenden?
        </div>
        <div class="content">
            <p class="text-stone-700 mb-3">
                Du bist dabei, <strong>{{ $newsletter->title }}</strong> an
                <strong>{{ $activeSubscribers }} Abonnenten</strong> zu versenden.
            </p>
            <div class="ui warning message">
                <i class="exclamation triangle icon"></i>
                Diese Aktion kann <strong>nicht rückgängig gemacht</strong> werden.
                Bitte prüfe den Inhalt und die Empfänger nochmals sorgfältig.
            </div>
            <div class="mt-4">
                <label class="block text-sm font-semibold text-stone-700 mb-1">
                    Tippe <code class="bg-stone-100 px-1 rounded">VERSENDEN</code> zur Bestätigung:
                </label>
                <input type="text" id="confirm-word-input" autocomplete="off"
                       class="border border-stone-300 rounded px-3 py-2 w-full text-sm"
                       placeholder="VERSENDEN">
            </div>
        </div>
        <div class="actions">
            <button class="ui cancel basic button">
                <i class="times icon"></i> Abbrechen
            </button>
            <form method="POST"
                  action="{{ route('admin.newsletter.send', $newsletter) }}"
                  id="send-confirm-form">
                @csrf
                <input type="hidden" name="confirm_word" id="confirm-word-hidden">
                <button type="submit" id="send-confirm-btn"
                        class="ui green button" disabled>
                    <i class="paper plane icon"></i> Jetzt versenden
                </button>
            </form>
        </div>
    </div>

    @include('admin.newsletter._editor_scripts', ['subscriberCount' => $activeSubscribers])

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal      = window.$('#send-confirm-modal');
        var input      = document.getElementById('confirm-word-input');
        var hiddenWord = document.getElementById('confirm-word-hidden');
        var sendBtn    = document.getElementById('send-confirm-btn');
        var openBtn    = document.getElementById('open-send-modal');

        if (openBtn) {
            openBtn.addEventListener('click', function () {
                if (input) input.value = '';
                if (sendBtn) sendBtn.disabled = true;
                modal.modal({ closable: true }).modal('show');
            });
        }

        if (input) {
            input.addEventListener('input', function () {
                var match = input.value === 'VERSENDEN';
                sendBtn.disabled = !match;
                if (hiddenWord) hiddenWord.value = input.value;
            });
        }
    });
    </script>
</x-app-layout>
