# Backlog · Events / Abenteuer (ADV)

Veranstaltungen mit Ort, Status, Kategorie, Auftraggeber.
(Anmeldungen/Teilnahme: siehe [bookings-visits.md](bookings-visits.md).)

## Inventar (✅)

### ADV-01 · Adventures-Schema + Model + Lookups · ⏱ 4h · ✅
`adventures` + locations/categories/statuses/clients/roles (geseedet).

### ADV-02 · AdventureController (CRUD) + Belegungslogik · ⏱ 4h · ✅
Anlegen/Bearbeiten; `freeSlots`/`isFull`/`registrationOpen`.

### ADV-03 · Abenteuer-Modal (Detail + Anmeldung) · ⏱ 2h · ✅
Detail, Anmeldungen, Anmeldeformular per AJAX.

### ADV-04 · Rechte (ansehen/buchen/Events verwalten) · ⏱ 3h · ✅
`adventure.access` / `adventure.book` / `events.edit`.

## Offen (🔲)

### ADV-05 · Event-Status-Workflow · ⏱ 4h · 🔲
**Beschreibung:** `type_eventStatus` (unbekannt→geplant→Anmeldung offen→…→
Abgeschlossen) als geführter Workflow statt freier Auswahl.
**Akzeptanzkriterien:**
- [ ] Erlaubte Status-Übergänge definiert; ungültige Sprünge unterbunden.
- [ ] Statusabhängige Aktionen (Anmeldung nur bei „offen", Abrechnung bei „Abrechnung").
- [ ] Statusfarbe (`color`) in Liste/Detail.
- [ ] Tests.

### ADV-06 · Event-Verwaltung in der Verwaltung verlinken · ⏱ 2h · 🔲
**Beschreibung:** „Verwaltung → Veranstaltungen" zeigt aktuell die normale Liste.
Eine Admin-Eventliste mit Verwaltungsaktionen (anlegen/bearbeiten/absagen).
**Akzeptanzkriterien:**
- [ ] Admin-Eventliste mit Status, Belegung, Aktionen.
- [ ] Trennung Browsen (Spieler) vs. Verwalten (Admin) klar.

### ADV-07 · Event absagen (mit Folgeaktionen) · ⏱ 3h · 🔲
**Beschreibung:** Status „abgesagt" inkl. Benachrichtigung der Gebuchten.
**Akzeptanzkriterien:**
- [ ] Aktion „Event absagen" setzt Status + sperrt Buchungen.
- [ ] Trigger für Absage-Mails (siehe NOTI-04).
- [ ] Tests.
**Abhängig von:** ADV-05, NOTI-04.

### ADV-08 · Orte-Lookup-CRUD (locations) · ⏱ 3h · 🔲
**Beschreibung:** Veranstaltungsorte pflegbar (Titel, GPS, PLZ, Stadt, Adresse, Bild).
**Akzeptanzkriterien:**
- [ ] Admin-CRUD; Ort im Event-Formular wählbar.
- [ ] Tests.

### ADV-09 · Event-Kategorien & Auftraggeber CRUD · ⏱ 3h · 🔲
**Beschreibung:** `event_categories` (Soft-Delete) + `event_clients` pflegbar.
**Akzeptanzkriterien:**
- [ ] Admin-CRUD für beide Lookups.
- [ ] Kategorie-Soft-Delete respektiert; Auswahl im Event-Formular.

### ADV-10 · Event-Rollen-Lookup-CRUD (event_roles) · ⏱ 2h · 🔲
**Beschreibung:** Teilnahme-Rollen (Spieler, NSC, Teamer A–C) pflegbar.
**Akzeptanzkriterien:**
- [ ] Admin-CRUD; Verwendung im Buchungsformular.

### ADV-11 · Gamemaster/Eventleiter zuweisen · ⏱ 3h · 🔲
**Beschreibung:** `gamemaster_id`/`eventleader_id` (FK users) im Formular setzbar.
**Akzeptanzkriterien:**
- [ ] Auswahl berechtigter Nutzer (z. B. Spielleiter-Rolle).
- [ ] Anzeige in Event-Detail.
- [ ] Tests.

### ADV-12 · Event-Kalenderansicht · ⏱ 4h · 🔲
**Beschreibung:** Kommende Events als Kalender/Liste nach Datum.
**Akzeptanzkriterien:**
- [ ] Chronologische Ansicht kommender Events mit Status/Belegung.
- [ ] Optional Monats-/Listen-Umschaltung.
