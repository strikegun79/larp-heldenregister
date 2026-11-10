<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Neuer Newsletter</h2>
            <a href="{{ route('admin.newsletter.index') }}" class="ui basic button">
                <i class="arrow left icon"></i> Zurück
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('admin.newsletter.store') }}" id="newsletter-form">
                @csrf

                {{-- Betreff --}}
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-6 mb-4">
                    <x-input-label for="title" value="Betreff" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                  :value="old('title')" required
                                  placeholder="z.B. Neuigkeiten vom Sommerlager 2026" />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                {{-- Editor --}}
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-6 mb-4">
                    <x-input-label for="body_html" value="Inhalt" class="mb-2" />
                    <textarea id="body_html" name="body_html">{!! old('body_html') !!}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('body_html')" />
                    <p id="editor-save-status" class="text-xs text-stone-400 mt-2 h-4"></p>
                </div>

                {{-- Aktionsleiste --}}
                <div class="bg-white/70 border-2 border-[#5a3a22]/40 rounded-lg p-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-stone-600">
                        <i class="users icon"></i>
                        {{ $activeSubscribers }} aktive Abonnenten
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.newsletter.index') }}" class="ui basic button">Abbrechen</a>
                        <button type="submit" class="ui primary button">
                            <i class="save icon"></i> Als Entwurf speichern
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    @include('admin.newsletter._editor_scripts', ['subscriberCount' => $activeSubscribers])
</x-app-layout>
