# Backlog · Öffentliche Heldenansicht & Code (PUB)

Vision: Helden öffentlich einsehbar **ohne Realnamen**; jedes Kind erhält real
einen **6-stelligen Code** und kann seinen Helden darüber finden/teilen.
(Quelle: [../vision.md](../vision.md).)

## Offen (🔲)

### PUB-01 · 6-stelliger Helden-Code · ⏱ 3h · ✅
**Beschreibung:** Jeder Held erhält einen eindeutigen, gut lesbaren 6-stelligen
Code (z. B. Base32 ohne Verwechslungszeichen).
**Akzeptanzkriterien:**
- [x] Migration `heroes.public_code` (unique), Generierung bei Anlage/Migration.
- [x] Kollisionsfreie Erzeugung; Backfill für bestehende Helden.
- [x] Code im Helden-Detail (für den Spieler) sichtbar.
- [x] Tests (Eindeutigkeit, Format).

### PUB-02 · Öffentliches Helden-Profil (ohne Realname) · ⏱ 4h · ✅
**Beschreibung:** Öffentlich erreichbare, anonymisierte Heldenseite
(`/h/{code}`) – nur Charakterdaten, **kein** Spieler-Realname.
**Akzeptanzkriterien:**
- [x] Route ohne Auth; zeigt Charaktername, Klassen, Fertigkeiten/Perlen, ggf. Heimatort.
- [x] Keine personenbezogenen Daten (kein Spieler-Realname, keine E-Mail/Geburtsdatum).
- [x] „Nicht gefunden" bei unbekanntem/deaktiviertem Code.
- [x] Tests (sichtbare vs. verborgene Felder).

### PUB-03 · Heldensuche per Code · ⏱ 2h · ✅
**Beschreibung:** Öffentliche Suchmaske: Code eingeben → Heldenseite.
**Akzeptanzkriterien:**
- [x] Öffentliche Suchseite mit Code-Eingabe (Format-Validierung).
- [x] Weiterleitung auf `/h/{code}`; freundliche Fehlermeldung.
- [x] Tests.

### PUB-04 · Sichtbarkeit/Opt-out je Held steuern · ⏱ 3h · ✅
**Beschreibung:** Datenschutz: pro Held einstellbar, ob öffentlich sichtbar.
**Akzeptanzkriterien:**
- [x] `heroes.public_visible` (default true), Migration + Model + Factory.
- [x] Nicht sichtbare Helden liefern 404 auf der öffentlichen Seite.
- [x] Umschalten im Helden-Formular + schneller Toggle-Button im Detail.
- [x] Tests (7 Tests: 404, Default, Toggle, Formular, Zugriffsschutz).
**Abhängig von:** PUB-02.

