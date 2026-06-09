# Backlog · Buchungen & Teilnahme (BOOK)

Anmeldungen zu Events (`bookings`) und tatsächliche Teilnahme (`event_visits`).

## Inventar (✅)

### BOOK-01 · Buchungen-Schema + Model · ⏱ 3h · ✅
`bookings` mit allen Flags, Warteliste, `approved_at`; Unique gegen Doppelbuchung.

### BOOK-02 · Buchen mit Kapazitäts-/Wartelistenlogik · ⏱ 3h · ✅
`BookingController@store`; volles Event → Warteliste.

### BOOK-03 · Stornieren/Abmelden + AJAX-Toast · ⏱ 2h · ✅
`destroy`; AJAX-Submit mit Erfolg/Fehler-Toast.

## Offen (🔲)

### BOOK-04 · Buchung bearbeiten (`adventure.modify`) · ⏱ 4h · 🔲
**Beschreibung:** Die Berechtigung `adventure.modify` existiert, aber es gibt
keine Edit-Aktion. Buchungsdetails (Rolle, Flags, Allergien, Medikamente,
Erreichbarkeit) nachträglich ändern.
**Akzeptanzkriterien:**
- [ ] Route + Controller-Methode `update` (Booking) hinter `can:adventure.modify`.
- [ ] Edit-Formular im Modal; AJAX-Submit mit Toast.
- [ ] Tests (Erlaubt/verboten/Validierung).

### BOOK-05 · Buchung bestätigen/freigeben (`approved_at`) · ⏱ 3h · 🔲
**Beschreibung:** `approved_at` existiert, wird aber nie gesetzt. Freigabe-Workflow.
**Akzeptanzkriterien:**
- [ ] Admin/Bürokrat kann Buchung bestätigen (setzt `approved_at`).
- [ ] Anzeige bestätigt/unbestätigt in Anmeldeliste.
- [ ] Optionaler Trigger Bestätigungs-Mail (NOTI-02).
- [ ] Tests.

### BOOK-06 · Bezahlt-Status (`paid`) pflegen · ⏱ 2h · 🔲
**Beschreibung:** Teilnahmebeitrag-Status je Buchung.
**Akzeptanzkriterien:**
- [ ] Toggle „bezahlt" je Buchung (Bürokrat).
- [ ] Summen-/Offen-Anzeige je Event (siehe REP).

### BOOK-07 · Warteliste nachrücken · ⏱ 4h · 🔲
**Beschreibung:** Bei Storno eines regulären Platzes rückt die erste Wartelisten-
Buchung automatisch nach.
**Akzeptanzkriterien:**
- [ ] Beim Storno wird der älteste Wartelisten-Eintrag regulär gesetzt.
- [ ] Trigger Benachrichtigung (NOTI-03).
- [ ] Tests (Reihenfolge, kein Nachrücken wenn niemand wartet).
**Abhängig von:** BOOK-03.

### BOOK-08 · Teilnahme erfassen (`event_visits`) · ⏱ 4h · 🔲
**Beschreibung:** Tabelle/Model vorhanden, aber keine UI. Erfassen, wer wirklich
da war (Grundlage für EP-Vergabe).
**Akzeptanzkriterien:**
- [ ] Check-in-Liste je Event (aus Buchungen) zum Abhaken der Anwesenden.
- [ ] Erzeugt `event_visits`-Einträge.
- [ ] Berechtigung (Spielleiter/Teamer/Admin); Tests.

### BOOK-09 · Automatische EP-Vergabe nach Teilnahme · ⏱ 4h · 🔲
**Beschreibung:** Nach erfasster Teilnahme bekommt der aktive Held des Spielers
EP (`loot_ep_day` × Tage, Typ 50 „Abenteuer bestritten").
**Akzeptanzkriterien:**
- [ ] Aktion „EP verbuchen" je Event erzeugt EP-Transaktionen für alle Anwesenden.
- [ ] Idempotent (keine Doppelvergabe); nutzt aktiven Helden.
- [ ] Tests (Vergabe, keine Doppelvergabe, kein aktiver Held → Hinweis).
**Abhängig von:** BOOK-08, EP-01, HERO-07.

### BOOK-10 · Buchungsformular: Spielerliste auf Berechtigte begrenzen · ⏱ 2h · 🔲
**Beschreibung:** Aktuell sind im Buchungs-Modal alle Spieler wählbar; sinnvoll
sind die eigenen (bzw. für Bürokrat alle).
**Akzeptanzkriterien:**
- [ ] Nicht-Admin sieht nur eigene/betreute Spieler.
- [ ] Tests.
