<span data-modal-title hidden>Held bearbeiten: {{ $hero->character_name }}</span>

<form id="hero-edit-form" method="POST" action="{{ route('heroes.update', $hero) }}">
    @method('PUT')
    @include('heroes._form', ['hideFormActions' => true])
</form>

{{-- Klassen-Sektion --}}
@can('heldenregister.edit')
@php($availableClassesEdit = $classes->whereNotIn('id', $hero->classes->pluck('id')))
<div class="ui segment" style="margin-top:1rem;">
    <h4 class="font-uncial text-waldritter mb-3">Klassen</h4>

    {{-- Aktuelle Klassen mit Entfernen-Button --}}
    <div class="flex flex-wrap gap-2 mb-3">
        @forelse ($hero->classes as $class)
            <span class="ui label">
                {{ $class->name }}
                <form method="POST" action="{{ route('heroes.classes.destroy', [$hero, $class]) }}" data-refresh-modal class="inline"
                      data-confirm="Klasse „{{ $class->name }}" entfernen? {{ $class->ep_cost }} EP werden erstattet.">
                    @csrf @method('DELETE')
                    <button type="submit" class="ml-1 text-red-600" title="Entfernen">&times;</button>
                </form>
            </span>
        @empty
            <span class="text-stone-500 text-sm">Keine Klassen zugewiesen.</span>
        @endforelse
    </div>

    {{-- Klasse hinzufügen --}}
    @if ($availableClassesEdit->isNotEmpty())
        <form method="POST" action="{{ route('heroes.classes.store', $hero) }}" data-refresh-modal class="ui form"
              data-confirm="Sollen die EP-Kosten wirklich abgezogen werden?"
              data-confirm-unless-id="class-free-edit-{{ $hero->id }}"
              data-confirm-unless-val="1">
            @csrf
            <input type="hidden" name="free" id="class-free-edit-{{ $hero->id }}" value="0">
            <div class="flex items-end gap-2 flex-wrap">
                <div class="field !mb-0">
                    <select name="hero_class_id" required>
                        @foreach ($availableClassesEdit as $class)
                            <option value="{{ $class->id }}">{{ $class->name }} (−{{ $class->ep_cost }} EP)</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="ui primary button"
                        onclick="document.getElementById('class-free-edit-{{ $hero->id }}').value='0'">Hinzufügen</button>
                <button type="submit" class="ui basic button"
                        onclick="document.getElementById('class-free-edit-{{ $hero->id }}').value='1'"
                        title="Versehentlich entfernt? Ohne EP-Abzug wieder hinzufügen">Korrektur (0 EP)</button>
            </div>
        </form>
    @else
        <p class="text-stone-500 text-sm">Alle verfügbaren Klassen sind bereits zugewiesen.</p>
    @endif
</div>
@endcan

<div data-modal-actions hidden>
    <button type="submit" form="hero-edit-form" class="ui primary button">Speichern</button>
</div>
