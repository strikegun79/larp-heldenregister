<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                    {{ $survey->title }}
                </h2>
                <p class="text-sm text-stone-500 mt-0.5">
                    {{ $survey->adventure->name }}
                    &nbsp;·&nbsp;
                    <span class="ui {{ $survey->status === 'active' ? 'green' : ($survey->status === 'closed' ? 'grey' : 'yellow') }} label">
                        {{ $survey->status_label }}
                    </span>
                </p>
            </div>
            <div class="flex gap-2 flex-wrap">
                @if($completedCount > 0)
                    <a href="{{ route('admin.surveys.export.pdf', $survey) }}"
                       class="ui teal button">
                        <i class="file pdf icon"></i> PDF-Export
                    </a>
                @endif
                @can('survey.admin')
                    @if($survey->status !== 'closed')
                        <form method="POST" action="{{ route('admin.surveys.close', $survey) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="ui grey button"
                                    onclick="return confirm('Umfrage wirklich schließen?')">
                                Schließen
                            </button>
                        </form>
                    @endif
                @endcan
                <a href="{{ route('admin.surveys.index') }}" class="ui button">← Übersicht</a>
            </div>
        </div>
    </x-slot>

    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
                <div class="ui success message">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="ui error message">{{ session('error') }}</div>
            @endif

            {{-- ================================================================
                 ABSCHNITT 1: Rücklauf-Übersicht
                 ================================================================ --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-3">Rücklauf</h3>
                <div class="survey-result-card">
                    <div class="survey-result-card-body">
                        @php $pct = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0; @endphp
                        <div class="survey-rücklauf-bar mb-2">
                            <div class="survey-rücklauf-fill" style="width:{{ $pct }}%"></div>
                        </div>
                        <p class="text-sm text-stone-600">
                            <strong>{{ $completedCount }}</strong> von <strong>{{ $totalCount }}</strong>
                            Eingeladenen haben geantwortet ({{ $pct }} %)
                        </p>
                    </div>
                </div>
            </section>

            {{-- ================================================================
                 ABSCHNITT 2: Einladungsliste
                 ================================================================ --}}
            <section>
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <h3 class="font-uncial text-lg text-waldritter">Eingeladene Personen</h3>
                    @can('survey.admin')
                        @if($survey->status !== 'closed')
                            <form method="POST"
                                  action="{{ route('admin.surveys.import-bookings', $survey) }}">
                                @csrf
                                <button type="submit" class="ui primary button">
                                    <i class="download icon"></i>
                                    Teilnehmer aus Veranstaltung importieren
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>

                <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-black/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">E-Mail</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Zielgruppe</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-stone-500 uppercase">E-Mail gesendet</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-stone-500 uppercase">Geantwortet</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200">
                            @forelse($links as $link)
                                <tr class="{{ $link->isCompleted() ? 'bg-green-50/50' : '' }}">
                                    <td class="px-4 py-3 font-medium text-stone-800">
                                        {{ $link->name }}
                                    </td>
                                    <td class="px-4 py-3 text-stone-600">
                                        {{ $link->email }}
                                    </td>
                                    <td class="px-4 py-3 text-stone-500 text-xs">
                                        {{ \App\Models\SurveyTemplate::TARGET_GROUPS[$link->target_type] ?? $link->target_type }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($link->sent_at)
                                            <span class="ui green label" title="{{ $link->sent_at->format('d.m.Y H:i') }}">
                                                ✓ {{ $link->sent_at->format('d.m.Y') }}
                                            </span>
                                        @else
                                            <span class="ui grey label">Noch nicht</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($link->isCompleted())
                                            <span class="ui green label" title="{{ $link->completed_at->format('d.m.Y H:i') }}">
                                                ✓ {{ $link->completed_at->format('d.m.Y') }}
                                            </span>
                                        @elseif($link->isExpired())
                                            <span class="ui red label">Abgelaufen</span>
                                        @else
                                            <span class="ui yellow label">Ausstehend</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        {{-- Antworten anzeigen --}}
                                        @if($link->isCompleted())
                                            <a href="{{ route('admin.surveys.links.response', [$survey, $link]) }}"
                                               class="ui small teal button">
                                                <i class="eye icon"></i> Antworten
                                            </a>
                                        @endif

                                        {{-- E-Mail senden / erneut senden --}}
                                        @can('survey.admin')
                                            @if(! $link->isCompleted() && $survey->status !== 'closed')
                                                <form method="POST"
                                                      action="{{ route('admin.surveys.links.send', [$survey, $link]) }}"
                                                      class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="ui small {{ $link->sent_at ? 'grey' : 'primary' }} button"
                                                            title="{{ $link->sent_at ? 'Erneut senden' : 'Einladung senden' }}">
                                                        <i class="mail icon"></i>
                                                        {{ $link->sent_at ? 'Erneut' : 'Senden' }}
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Einladung löschen (nur unbeantwortete) --}}
                                            @if(! $link->isCompleted())
                                                <form method="POST"
                                                      action="{{ route('admin.surveys.links.destroy', [$survey, $link]) }}"
                                                      class="inline"
                                                      onsubmit="return confirm('Einladung von „{{ addslashes($link->name) }}" wirklich löschen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="ui small red icon button"
                                                            title="Einladung löschen">
                                                        <i class="trash icon"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-stone-400">
                                        Noch keine Personen eingeladen.
                                        @can('survey.admin')
                                            Nutze den Button „Teilnehmer importieren" oder füge Personen manuell hinzu.
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Manuell hinzufügen --}}
                @can('survey.admin')
                    @if($survey->status !== 'closed')
                        <details class="mt-3">
                            <summary class="text-sm text-stone-500 cursor-pointer hover:text-waldritter">
                                + Person manuell hinzufügen
                            </summary>
                            <div class="mt-3 bg-white/70 border border-stone-200 rounded-lg p-4">
                                <form method="POST"
                                      action="{{ route('admin.surveys.send', $survey) }}">
                                    @csrf
                                    {{-- Zielgruppe kommt vom Template der Umfrage, muss nicht nochmal gewählt werden --}}
                                    <p class="text-xs text-stone-400 mb-3">
                                        Zielgruppe: <strong>{{ $survey->template->target_group_label }}</strong>
                                        @if($survey->template->target_group === 'all')
                                            – bitte passende Gruppe pro Person wählen
                                        @endif
                                    </p>
                                    <div id="manual-recipients" class="space-y-3 mb-3"></div>
                                    <script>
                                    (function() {
                                        let i = 0;
                                        const isAll = @json($survey->template->target_group === 'all');
                                        const fixedType = @json($survey->template->target_group);
                                        const groups = @json(\App\Models\SurveyTemplate::TARGET_GROUPS);
                                        delete groups['all'];

                                        function addRow() {
                                            const j = i++;
                                            const targetField = isAll
                                                ? `<div><label class="text-xs text-stone-500">Zielgruppe</label>
                                                   <select name="recipients[${j}][target_type]" class="ui dropdown" required>
                                                     ${Object.entries(groups).map(([v,l]) => `<option value="${v}">${l}</option>`).join('')}
                                                   </select></div>`
                                                : `<input type="hidden" name="recipients[${j}][target_type]" value="${fixedType}">`;

                                            document.getElementById('manual-recipients').insertAdjacentHTML('beforeend', `
                                                <div class="flex flex-wrap gap-2 items-end">
                                                    ${targetField}
                                                    <div><label class="text-xs text-stone-500">Name</label>
                                                    <input type="text" name="recipients[${j}][name]" class="ui input" required></div>
                                                    <div><label class="text-xs text-stone-500">E-Mail</label>
                                                    <input type="email" name="recipients[${j}][email]" class="ui input" required></div>
                                                    <button type="button" onclick="this.closest('div.flex').remove()" class="ui red icon button"><i class="trash icon"></i></button>
                                                </div>`);
                                        }
                                        document.addEventListener('DOMContentLoaded', () => addRow());
                                        window.addManualRecipient = addRow;
                                    })();
                                    </script>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="addManualRecipient()" class="ui secondary small button">
                                            + Weitere Person
                                        </button>
                                        <button type="submit" class="ui primary small button">
                                            Einladung(en) versenden
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </details>
                    @endif
                @endcan
            </section>

            {{-- ================================================================
                 ABSCHNITT 3: Aggregierte Statistiken
                 ================================================================ --}}
            @if($completedCount > 0)
                <section>
                    <h3 class="font-uncial text-lg text-waldritter mb-3">Auswertung</h3>

                    @foreach($stats as $stat)
                        {{-- Issue #46: Bereich als Zwischenüberschrift --}}
                        @if(isset($stat['section']))
                            <div class="mt-6 mb-2">
                                <h4 class="font-semibold text-waldritter text-base">{{ $stat['number'] }}. {{ $stat['section']->question_text }}</h4>
                                @if($stat['section']->subtitle())
                                    <p class="text-sm text-stone-500">{{ $stat['section']->subtitle() }}</p>
                                @endif
                            </div>
                            @continue
                        @endif
                        <div class="survey-result-card">
                            <div class="survey-result-card-header">
                                {{ $stat['question']->typeIcon() }}
                                {{ $stat['question']->question_text }}
                                @if($stat['question']->isArchived())
                                    <span class="ui tiny grey label ml-2">entfernte Frage</span>
                                @endif
                                <span class="ml-auto text-xs opacity-70">{{ $stat['count'] }} Antworten</span>
                            </div>
                            <div class="survey-result-card-body">

                                @if($stat['question']->type === 'rating')
                                    <div class="flex items-end gap-6">
                                        <div>
                                            <div class="survey-avg-value">
                                                {{ $stat['average'] !== null ? number_format($stat['average'], 1) : '–' }}
                                            </div>
                                            <p class="text-xs text-stone-400">Ø von 10</p>
                                        </div>
                                        @if($stat['distribution']->isNotEmpty())
                                            @php $maxVal = $stat['distribution']->max() ?: 1; @endphp
                                            <div>
                                                <div class="survey-distribution-bar">
                                                    @for($v = 1; $v <= 10; $v++)
                                                        @php $cnt = $stat['distribution'][$v] ?? 0; @endphp
                                                        <div class="survey-dist-bar-item"
                                                             title="{{ $v }}: {{ $cnt }}x"
                                                             style="height:{{ $maxVal > 0 ? round(($cnt / $maxVal) * 100) : 0 }}%"></div>
                                                    @endfor
                                                </div>
                                                <div class="flex justify-between text-xs text-stone-400 mt-1">
                                                    <span>1</span><span>10</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <details class="mt-3">
                                        <summary class="text-xs text-stone-400 cursor-pointer">Datentabelle</summary>
                                        <table class="text-sm mt-2 w-full">
                                            <thead><tr>@for($v=1;$v<=10;$v++)<th class="text-center px-1 font-medium text-stone-500">{{ $v }}</th>@endfor</tr></thead>
                                            <tbody><tr>@for($v=1;$v<=10;$v++)<td class="text-center px-1">{{ $stat['distribution'][$v] ?? 0 }}</td>@endfor</tr></tbody>
                                        </table>
                                    </details>

                                @elseif($stat['question']->type === 'yes_no')
                                    @php
                                        $yes = $stat['yes_count']; $no = $stat['no_count'];
                                        $t = max($yes + $no, 1);
                                    @endphp
                                    <div class="flex gap-6">
                                        <div class="text-center">
                                            <div class="survey-avg-value" style="color:#5a9e4a;">{{ $yes }}</div>
                                            <p class="text-xs text-stone-400">Ja ({{ round($yes / $t * 100) }} %)</p>
                                        </div>
                                        <div class="text-center">
                                            <div class="survey-avg-value" style="color:#c06060;">{{ $no }}</div>
                                            <p class="text-xs text-stone-400">Nein ({{ round($no / $t * 100) }} %)</p>
                                        </div>
                                    </div>

                                @elseif($stat['question']->type === 'smiley')
                                    {{-- Issue #24: Smiley-Skala 1–5 --}}
                                    @php
                                        $avg = $stat['average'];
                                        $avgSmiley = $avg !== null ? \App\Models\SurveyTemplateQuestion::SMILEYS[(int) round($avg)] : '';
                                        $maxVal = max($stat['distribution']->max() ?? 0, 1);
                                    @endphp
                                    <div class="flex flex-wrap items-start gap-6">
                                        <div class="text-center">
                                            <div class="survey-avg-value">
                                                {{ $avgSmiley }} {{ $avg !== null ? number_format($avg, 1, ',', '') : '–' }}
                                            </div>
                                            <p class="text-xs text-stone-400">Ø von 5</p>
                                        </div>
                                        <table class="text-sm flex-1 min-w-[14rem]">
                                            <caption class="sr-only">Verteilung der Smiley-Antworten</caption>
                                            <tbody>
                                                @foreach($stat['question']->smileyLabels() as $points => $label)
                                                    @php $cnt = $stat['distribution'][$points] ?? 0; @endphp
                                                    <tr>
                                                        <th scope="row" class="text-left font-normal pr-3 py-0.5 whitespace-nowrap">
                                                            {{ \App\Models\SurveyTemplateQuestion::SMILEYS[$points] }} {{ $label }}
                                                        </th>
                                                        <td class="w-full py-0.5">
                                                            <div class="h-3 rounded bg-amber-500/70" style="width: {{ round($cnt / $maxVal * 100) }}%"></div>
                                                        </td>
                                                        <td class="text-right pl-3 py-0.5 tabular-nums">{{ $cnt }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    {{-- Issue #46: Zusatzantwort ohne Smiley, nicht im Durchschnitt --}}
                                    @if($stat['question']->hasNaOption() || $stat['na_count'] > 0)
                                        <p class="text-sm text-stone-600 mt-2">
                                            {{ $stat['question']->naLabel() }}: <strong class="tabular-nums">{{ $stat['na_count'] }}</strong>
                                            <span class="text-xs text-stone-400">(nicht im Durchschnitt)</span>
                                        </p>
                                    @endif

                                @elseif($stat['question']->type === 'choice')
                                    {{-- Issue #24: Optionen mit Freitext --}}
                                    @php $base = max($stat['choice_respondents'], 1); @endphp
                                    <table class="text-sm w-full">
                                        <caption class="sr-only">Anzahl je Antwortoption</caption>
                                        <tbody>
                                            @foreach($stat['choice_counts'] as $choice => $cnt)
                                                <tr>
                                                    <th scope="row" class="text-left font-normal pr-3 py-0.5 whitespace-nowrap">{{ $choice }}</th>
                                                    <td class="w-full py-0.5">
                                                        <div class="h-3 rounded bg-green-600/60" style="width: {{ round($cnt / $base * 100) }}%"></div>
                                                    </td>
                                                    <td class="text-right pl-3 py-0.5 tabular-nums whitespace-nowrap">{{ $cnt }} ({{ round($cnt / $base * 100) }} %)</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if($stat['question']->allowsMultiple())
                                        <p class="text-xs text-stone-400 mt-1">Mehrfachauswahl – Anteile bezogen auf {{ $stat['choice_respondents'] }} Personen mit Auswahl.</p>
                                    @endif

                                    @if($stat['question']->hasFreeText() || $stat['texts']->isNotEmpty())
                                        <h4 class="text-xs font-semibold text-stone-500 uppercase mt-4 mb-2">Nachrichten</h4>
                                        @can('survey.admin')
                                            @forelse($stat['texts'] as $text)
                                                <blockquote class="survey-text-quote mb-2">{{ $text }}</blockquote>
                                            @empty
                                                <p class="text-sm text-stone-400">Keine Nachrichten.</p>
                                            @endforelse
                                        @else
                                            <p class="text-sm text-stone-400 italic">
                                                Freitext-Antworten sind nur für Umfrage-Admins einsehbar.
                                            </p>
                                        @endcan
                                    @endif

                                @elseif($stat['question']->type === 'text')
                                    @can('survey.admin')
                                        @if($stat['texts']->isNotEmpty())
                                            <div class="space-y-2">
                                                @foreach($stat['texts'] as $text)
                                                    <blockquote class="survey-text-quote">{{ $text }}</blockquote>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-stone-400">Keine Freitext-Antworten.</p>
                                        @endif
                                    @else
                                        <p class="text-sm text-stone-400 italic">
                                            Freitext-Antworten sind nur für Umfrage-Admins einsehbar.
                                        </p>
                                    @endcan
                                @endif

                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
