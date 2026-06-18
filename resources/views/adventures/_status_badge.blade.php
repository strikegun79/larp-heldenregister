@if ($status ?? null)
    <span class="inline-block rounded px-2 py-0.5 text-xs font-medium"
          style="background: {{ $status->color }}33; border: 1px solid {{ $status->color }};">
        {{ $status->description }}
    </span>
@else
    <span class="text-stone-500">—</span>
@endif
