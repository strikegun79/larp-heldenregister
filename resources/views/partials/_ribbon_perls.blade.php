{{--
    Wiederverwendbare Bändchen-&-Perlen-Anzeige, nach Klasse gruppiert.
    Erwartet: $perlByClass (Collection<{class: HeroClass, perls: Collection<{color, count}>}>)
              $compact (bool, optional) – true für die kompakte Mobile-Ansicht
--}}
@php($compact = $compact ?? false)

<div class="space-y-4">
    @foreach ($perlByClass as $entry)
        @php($ribbonUrl = $entry->class->ribbonImageUrl())
        <div class="flex items-start gap-3">

            {{-- Klassenband-Streifen --}}
            @if ($ribbonUrl)
                <img src="{{ $ribbonUrl }}"
                     alt="Klassenband {{ $entry->class->name }}"
                     title="{{ $entry->class->name }}"
                     class="object-cover rounded border border-stone-200 shrink-0"
                     style="height:{{ $compact ? '3.5rem' : '5rem' }}; width:auto; max-width:1.75rem">
            @elseif ($entry->class->ribbon_color)
                <div class="rounded shrink-0"
                     style="background:{{ $entry->class->ribbon_color }};
                            width:0.625rem;
                            height:{{ $compact ? '3.5rem' : '5rem' }};"
                     title="{{ $entry->class->name }}"></div>
            @endif

            {{-- Klasse + Perlen --}}
            <div class="min-w-0">
                <p class="text-xs font-semibold text-waldritter uppercase tracking-wide mb-1">
                    {{ $entry->class->name }}
                </p>

                @if ($compact)
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-stone-700">
                        @foreach ($entry->perls as $perl)
                            <span>
                                <span class="inline-block w-3 h-3 rounded-full mr-1 align-middle"
                                      style="background:{{ $perl->color->code }}"></span>
                                {{ $perl->color->name }}: <strong>{{ $perl->count }}</strong>
                            </span>
                        @endforeach
                    </div>
                @else
                    <table class="ui very basic compact table" style="max-width:18rem">
                        <thead>
                            <tr>
                                <th>Farbe</th>
                                <th class="right aligned">Anzahl</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entry->perls as $perl)
                                <tr>
                                    <td>
                                        <span class="inline-block w-3 h-3 rounded-full mr-1 align-middle"
                                              style="background:{{ $perl->color->code }}"></span>
                                        {{ $perl->color->name }}
                                    </td>
                                    <td class="right aligned font-semibold">{{ $perl->count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endforeach
</div>
