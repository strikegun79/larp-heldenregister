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

### ADM-07 · Event-Status-Lookup-CRUD · ⏱ 2h · ✅
**Beschreibung:** `event_statuses` (Beschreibung, Farbe) pflegbar.
**Akzeptanzkriterien:**
- [x] Admin-CRUD; System-IDs vor Löschung geschützt (Sperre wenn adventures_count > 0).

### ADM-08 · Audit-Log (Grundgerüst) · ⏱ 4h · ✅
**Beschreibung:** Protokoll wichtiger Admin-Aktionen (Rollen, Aktivierung,
Matrix, EP-Buchungen).
**Akzeptanzkriterien:**
- [x] `audit_logs` (actor_id, actor_name, action, subject_type/id/label, changes JSON, created_at).
- [x] `AuditLogger::log()` statischer Helper; Admin-Ansicht mit Filter (Aktion, Akteur).
- [x] Logs bei user.created/updated/deleted/restored (UserController) und ep.booked (EpTransactionController).
- [x] Tests.

### ADM-09 · Portal-Konfiguration (Key/Value) · ⏱ 3h · ✅
**Beschreibung:** Legacy `portal_config` (Logo, URL, Version). In Laravel als
`config` + ggf. editierbare Settings-Tabelle.
**Akzeptanzkriterien:**
- [x] Settings-Quelle definiert: `settings`-Tabelle (Key/Value) für Laufzeit-Werte; deployment-spezifisches (APP_URL, APP_NAME) bleibt in `.env`.
- [x] Editierbare Vereins-Settings (Vereinsname, Kontakt-E-Mail, Logo-Dateiname) im Admin.
- [x] `Setting::get()` / `Setting::set()` als statische Helper; `SettingsSeeder` mit Defaults.
- [x] Tests.

### ADM-10 · Portal-Nutzer-Bearbeiten
**Beschreibung:** bessere GUI Einstellungen für die Nutzer-Verwaltung
**Akzeptanzkriterien:**
- [] Das öffnen eines Nutzer in der Nutzer-Verwaltung soll per klick auf die Zeile sich öffnen. 
- [] Löschen soll als Symbol (Mülleimer) zu sehen sein.
- [] Ein Nutzer soll auch Wiederverwendbar sein. Also nur Soft-Deletes