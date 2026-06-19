@props([
    'name',
    'value'    => '',
    'type'     => 'date',     // 'date' | 'datetime'
    'required' => false,
])
@php
    $placeholder = $type === 'datetime' ? 'TT.MM.JJJJ HH:mm' : 'TT.MM.JJJJ';
@endphp

<div class="ui calendar mt-1" data-cal-type="{{ $type }}" data-initial="{{ $value }}">
    <div class="relative">
        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-stone-400">
            <i class="calendar alternate outline icon" style="margin:0"></i>
        </span>
        <input type="text" placeholder="{{ $placeholder }}" autocomplete="off"
               class="block w-full border-gray-300 rounded-md shadow-sm focus:border-amber-600 focus:ring-amber-600 pl-9"
               @required($required)>
    </div>
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}">
</div>
