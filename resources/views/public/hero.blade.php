<x-public-layout :title="$hero->character_name . ' – Heldenregister'">

    <div class="max-w-2xl mx-auto px-4 sm:px-6">

        {{-- Helden-Header --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden mb-6">
            <div class="flex items-center gap-5 p-5">
                <img src="{{ $hero->image_url }}" alt="{{ $hero->character_name }}"
                     class="h-24 w-24 object-cover rounded border-2 border-[#5a3a22]/40 shrink-0">
                <div>
                    <h1 class="font-uncial text-2xl text-waldritter leading-tight">
                        {{ $hero->character_name }}
                    </h1>
                    @if ($hero->classes->isNotEmpty())
                        <p class="text-stone-600 mt-1">
                            {{ $hero->classes->pluck('name')->implode(', ') }}
                        </p>
                    @endif
                    @if ($hero->homeplace)
                        <p class="text-sm text-stone-500 mt-0.5">
                            <i class="map marker alternate icon"></i>{{ $hero->homeplace }}
                        </p>
                    @endif
                    <div class="mt-2">
                        @if ($hero->died)
                            <span class="ui tiny red label">verschollen</span>
                        @elseif ($hero->active)
                            <span class="ui tiny green label">aktiv</span>
                        @else
                            <span class="ui tiny label">inaktiv</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Steckbrief --}}
        @if ($hero->description)
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
                <h2 class="font-uncial text-lg text-waldritter mb-2">Steckbrief</h2>
                <p class="text-stone-700 whitespace-pre-line">{{ $hero->description }}</p>
            </div>
        @endif

        {{-- Fertigkeiten --}}
        @if ($hero->skills->isNotEmpty())
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
                <h2 class="font-uncial text-lg text-waldritter mb-3">Fertigkeiten</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($hero->skills as $skill)
                        <span class="ui label">{{ $skill->name }}
                            <span class="detail">{{ $skill->ep_costs }} EP</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Bändchen / Perlen --}}
        @if ($perlSummary->isNotEmpty())
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
                <h2 class="font-uncial text-lg text-waldritter mb-3">Bändchen &amp; Perlen</h2>
                <div class="flex flex-wrap gap-x-6 gap-y-2 text-stone-700">
                    @foreach ($perlSummary as $entry)
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-3.5 h-3.5 rounded-full border border-stone-300"
                                  style="background:{{ $entry->color->code }}"></span>
                            {{ $entry->color->name }}:
                            <strong>{{ $entry->count }}</strong>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Gruppen --}}
        @if ($hero->groups->isNotEmpty())
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6">
                <h2 class="font-uncial text-lg text-waldritter mb-3">Gruppen</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($hero->groups as $group)
                        <span class="ui label">
                            {{ $group->name }}
                            @if ($group->pivot->role)
                                <span class="detail">{{ $group->pivot->role }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Teilen --}}
        <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-5 mb-6 text-center">
            <p class="text-sm text-stone-600 mb-2">
                Helden-Code:
                <code class="font-mono tracking-widest text-waldritter text-base">{{ $hero->public_code }}</code>
            </p>
            <p class="text-xs text-stone-400 mb-3 break-all">{{ url()->current() }}</p>
            <button type="button"
                    class="ui small button"
                    onclick="navigator.clipboard.writeText('{{ url()->current() }}').then(function(){this.textContent='✓ Kopiert!';setTimeout(function(){document.querySelectorAll('[data-copy-btn]').forEach(function(b){b.textContent='Link kopieren'})},2000)}.bind(this))"
                    data-copy-btn>
                Link kopieren
            </button>
        </div>

    </div>

</x-public-layout>
