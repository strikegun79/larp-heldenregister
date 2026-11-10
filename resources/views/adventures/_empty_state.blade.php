<div class="p-8 text-center">
    <p class="font-medium text-stone-700 mb-1">Keine Abenteuer geplant.</p>
    <p class="text-sm text-stone-500 mb-4">Sobald ein Abenteuer veröffentlicht wird, erscheint es hier.</p>
    @can('events.edit')
        <a href="{{ route('adventures.manage-index') }}" class="ui small primary button">Zur Verwaltung</a>
    @endcan
</div>
