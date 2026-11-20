<span data-modal-title hidden>Held bearbeiten: {{ $hero->character_name }}</span>

{{-- Waldritter v1: eigener Wrapper, weil das Formular im globalen Modal lädt --}}
<div class="wr-v1">
<form id="hero-edit-form" method="POST" action="{{ route('heroes.update', $hero) }}">
    @method('PUT')
    @include('heroes._form', ['hideFormActions' => true])
</form>

{{-- Klassen-Sektion (HERO-06): Entfernen erstattet EP, Hinzufügen kostet EP (Korrektur ohne Abzug) --}}
@can('heldenregister.edit')
@php($availableClassesEdit = $classes->whereNotIn('id', $hero->classes->pluck('id')))
<section class="wr-fieldset mt-5" aria-labelledby="hero-classes-heading-{{ $hero->id }}">
    <h3 id="hero-classes-heading-{{ $hero->id }}" class="wr-section-heading__title text-lg mb-3">Klassen</h3>

    {{-- Aktuelle Klassen mit Entfernen-Button --}}
    <ul class="flex flex-wrap gap-2 mb-4 p-0 list-none" role="list">
        @forelse ($hero->classes as $class)
            <li class="inline-flex items-center gap-1">
                <x-wr.class-badge :hero-class="$class" />
                <form method="POST" action="{{ route('heroes.classes.destroy', [$hero, $class]) }}" data-refresh-modal class="inline"
                      data-confirm="Klasse „{{ $class->name }}“ entfernen? {{ $class->ep_cost }} EP werden erstattet.">
                    @csrf @method('DELETE')
                    <button type="submit" class="wr-btn wr-btn--danger wr-btn--small !px-2.5" aria-label="Klasse {{ $class->name }} entfernen">
                        <i class="times icon m-0" aria-hidden="true"></i>
                    </button>
                </form>
            </li>
        @empty
            <li class="text-wr-ink-muted text-sm">Keine Klassen zugewiesen.</li>
        @endforelse
    </ul>

    {{-- Klasse hinzufügen --}}
    @if ($availableClassesEdit->isNotEmpty())
        <form method="POST" action="{{ route('heroes.classes.store', $hero) }}" data-refresh-modal class="ui form mb-3"
              data-confirm="Sollen die EP-Kosten wirklich abgezogen werden?"
              data-confirm-unless-id="class-free-edit-{{ $hero->id }}"
              data-confirm-unless-val="1">
            @csrf
            <input type="hidden" name="free" id="class-free-edit-{{ $hero->id }}" value="0">
            <div class="flex items-end gap-2 flex-wrap">
                <div class="field !mb-0">
                    <label for="hero-class-add-{{ $hero->id }}">Klasse hinzufügen</label>
                    <select id="hero-class-add-{{ $hero->id }}" name="hero_class_id" required>
                        @foreach ($availableClassesEdit as $class)
                            <option value="{{ $class->id }}">{{ $class->name }} (−{{ $class->ep_cost }} EP)</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="wr-btn wr-btn--primary"
                        onclick="document.getElementById('class-free-edit-{{ $hero->id }}').value='0'">Hinzufügen</button>
                <button type="submit" class="wr-btn wr-btn--secondary"
                        onclick="document.getElementById('class-free-edit-{{ $hero->id }}').value='1'">Korrektur (0 EP)</button>
            </div>
            <small class="wr-help">„Korrektur“ fügt eine versehentlich entfernte Klasse ohne EP-Abzug wieder hinzu.</small>
        </form>
    @else
        <p class="text-wr-ink-muted text-sm mb-3">Alle verfügbaren Klassen sind bereits zugewiesen.</p>
    @endif
</section>
@endcan
</div>

<div data-modal-actions hidden>
    <button type="submit" form="hero-edit-form" class="wr-btn wr-btn--primary">Speichern</button>
</div>
