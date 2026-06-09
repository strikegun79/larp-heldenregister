# Backlog · Öffentliche Heldenansicht & Code (PUB)

Vision: Helden öffentlich einsehbar **ohne Realnamen**; jedes Kind erhält real
einen **6-stelligen Code** und kann seinen Helden darüber finden/teilen.
(Quelle: [../vision.md](../vision.md).)

## Offen (🔲)

### PUB-01 · 6-stelliger Helden-Code · ⏱ 3h · 🔲
**Beschreibung:** Jeder Held erhält einen eindeutigen, gut lesbaren 6-stelligen
Code (z. B. Base32 ohne Verwechslungszeichen).
**Akzeptanzkriterien:**
- [ ] Migration `heroes.public_code` (unique), Generierung bei Anlage/Migration.
- [ ] Kollisionsfreie Erzeugung; Backfill für bestehende Helden.
- [ ] Code im Helden-Detail (für den Spieler) sichtbar.
- [ ] Tests (Eindeutigkeit, Format).

### PUB-02 · Öffentliches Helden-Profil (ohne Realname) · ⏱ 4h · 🔲
**Beschreibung:** Öffentlich erreichbare, anonymisierte Heldenseite
(`/h/{code}`) – nur Charakterdaten, **kein** Spieler-Realname.
**Akzeptanzkriterien:**
- [ ] Route ohne Auth; zeigt Charaktername, Klassen, Fertigkeiten/Perlen, ggf. Heimatort.
- [ ] Keine personenbezogenen Daten (kein Spieler-Realname, keine E-Mail/Geburtsdatum).
- [ ] „Nicht gefunden" bei unbekanntem/deaktiviertem Code.
- [ ] Tests (sichtbare vs. verborgene Felder).

### PUB-03 · Heldensuche per Code · ⏱ 2h · 🔲
**Beschreibung:** Öffentliche Suchmaske: Code eingeben → Heldenseite.
**Akzeptanzkriterien:**
- [ ] Öffentliche Suchseite mit Code-Eingabe (Format-Validierung).
- [ ] Weiterleitung auf `/h/{code}`; freundliche Fehlermeldung.
- [ ] Tests.

### PUB-04 · Sichtbarkeit/Opt-out je Held steuern · ⏱ 3h · 🔲
**Beschreibung:** Datenschutz: pro Held einstellbar, ob öffentlich sichtbar.
**Akzeptanzkriterien:**
- [ ] `heroes.public_visible` (default an/aus nach DSGVO-Entscheid).
- [ ] Nicht sichtbare Helden liefern 404 auf der öffentlichen Seite.
- [ ] Umschalten im Helden-Formular; Tests.
**Abhängig von:** PUB-02.

### PUB-05 · QR-/Teilen-Funktion für den Code · ⏱ 3h · 🔲
**Beschreibung:** QR-Code/Link zum Helden zum Ausdrucken/Teilen (Legacy hatte
`chillerlan/php-qrcode` – ungenutzt; hier sinnvoll reaktivierbar).
**Akzeptanzkriterien:**
- [ ] QR-Code zur öffentlichen Heldenseite im Detail/Charakterbogen.
- [ ] Druckfreundliche Darstellung; Test (QR wird erzeugt).
**Abhängig von:** PUB-02.

### PUB-06 · Rate-Limiting & Missbrauchsschutz öffentliche Endpunkte · ⏱ 2h · 🔲
**Beschreibung:** Öffentliche Suche/Profil gegen Enumeration/Scraping absichern.
**Akzeptanzkriterien:**
- [ ] Throttle auf Such-/Profil-Routen.
- [ ] Code-Raum groß genug gegen Erraten; Test.
