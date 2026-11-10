{{--
    WYSIWYG-Editor für den Helden-Steckbrief.
    Parameter: $id (Textarea-ID), $value (HTML-Startwert).
--}}
@include('partials._rich_editor', [
    'id'      => $id,
    'name'    => 'description',
    'value'   => $value,
    'maxText' => \App\Models\Hero::DESCRIPTION_MAX_TEXT,
])
