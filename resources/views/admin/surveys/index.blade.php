<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">Umfragen</h2>
            @can('survey.admin')
                <div class="flex gap-2">
                    <a href="{{ route('admin.surveys.templates.index') }}" class="ui secondary button">
                        Vorlagen verwalten
                    </a>
                    <a href="{{ route('admin.surveys.create') }}" class="ui primary button">
                        + Neue Umfrage
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="ui success message mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="ui error message mb-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Veranstaltung</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Titel</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Zielgruppe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rücklauf</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @forelse($surveys as $survey)
                            @php
                                $pct = $survey->links_count > 0
                                    ? round(($survey->completed_count / $survey->links_count) * 100)
                                    : 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm" data-label="Veranstaltung">{{ $survey->adventure->name }}</td>
                                <td class="px-4 py-3 font-medium" data-label="Titel">{{ $survey->title }}</td>
                                <td class="px-4 py-3 text-sm text-stone-500" data-label="Zielgruppe">
                                    {{ $survey->template->target_group_label }}
                                </td>
                                <td class="px-4 py-3" data-label="Status">
                                    @php
                                        $cls = match($survey->status) {
                                            'active' => 'green',
                                            'closed' => 'grey',
                                            default  => 'yellow',
                                        };
                                    @endphp
                                    <span class="ui {{ $cls }} label">{{ $survey->status_label }}</span>
                                </td>
                                <td class="px-4 py-3" data-label="Rücklauf">
                                    <div class="survey-rücklauf-bar" style="width:120px;">
                                        <div class="survey-rücklauf-fill" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <p class="text-xs text-stone-500 mt-0.5">
                                        {{ $survey->completed_count }} / {{ $survey->links_count }}
                                        ({{ $pct }} %)
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.surveys.results', $survey) }}"
                                       class="ui small button">Ergebnisse</a>
                                    @can('survey.admin')
                                        @if($survey->status !== 'closed')
                                            <form method="POST"
                                                  action="{{ route('admin.surveys.close', $survey) }}"
                                                  class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                        class="ui small grey button"
                                                        onclick="return confirm('Umfrage wirklich schließen?')">
                                                    Schließen
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-stone-400">
                                    Noch keine Umfragen vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </x-mobile.cards-or-table>
            </div>

            <div class="mt-4">{{ $surveys->links() }}</div>

        </div>
    </div>

    <link rel="stylesheet" href="{{ asset('css/survey.css') }}">
</x-app-layout>
