{{-- Gefahrenbereich „Löschen“ am Ende einer Bearbeiten-Ansicht – nur im Inspektor (?inspector=1),
     nicht im bisherigen Modal. Ist Löschen gesperrt, steht der Grund sichtbar da.
     Das Formular wird im Inspektor per AJAX gesendet und schließt ihn bei Erfolg. --}}
@props([
    'action',
    'confirm',
    'title' => 'Löschen',
    'blocked' => null,
    'hint' => null,
])

@if (request()->boolean('inspector'))
    <section class="wr-danger-zone" aria-label="{{ $title }}">
        <h3 class="wr-danger-zone__title">{{ $title }}</h3>
        @if ($blocked)
            <p class="text-sm m-0 flex items-start gap-1.5"><i class="lock icon m-0 mt-0.5" aria-hidden="true"></i> {{ $blocked }}</p>
        @else
            @if ($hint)
                <p class="text-sm mb-2">{{ $hint }}</p>
            @endif
            <form method="POST" action="{{ $action }}" data-inspector-form data-inspector-close-on-success data-confirm="{{ $confirm }}">
                @csrf @method('DELETE')
                <button type="submit" class="wr-btn wr-btn--danger wr-btn--small">
                    <i class="trash icon m-0" aria-hidden="true"></i> {{ $title }}
                </button>
            </form>
        @endif
    </section>
@endif
