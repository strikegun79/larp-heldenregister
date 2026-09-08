<x-mail::message>
{!! $bodyHtml !!}

---

<x-mail::subcopy>
Du erhältst diese E-Mail, weil du den Newsletter der Waldritter Gießen abonniert hast.
[Newsletter abbestellen]({!! $unsubscribeUrl !!})
</x-mail::subcopy>
</x-mail::message>
