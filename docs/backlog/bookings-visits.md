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

### BOOK-05 · Buchung bestätigen/freigeben (`approved_at`) · ⏱ 3h · ✅
**Beschreibung:** `approved_at` existiert, wird aber nie gesetzt. Freigabe-Workflow.
**Akzeptanzkriterien:**
- [x] Admin/Bürokrat kann Buchung bestätigen (setzt `approved_at`).
- [x] Anzeige bestätigt/unbestätigt in Anmeldeliste.
- [ ] Optionaler Trigger Bestätigungs-Mail (NOTI-02). → offen in NOTI-02 (Hook gesetzt).
- [x] Tests.

> Umgesetzt: Gate `approve-bookings` (Bürokrat/`registrar` + Admin via before).
> `BookingController@approve` (`PATCH adventures/{adventure}/bookings/{booking}/approval`)
> als Toggle von `approved_at`. Neue Spalte „Status" (✓ bestätigt / offen) in der
> Anmeldeliste; Button „bestätigen"/„zurücknehmen" nur für Berechtigte, AJAX +
> Modal-Refresh. Mail-Versand als Kommentar-Hook für NOTI-02 vorbereitet.
> `abort 404` wenn Buchung != Abenteuer. Tests: `BookingApprovalTest` (4).

### BOOK-06 · Bezahlt-Status (`paid`) pflegen · ⏱ 2h · ✅
**Beschreibung:** Teilnahmebeitrag-Status je Buchung.
**Akzeptanzkriterien:**
- [x] Toggle „bezahlt" je Buchung (Bürokrat).
- [x] Summen-/Offen-Anzeige je Event (siehe REP).

> Umgesetzt: Gate `manage-payments` (Bürokrat/`registrar` + Admin).
> `BookingController@togglePaid` (`PATCH adventures/{adventure}/bookings/{booking}/payment`)
> als Toggle von `paid`; 404 wenn Buchung != Abenteuer. Neue Spalte „Beitrag"
> (bezahlt/offen) + Toggle-Button „als bezahlt"/„als offen" je Anmeldung,
> AJAX + Modal-Refresh. Summenzeile (nur für Berechtigte) je Event: Beitrag,
> bezahlt X/Y, eingegangen €, offen € (über reguläre, nicht-Warteliste-Anmeldungen).
> Tests: `BookingPaymentTest` (4).

### BOOK-07 · Warteliste nachrücken · ⏱ 4h · ✅
**Beschreibung:** Bei Storno eines regulären Platzes rückt die erste Wartelisten-
Buchung automatisch nach.
**Akzeptanzkriterien:**
- [x] Beim Storno wird der älteste Wartelisten-Eintrag regulär gesetzt.
- [ ] Trigger Benachrichtigung (NOTI-03). → offen in NOTI-03 (Hook gesetzt).
- [x] Tests (Reihenfolge, kein Nachrücken wenn niemand wartet).
**Abhängig von:** BOOK-03.

> Umgesetzt in `BookingController@destroy`: War die stornierte Anmeldung
> regulär, wird die älteste Wartelisten-Buchung (`waitlisted=true`,
> `orderBy created_at, id`) auf `waitlisted=false` gesetzt. Kein Nachrücken,
> wenn die stornierte selbst auf der Warteliste war oder niemand wartet.
> Erfolgsmeldung nennt den nachgerückten Spieler. NOTI-03 als Kommentar-Hook
> vorbereitet. Tests: `BookingWaitlistPromotionTest` (Reihenfolge,
> Warteliste-Storno ohne Nachrücken, niemand wartend).

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

### BOOK-10 · Buchungsformular: Spielerliste auf Berechtigte begrenzen · ⏱ 2h · ✅
**Beschreibung:** Aktuell sind im Buchungs-Modal alle Spieler wählbar; sinnvoll
sind die eigenen (bzw. für Bürokrat alle).
**Akzeptanzkriterien:**
- [x] Nicht-Admin sieht nur eigene/betreute Spieler.
- [x] Tests.

> Umgesetzt: Gate `book-any-player` (Bürokrat/`registrar` + Admin). In
> `AdventureController@show` wird die Spielerliste für Nicht-Berechtigte auf
> `request()->user()->players()` (eigene/betreute via Pivot) begrenzt; Bürokrat/
> Admin sehen alle. Zusätzlich serverseitige Durchsetzung in
> `BookingController@store` (422, wenn ein fremder Spieler gebucht wird) –
> rein kosmetisches Filtern wäre umgehbar. Bestehende `AdventureTest`-Buchungs-
> tests ordnen den Spieler jetzt dem Bucher zu. Tests: `BookingPlayerScopeTest`
> (Liste eigene/alle, Buchen fremd verboten/Bürokrat erlaubt).
