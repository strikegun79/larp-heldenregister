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

### PUB-07 · Heldencode sehen & Freigabe · ⏱ 3h · 🔲
**Beschreibung:** Bürokrat kann Heldencode sehen & Freigabe durch Betreuer
**Akzeptanzkriterien:**
- [] Nutzer mit Rolle Admin oder Bürokrat, können den Code sehen zur öffentlichen Seite. Damit er diesen auf den Ausweis schreiben kann für das Kind
- [] Betreuer des Spielers, admin und Bürokrat können im Helden-Detail die öffentliche Seite deaktivieren oder aktivieren. Damit steht es jedem Frei das zu entscheiden

### PUB-08 Heldensuche auf der öffentlichen Seite · ⏱ 3h · 🔲
**Beschreibung:** Nur wer sein Helden für die Heldensuche freigibt, kann gefunden werden.
**Akzeptanzkriterien:**
- [] Option im Helden-Detail, ob man per öffentliche Suche gefunden werden kann.
- [] Änderbar nur durch Betreuer, Admin oder Bürokrat
- [] Suche soll den Heldennamen oder Code beinhalten
