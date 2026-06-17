# Backlog · Stammdaten & Verwaltung (ADM)

Admin-Bereich (`portal.manage`/`users.manage`) und übergreifende Stammdaten.
Entity-spezifische Lookup-CRUDs stehen bei der jeweiligen Entität (ADV/EP/HERO/MTX);
hier die übergreifenden Admin-Themen.

## Inventar (✅)

### ADM-01 · Admin-Landing + Nutzerverwaltung · ⏱ 4h · ✅
Rollen zuweisen, aktivieren/deaktivieren.

### ADM-02 · Admin-Spielerliste · ⏱ 2h · ✅
Alle Spieler inkl. Matrix-Status.

### ADM-03 · Nutzer-Bearbeiten als Modal + AJAX-Toast · ⏱ 2h · ✅
Rollen/Aktivierung im Modal.

## Offen (🔲)

### ADM-04 · Zentrales Stammdaten-Dashboard · ⏱ 2h · ✅
**Beschreibung:** Eine „Stammdaten"-Übersicht in der Verwaltung, die alle
Lookup-CRUDs (Orte, Kategorien, Status, Event-Rollen, Klassen, Perlenfarben,
EP-Arten, Matrix-Räume) bündelt.
**Akzeptanzkriterien:**
- [x] Karten/Links zu allen Lookup-Verwaltungen.
- [x] Nur mit `portal.manage`.

### ADM-05 · Generischer Lookup-CRUD-Baustein · ⏱ 4h · 🔲
**Beschreibung:** Wiederverwendbares Muster (Controller-Trait + Blade-Partial)
für einfache Lookups, um ADV-08/09/10, EP-05/06, HERO-05 nicht zu duplizieren.
**Akzeptanzkriterien:**
- [ ] Gemeinsames Listen-/Formular-Partial + Basis-Controller.
- [ ] Mind. ein Lookup darauf umgestellt (Proof).
- [ ] Tests.

### ADM-06 · Nutzer im Admin anlegen/einladen · ⏱ 3h · ✅
**Beschreibung:** Admin kann Nutzer manuell anlegen/einladen (statt nur
Selbstregistrierung).
**Akzeptanzkriterien:**
- [x] Formular Anlegen (Name, E-Mail, Rolle) + Einladungs-/Set-Password-Mail.
- [x] Tests.

### ADM-07 · Event-Status-Lookup-CRUD · ⏱ 2h · 🔲
**Beschreibung:** `event_statuses` (Beschreibung, Farbe) pflegbar.
**Akzeptanzkriterien:**
- [ ] Admin-CRUD; System-IDs vor Löschung geschützt.

### ADM-08 · Audit-Log (Grundgerüst) · ⏱ 4h · 🔲
**Beschreibung:** Protokoll wichtiger Admin-Aktionen (Rollen, Aktivierung,
Matrix, EP-Buchungen).
**Akzeptanzkriterien:**
- [ ] `audit_logs` (actor, action, subject, changes JSON, timestamp).
- [ ] Schreib-Helper/Observer; Admin-Ansicht mit Filter.
- [ ] Tests.

### ADM-09 · Portal-Konfiguration (Key/Value) · ⏱ 3h · 🔲
**Beschreibung:** Legacy `portal_config` (Logo, URL, Version). In Laravel als
`config` + ggf. editierbare Settings-Tabelle.
**Akzeptanzkriterien:**
- [ ] Settings-Quelle definiert (config vs. DB).
- [ ] Editierbare Vereins-Settings (Name, Logo, Kontakt) im Admin.
- [ ] Tests.
