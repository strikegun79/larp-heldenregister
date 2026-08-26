<x-mail::message>
# Willkommen im Heldenregister, {{ $heroName }}!

Die alten Chronisten der Waldritter haben ihre Federn gezückt und **{{ $heroName }}** soeben in das legendäre **Heldenregister** eingetragen. Eine neue Seite in der Geschichte der Waldritter wird aufgeschlagen!

Von diesem Tage an werden alle Abenteuer, errungenen Fertigkeiten und ruhmreichen Taten dieses Helden für die Ewigkeit in unseren Schriftrollen bewahrt. Jede bestandene Prüfung, jeder tapfer erfochtene Sieg – nichts wird vergessen.

@if ($hero->public_code)

---

**Das Helden-Siegel lautet:** `{{ $hero->public_code }}`

Dieses Siegel ist das unverwechselbare Zeichen des Helden in der Welt der Waldritter.

@if ($hero->public_visible)
Unter folgendem Link kannst du das öffentliche Heldenprofil aufrufen:

<x-mail::button :url="$publicUrl">
Heldenprofil ansehen
</x-mail::button>

*(Die Sichtbarkeit des Profils kann jederzeit in den Einstellungen geändert werden.)*
@else
Das öffentliche Heldenprofil ist derzeit noch verborgen. Sobald es freigeschaltet ist, kann es unter folgendem Link aufgerufen werden:

{{ $publicUrl }}

*(Die Sichtbarkeit des Profils kann jederzeit in den Einstellungen des Heldenregisters geändert werden.)*
@endif

---

Falls ein Heldenausweis vorliegt, kann das Profil auch direkt über den QR-Code auf dem Ausweis oder durch Eingabe des Helden-Siegels aufgerufen werden:

{{ $searchUrl }}

@endif

*Möge {{ $heroName }} stets tapfer kämpfen und in Ehren bestehen!*

Schönen Gruß,
Die Chronisten der Waldritter
</x-mail::message>
