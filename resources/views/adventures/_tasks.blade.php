@php
    $definitions = \App\Models\TaskTypeDefinition::orderedByConfig()->filter(fn ($d) => $d->is_enabled);
    $categories  = config('adventure_tasks.categories', []);
    $tasksByType = $tasks->keyBy('task_type');

    $referenceOptions = ['start_at' => 'Beginn', 'end_at' => 'Ende'];
    $directionLabels  = ['before' => 'vorher', 'after' => 'nachher'];
@endphp

<form method="POST" action="{{ route('adventures.tasks.save', $adventure) }}" id="manage-tasks-form">
    @csrf
    @method('PUT')

    {{-- Sticky-Leiste: immer sichtbar, klar vom Haupt-Speichern unterscheidbar --}}
    <div class="sticky top-0 z-10 -mx-4 px-4 py-2 mb-4 bg-amber-50 border-b border-amber-200 flex items-center justify-between gap-3">
        <span class="text-sm text-amber-800 font-medium">
            <i class="clock icon"></i> Taskmanager-Einstellungen
        </span>
        <button type="submit" class="ui small teal button">
            <i class="save icon"></i> Aufgaben speichern
        </button>
    </div>

    @foreach ($categories as $catKey => $cat)
        @php
            $catDefs = $definitions->filter(fn ($d) => $d->getCategoryKey() === $catKey);
        @endphp
        @if ($catDefs->isNotEmpty())
            <div class="mb-6">
                <h4 class="ui small header text-stone-600 mb-3">
                    <i class="{{ $cat['icon'] }} icon"></i>
                    {{ $cat['label'] }}
                </h4>

                <div class="ui divided list">
                    @foreach ($catDefs as $def)
                        @php
                            $type  = $def->task_type;
                            $saved = $tasksByType->get($type);
                            $isActive  = $saved?->is_active ?? false;
                            $days      = $saved?->trigger_days      ?? $def->default_days;
                            $reference = $saved?->trigger_reference ?? $def->default_reference;
                            $direction = $saved?->trigger_direction ?? $def->default_direction;
                            $executedAt = $saved?->executed_at;
                            $result     = $saved?->result;

                            // Vorschau-Datum berechnen
                            $refDate = $adventure->{$reference};
                            $triggerDate = null;
                            if ($refDate) {
                                $triggerDate = $direction === 'before'
                                    ? $refDate->copy()->subDays($days)
                                    : $refDate->copy()->addDays($days);
                            }
                        @endphp

                        <div class="item py-3">
                            <div class="flex items-start gap-3 flex-wrap">

                                {{-- Checkbox --}}
                                <div class="ui checkbox mt-1" style="flex-shrink:0;">
                                    <input type="checkbox"
                                           id="task_{{ $type }}_active"
                                           name="tasks[{{ $type }}][is_active]"
                                           value="1"
                                           {{ $isActive ? 'checked' : '' }}
                                           onchange="toggleTaskRow('{{ $type }}', this.checked)">
                                    <label for="task_{{ $type }}_active"></label>
                                </div>

                                {{-- Icon + Label + Beschreibung --}}
                                <div style="flex: 1; min-width: 200px;">
                                    <div class="font-medium flex items-center gap-2">
                                        <i class="{{ $def->getIcon() }} icon text-stone-500"></i>
                                        {{ $def->label }}
                                        @if ($executedAt)
                                            <span class="ui mini green label">
                                                <i class="check icon"></i>
                                                Ausgeführt {{ $executedAt->format('d.m.Y') }}
                                            </span>
                                        @elseif ($isActive && $triggerDate)
                                            @if ($triggerDate->isPast())
                                                <span class="ui mini orange label" data-tooltip="Task ist fällig aber noch nicht ausgeführt" data-variation="mini">
                                                    <i class="clock icon"></i> Fällig
                                                </span>
                                            @else
                                                <span class="ui mini teal label">
                                                    <i class="calendar alternate outline icon"></i>
                                                    {{ $triggerDate->format('d.m.Y') }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="text-sm text-stone-500 mt-1">{{ $def->description }}</div>
                                    @if ($result && $executedAt)
                                        <div class="text-xs text-stone-400 mt-1">
                                            <i class="info circle icon"></i> {{ $result }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Konfiguration (Tage / Referenz / Richtung) --}}
                                <div id="task_{{ $type }}_config"
                                     class="flex flex-wrap items-center gap-2 text-sm"
                                     style="{{ $isActive ? '' : 'opacity:0.4; pointer-events:none;' }}">

                                    <div class="ui mini input" style="width:70px;">
                                        <input type="number"
                                               name="tasks[{{ $type }}][trigger_days]"
                                               value="{{ $days }}"
                                               min="0" max="999"
                                               style="text-align:center;">
                                    </div>
                                    <span class="text-stone-500">Tage</span>

                                    <select name="tasks[{{ $type }}][trigger_direction]"
                                            class="ui mini dropdown"
                                            style="min-width:90px; padding:.4em .6em; border:1px solid rgba(34,36,38,.15); border-radius:.28rem; background:#fff; cursor:pointer;">
                                        @foreach ($directionLabels as $val => $lbl)
                                            <option value="{{ $val }}" {{ $direction === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>

                                    <select name="tasks[{{ $type }}][trigger_reference]"
                                            class="ui mini dropdown"
                                            style="min-width:80px; padding:.4em .6em; border:1px solid rgba(34,36,38,.15); border-radius:.28rem; background:#fff; cursor:pointer;">
                                        @foreach ($referenceOptions as $val => $lbl)
                                            <option value="{{ $val }}" {{ $reference === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>

                                    @if ($triggerDate)
                                        <span class="text-stone-400 text-xs">
                                            → {{ $triggerDate->format('d.m.Y') }}
                                        </span>
                                    @elseif (! $adventure->start_at)
                                        <span class="text-amber-500 text-xs">
                                            <i class="exclamation triangle icon"></i> Kein Datum gesetzt
                                        </span>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

</form>

<script>
function toggleTaskRow(type, active) {
    var config = document.getElementById('task_' + type + '_config');
    if (!config) return;
    config.style.opacity = active ? '1' : '0.4';
    config.style.pointerEvents = active ? '' : 'none';
}
</script>
