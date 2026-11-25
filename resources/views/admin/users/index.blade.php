<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Portal-Nutzer</h2>
            <a href="{{ route('admin.users.create') }}"
               data-inspector-new="{{ route('admin.users.create') }}"
               class="ui primary button">Nutzer einladen</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Suche und Filter: filtern beim Tippen/Ändern automatisch (data-auto-filter), ohne JS per Enter --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('admin.users.index') }}" class="ui form"
                      data-auto-filter="#user-results" role="search" aria-label="Nutzer filtern">
                    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3">
                        <div class="ui action input w-full sm:w-auto">
                            <label for="user-search" class="sr-only">Name oder E-Mail suchen</label>
                            <input type="search" id="user-search" name="q" value="{{ $q }}"
                                   placeholder="Name oder E-Mail suchen…"
                                   autocomplete="off" autofocus>
                            <button type="submit" class="ui icon button" aria-label="Suchen">
                                <i class="search icon"></i>
                            </button>
                        </div>
                        <div class="w-full sm:w-56">
                            <label for="user-role" class="sr-only">Rolle</label>
                            <select id="user-role" name="role" class="w-full">
                                <option value="">Alle Rollen</option>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->id }}" @selected($role === (string) $r->id)>{{ $r->label }}</option>
                                @endforeach
                                <option value="none" @selected($role === 'none')>Ohne Rolle</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="user-deleted" name="deleted" value="1"
                                   class="rounded border-gray-300 text-amber-700 focus:ring-amber-600"
                                   @checked($showDeleted)>
                            <label for="user-deleted" class="cursor-pointer">Gelöschte Nutzer anzeigen</label>
                        </div>
                        <a href="{{ route('admin.users.index') }}" data-auto-filter-reset
                           class="text-sm text-stone-500 hover:underline shrink-0 {{ $filtered ? '' : 'hidden' }}">
                            Filter zurücksetzen
                        </a>
                    </div>
                </form>
            </div>

            {{-- Ergebnisliste: wird beim automatischen Filtern ausgetauscht --}}
            <div id="user-results">
                @include('admin.users._results')
            </div>
            <br>
            <a href="{{ route('admin.index') }}">
                <x-primary-button>Zurück zur Verwaltung</x-primary-button>
            </a>
        </div>
    </div>
</x-app-layout>
