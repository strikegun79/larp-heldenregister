{{-- Waldritter v1: Klassenkennzeichnung aus Klassenbanner und Klassenname.
     Die Klassenfarbe ist nur ein Akzent (linker Rand), nie die alleinige Information. --}}
@props(['heroClass'])

@php
    $bannerUrl = $heroClass->ribbonImageUrl();
@endphp

<span {{ $attributes->class(['wr-class-badge', 'wr-class--' . \Illuminate\Support\Str::slug($heroClass->name, '-', 'de')]) }}>
    @if ($bannerUrl)
        <img src="{{ $bannerUrl }}" alt="" aria-hidden="true" loading="lazy">
    @endif
    {{ $heroClass->name }}
</span>
