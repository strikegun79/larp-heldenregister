@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 focus:border-amber-600 focus:ring-amber-600 rounded-md shadow-sm']) !!}>