### PUB-05 · QR-/Teilen-Funktion für den Code · ⏱ 3h · ✅
**Beschreibung:** QR-Code/Link zum Helden zum Ausdrucken/Teilen.
**Akzeptanzkriterien:**
- [x] QR-Code-Canvas (data-qr-url) im internen Helden-Detail, gerendert per `qrcode` npm-Paket in heldenregister.js (inkl. nach Modal-Load).
- [x] Nur bei public_visible=true sichtbar; waldritter-Farbe (#5a3a22).
- [x] Öffentliches Profil: „Link kopieren"-Button (Clipboard-API) + Helden-Code als Text.
- [x] 5 Tests; Backlog ✅.
**Abhängig von:** PUB-02.

### PUB-06 · Rate-Limiting & Missbrauchsschutz öffentliche Endpunkte · ⏱ 2h · ✅
**Beschreibung:** Öffentliche Suche/Profil gegen Enumeration/Scraping absichern.
**Akzeptanzkriterien:**
- [x] Throttle `public-hero` (30/min je IP) auf /h, /h/search, /h/{code}.
- [x] X-RateLimit-Header in Antworten; 429 nach Überschreitung.
- [x] Code-Raum 31⁶ ≈ 887 Mio. (Base31) — rechnerisch sicher gegen Brute-Force.
- [x] 5 Tests (Header, 429-Verhalten, Limit-Wert).

### PUB-07 · Heldencode sehen & Freigabe · ⏱ 3h · ✅
**Beschreibung:** Bürokrat kann Heldencode sehen & Freigabe durch Betreuer
**Akzeptanzkriterien:**
- [x] Nutzer mit Rolle Admin oder Bürokrat, können den Code sehen zur öffentlichen Seite (auch wenn `public_visible=false`, mit „aktuell versteckt"-Hinweis).
- [x] Betreuer des Spielers, admin und Bürokrat können im Helden-Detail die öffentliche Seite deaktivieren oder aktivieren (`heroes.visibility` PATCH-Route, Zugriffsschutz via `canManagePublicSettings()`).
- [x] 10 Tests (PUB-07+08 kombiniert).

### PUB-08 · Heldensuche auf der öffentlichen Seite · ⏱ 3h · ✅
**Beschreibung:** Nur wer seinen Helden für die Heldensuche freigibt, kann gefunden werden.
**Akzeptanzkriterien:**
- [x] `heroes.public_searchable` (default true), Migration + Model + Factory.
- [x] Option im Helden-Detail (Toggle-Button), ob man per öffentlicher Suche gefunden werden kann.
- [x] Änderbar nur durch Betreuer, Admin oder Bürokrat (`heroes.searchable` PATCH-Route).
- [x] Suche beinhaltet Heldennamen (LIKE) oder direkten Code (Redirect); nur `public_visible=true` & `public_searchable=true`.

### PUB-09 · Heldensuche verlinken · ⏱ 3h · ✅
**Beschreibung:** Damit man zu Heldensuche kommt, verlinken auf unterschiedlichen Stellen
**Akzeptanzkriterien:**
- [x] Auf der Login-Seite: Info-Block „Was ist das Heldenregister?" mit Erklärung + Link zur Heldensuche.
- [x] Auf der Login-Seite: Hinweis auf Helden-Code (Ausweis vom Bürokraten, QR-Code im Detail).
- [x] Öffentliche Suchseite: Amber-Box mit Hinweis auf Ausweis und QR-Code als Code-Quellen.
- [x] 4 Tests.

### PUB-10 Heldencode eintragen durch den Bürokraten ✅
**Beschreibung:** Damit der Held gleich einen Ausweis erhält, werden die Ausweise schon vorab fertiggestellt.
**Akzeptanzkriterien:**
- [x] Generator für Heldenausweise in der Verwaltung, Rollen Admin und Bürokrat
- [x] Beim Helden den Helden-Code eintragen, wenn der Ausweis ausgehändigt wird. ansonsten kein Code.
- [x] Generator erstellt eine PDF mit Ausweise im Größenraster 7,52cm x 10cm im Raster von 3x2 querformat.
- [x] Generator fragt die Anzahl der Ausweise die erstellt werden sollen ab.
- [x] Der Helden-Kode soll per Zufallsprinzip generiert werden.
- [x] der Helden-Kode wird erst im System aktiv, wenn dieser vom Bürokrat dem Helden zugeteilt wird. Aber vermeiden der doppelbelegung.
- [x] Bei Verlust eines Ausweis, kann der Bürokrat den Ausweis neu herstellen per PDF export.
- [x] Vorderseite des Ausweis: template_helden_ausweis_vorderseite.png (optional per file_exists, CSS-Fallback)
- [x] Rückseite des Ausweis: template_helden_ausweis_rueckseite.png (Seite 2 im PDF, Spalten gespiegelt für Duplexdruck)
- [x] erstelle den QR Code und setze ihn auf das entsprechne Feld auf der Vorderseite des Ausweises

### PUB-11 Helden Informationen ⏱ 3h · ✅
**Beschreibung:** Was alles in der öffentlichen Seite angezeigt werden soll
**Akzeptanzkriterien:**
- [x] Zeige die Fertigkeitsbäume mit den errungenen Fertigkeiten und Perlen an.
- [x] Wenn noch kein Charaktername existiert, schreibe die Initialien des Spielers
- [x] Felder die kein Inhalt haben, sollen dennoch angezeigt werden, z.b. Steckbrief, dann mit der Notiz "Noch keine Eintragungen" oder Herkunft.
- [x] Zeige auch das Datum der "Erblickung" also wann der Charakter im Rollenspiel das erstemal erwähnt wurde
- [x] Zeige auch die verfügbare EP

### PUB-12 Helden-Ausweis ⏱ 3h · ✅
**Beschreibung:** Anpassung des Helden-Ausweises und Generator
**Akzeptanzkriterien:**
- [x] PDF Auflösung von mindestens 72dpi (QR-Code auf 400 px, Template-Hintergrund skaliert 100%×100%)
- [x] Keine Ränder oder zusätzliches, nur die Bilder-Templates template_helden_ausweis_vorderseite.png und template_helden_ausweis_rueckseite
- [x] die Templates sind 72dpi und 980 px x 1312 px
- [x] Der Platz für den QR-Code ist im template 247px x 220px groß und an der beginnt an der Position x:612px und y: 981px
- [x] Der Platz für den Helden-Kode ist im template 293px X 106px groß und beginnt an der Position x:105px und y:1053px
- [x] benenne überall den Begriff Helden-ID, Helden-Code oder Helden-Kode um in Helden-Siegel.
- [x] beim Generieren von mehreren Ausweisen, sollen kachelförmig die Ausweise auf eine duplex Seite passen. mit 2px abstand dazwischen.
