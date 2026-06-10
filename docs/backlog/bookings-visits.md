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

### BOOK-04 · Buchung bearbeiten (`adventure.modify`) · ⏱ 4h · ✅
**Beschreibung:** Die Berechtigung `adventure.modify` existiert, aber es gibt
keine Edit-Aktion. Buchungsdetails (Rolle, Flags, Allergien, Medikamente,
Erreichbarkeit) nachträglich ändern.
**Akzeptanzkriterien:**
- [x] Route + Controller-Methode `update` (Booking) hinter `can:adventure.modify`.
- [x] Edit-Formular im Modal; AJAX-Submit mit Toast.
- [x] Tests (Erlaubt/verboten/Validierung).

> Umgesetzt: `BookingController@edit` + `@update`
> (`GET/PUT adventures/{adventure}/bookings/{booking}`, `can:adventure.modify`
> = alle außer Teilnehmer). Edit-Partial `bookings/_edit.blade.php` (Rolle, Flags,
> Allergien, Medikamente, Erreichbarkeit). „bearbeiten"-Link je Anmeldung im
> Abenteuer-Detail. Neuer Modal-Mechanismus `data-modal-subview`: lädt die
> Unteransicht, ohne `appModalUrl` zu überschreiben → nach dem Speichern
> (`refresh_modal`) landet man wieder auf dem Abenteuer-Detail. `abort 404`,
> wenn die Buchung nicht zum Abenteuer gehört. Tests: `BookingEditTest` (5).

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

### BOOK-08 · Teilnahme erfassen (`event_visits`) · ⏱ 4h · ✅
**Beschreibung:** Tabelle/Model vorhanden, aber keine UI. Erfassen, wer wirklich
da war (Grundlage für EP-Vergabe).
**Akzeptanzkriterien:**
- [x] Check-in-Liste je Event (aus Buchungen) zum Abhaken der Anwesenden.
- [x] Erzeugt `event_visits`-Einträge.
- [x] Berechtigung (Spielleiter/Teamer/Admin); Tests.

> Umgesetzt: Gate `manage-attendance` (game_master/teamer + Admin).
> `AttendanceController@update` (`PUT adventures/{adventure}/attendance`):
> synchronisiert `event_visits` aus den abgehakten Anwesenden; nur gebuchte
> Spieler zählen; idempotent in Transaktion. Check-in-Block (Checkboxen je
> Anmeldung) im Abenteuer-Detail-Modal, AJAX + Modal-Refresh. Tests:
> `AttendanceTest` (4). Grundlage für BOOK-09 (EP-Vergabe).

### BOOK-09 · Automatische EP-Vergabe nach Teilnahme · ⏱ 4h · ✅
**Beschreibung:** Nach erfasster Teilnahme bekommt der aktive Held des Spielers
EP (`loot_ep_day` × Tage, Typ 50 „Abenteuer bestritten").
**Akzeptanzkriterien:**
- [x] Aktion „EP verbuchen" je Event erzeugt EP-Transaktionen für alle Anwesenden.
- [x] Idempotent (keine Doppelvergabe); nutzt aktiven Helden.
- [x] Tests (Vergabe, keine Doppelvergabe, kein aktiver Held → Hinweis).
**Abhängig von:** BOOK-08, EP-01, HERO-07.

> Umgesetzt: `AttendanceController@awardEp` (`POST adventures/{adventure}/award-ep`,
> `manage-attendance`). EP = `loot_ep_day` × Eventtage (Start–Ende inkl.),
> Typ 50 mit `adventure_id`, an den aktiven Helden jedes anwesenden Spielers.
> Idempotent (je Held & Abenteuer einmal); Anwesende ohne aktiven Helden werden
> übersprungen (Hinweis in der Meldung). Button „EP für Teilnehmer verbuchen"
> im Check-in-Block. Damit füllt sich HERO-11 (Abenteuerhistorie) live.
> Tests: `AttendanceTest` (Vergabe+Betrag, Idempotenz, ohne aktiven Held, Recht).

### BOOK-10 · Buchungsformular: Spielerliste auf Berechtigte begrenzen · ⏱ 2h · 🔲
**Beschreibung:** Aktuell sind im Buchungs-Modal alle Spieler wählbar; sinnvoll
sind die eigenen (bzw. für Bürokrat alle).
**Akzeptanzkriterien:**
- [ ] Nicht-Admin sieht nur eigene/betreute Spieler.
- [ ] Tests.
