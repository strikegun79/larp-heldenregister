{{-- ARCH-003: Tabelle → Karten auf Mobile. Wrapper, der per CSS aus einer
     Standard-HTML-Tabelle eine Card-Liste macht. Tabellen-<td> sollten
     data-label="Spaltenname" tragen, damit das Label auf Mobile angezeigt wird. --}}
<div {{ $attributes->merge(['class' => 'mobile-cards-table-wrapper overflow-x-auto']) }}>
    {{ $slot }}
</div>
