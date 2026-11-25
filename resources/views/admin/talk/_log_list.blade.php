{{-- Talk-Protokoll als Liste. Erwartet: $logs (TalkLog-Einträge), optional $showContext (Veranstaltung/Spieler anzeigen) --}}
<ul class="divide-y divide-stone-200 border border-stone-200 rounded-md bg-white/60 text-sm">
    @forelse ($logs as $log)
        <li class="px-3 py-2">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                @if ($log->success)
                    <span class="text-green-800 shrink-0"><i class="check circle icon" aria-hidden="true"></i><span class="sr-only">OK:</span></span>
                @else
                    <span class="text-red-700 shrink-0"><i class="times circle icon" aria-hidden="true"></i><span class="sr-only">Fehler:</span></span>
                @endif
                <time class="text-stone-500 tabular-nums shrink-0" datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('d.m.Y H:i:s') }}</time>
                <span class="font-medium text-stone-800">{{ $log->action }}</span>
                @if ($showContext ?? false)
                    @if ($log->adventure)
                        <span class="text-stone-500">· {{ $log->adventure->name }}</span>
                    @endif
                    @if ($log->player)
                        <span class="text-stone-500">· {{ trim($log->player->name.' '.$log->player->lastname) }}</span>
                    @endif
                @endif
                <span class="text-stone-400 text-xs ml-auto">
                    {{ $log->user ? trim($log->user->name.' '.$log->user->lastname) : 'automatisch' }}
                    @if ($log->status) · HTTP {{ $log->status }} @endif
                </span>
            </div>
            @if (! $log->success && $log->message)
                <p class="text-red-700 mt-1 mb-0">{{ $log->message }}</p>
            @endif
        </li>
    @empty
        <li class="px-3 py-3 text-stone-500">Keine Einträge.</li>
    @endforelse
</ul>
