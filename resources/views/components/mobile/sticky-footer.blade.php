{{-- ARCH-003: Sticky-Aktions-Footer. Auf Mobile fixiert am unteren Rand,
     auf Desktop inline (normaler Dokumentenfluss). CSS in heldenregister.css. --}}
<div {{ $attributes->merge(['class' => 'sticky-page-footer flex items-center gap-3 flex-wrap']) }}>
    {{ $slot }}
</div>
