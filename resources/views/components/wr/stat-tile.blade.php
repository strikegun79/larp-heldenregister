{{-- Waldritter v1: Kennzahl-Kachel für Verwaltungsübersichten (ruhig, wenig Dekor). --}}
@props([
    'label',
    'value',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'wr-stat']) }}>
    <span class="wr-stat__value">
        @if ($icon)
            <i class="{{ $icon }} icon" aria-hidden="true"></i>
        @endif
        {{ $value }}
    </span>
    <span class="wr-stat__label">{{ $label }}</span>
</div>
