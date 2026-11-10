<x-mail::message>
# Hallo {{ $link->name }}!

Das Abenteuer **{{ $adventure->name }}** ist vorbei – und wir würden gerne wissen, wie es dir gefallen hat!

@if(in_array($link->target_type, ['participant_child', 'participant_teen']))
**Deine Meinung zählt!** Wir möchten wissen, wie dir das Abenteuer gefallen hat. Hattest du Spaß? Hast du dich wohlgefühlt? Und war das Abenteuer so, wie du es dir gewünscht hast?

Mit dem kurzen Fragebogen kannst du uns sagen, was dir bei unseren Abenteuern wichtig ist.

Die Umfrage dauert nur ein paar Minuten. Vielen Dank, dass du mitmachst!
@elseif($link->target_type === 'teamer')
Als Teil des Teams möchten wir deine Erfahrungen und Einschätzungen hören. Dein Feedback hilft uns, die Organisation und Unterstützung stetig zu verbessern.
@else
Als Erziehungsberechtigte/r ist uns deine Rückmeldung besonders wichtig. Bitte nimm dir kurz Zeit, unsere Fragen zu beantworten.
@endif

> **Hinweis:** Deine Antworten werden nur für die interne Auswertung und Jugendförderungs-Dokumentation verwendet. Das Ausfüllen ist freiwillig.

<x-mail::button :url="$surveyUrl">
Zum Fragebogen
</x-mail::button>

@if($survey->closes_at)
Der Fragebogen ist bis **{{ $survey->closes_at->format('d.m.Y') }}** ausfüllbar.
@endif

Vielen Dank für deine Zeit!

Herzliche Grüße,
Deine Orga
</x-mail::message>
