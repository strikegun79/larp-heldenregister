{{-- Waldritter v1: Hinweis bei offenem Beitrag, bis wann Zahlungseingänge geprüft sind (Issue #5).
     Erklärt, warum eine frische Überweisung noch als „offen“ angezeigt wird. --}}
@props(['date'])

<p {{ $attributes->merge(['class' => 'wr-help m-0 flex items-start gap-1.5']) }}>
    <i class="info circle icon m-0 mt-0.5" aria-hidden="true"></i>
    <span>
        Zahlungseingänge zuletzt geprüft am {{ $date->format('d.m.Y') }}.
        Später überwiesene Beiträge erscheinen nach dem nächsten Abgleich.
    </span>
</p>
