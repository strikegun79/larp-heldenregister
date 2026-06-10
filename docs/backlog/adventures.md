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

### ADV-13 · Event-Spieleransicht · ⏱ 4h · 🔲
**Beschreibung:** Wer nur die Rollen Teilnehmer,Event-Buchen und Teamer hat, darf nur seine eigenen Spieler unter seinem Nutzer sehen.
**Akzeptanzkriterien:**
- [ ] Berücksichtigen der Rolle.
- [ ] Filtern der Spieler die am Event angemeldet sind.
- [ ] Spieler die schon angemeldet sind, erscheinen in der Dropdown auswahl nicht mehr.

### ADV-14 · Event-unter Verwaltung · ⏱ 4h · 🔲
**Beschreibung:** Das Öffnen eines Events unter der Verwaltung soll direkt die Editieren-Ansicht des Events zeigen.
**Akzeptanzkriterien:**
- [ ] Event öffnen unter Abenteuer öffnet ein Modal für die Anmeldung an einem Event
- [ ] Event öffnen unter der Verwaltung-Abenteuer öffnet das Modal zum editieren des Events als solches.
- [] Event Status dropdown soll durchnummeriert sein: 0 unbekannt, 10 in Bearbeitung, 20 geplant, 30 Anmeldung offen, 40 Anmeldung geschlossen, 50 Abrechnung, 60 Abgeschlossen, 70 abgesagt
- [] die Spieler Check-In können nur nach Status 40 erfolgen und nur durch die Rollen Admin, Projektleitung und Bürokrat


### ADV-15 · Event-Layout · ⏱ 4h · ✅
**Beschreibung:** Das Öffnen unter verfügbare Abenteuer 
**Akzeptanzkriterien:**
- [x] Beim anklicken eines Abenteuers aus "Abenteuer" öffnet ein Modal mit allen gängigen Informationen aus dem Event
- [x] Hinzu soll eine Funktionsmail kommen, die für das Event vorgesehen ist.
- [x] Auch die aktuelle Anmeldungen soll angezeigt werden. Beschränkt auf die Rolle. Wenn nur Teamer, Event buchen oder Teilnehmer, dürfen nur die eigenen Spieler zu sehen sein.
- [x] Es soll ein Button geben "Anmelden", dadurch öffnet sich ein Modal zum Anmelden mit allen Anmeldungsfelder.
- [x] wird das Anmeldemodal geschlossen oder bestätigt fällt man zurück zum Modal des Events und sieht die neuen angemeldeten Spieler.

> Umgesetzt: Spalte `adventures.function_email` (Migration, im Event-Formular
> pflegbar, im Detail als `mailto:`-Link). Gate `view-all-bookings`
> (Bürokrat/Projektleitung/Spielleiter + Admin); Teamer/Event-buchen/Teilnehmer
> sehen in der Anmeldeliste nur die eigenen Spieler (`AdventureController@show`
> liefert `$visibleBookings`). „Anmelden"-Button öffnet das Formular als
> Unteransicht (`bookings/_create.blade.php`, `GET adventures/{adventure}/
> bookings/create`, alle Anmeldefelder inkl. Medikamente/Erreichbarkeit) per
> `data-modal-subview` – ohne `appModalUrl` zu überschreiben. Absenden
> (`refresh_modal`) oder „Zurück" führen zurück aufs Event-Detail mit der neuen
> Anmeldung. Tests: `EventLayoutTest` (6) + angepasste `BookingPlayerScopeTest`.
### ADV-16 · Event-Layout mit Tabs · ⏱ 4h · ✅
**Beschreibung:** ein Event zu öffnen soll übersichtlicher werden
**Akzeptanzkriterien:**
- [x] Der Anmelde button soll Links im Footer liegen. Auf der rechten Seite ist der Button Schliessen
- [x] im Modal Content sollen UI tabs sein. das erste zeigt das Event. das zweite zeigt die Anmeldungen
- [x] für die Verwaltung -> Abenteuer soll ein Modal für verwaltungszwecke genutzt werden. Also direkt in den Editor Modus mit Tabs im Modal content. Ohne Anmeldung für den Nutzer. im ersten Tab die Allgemeinen Event Daten, im zweiten die aktuellen Anmeldungen und möglichkeiten zu bestätigung, "als bezahlt", bearbeiten, stornieren. im dritten Tag soll der Checkin möglich sein.

> Umgesetzt: Player-Detail (`_detail`) jetzt mit Fomantic-Tabs „Event"/
> „Anmeldungen" (schreibgeschützte, rollengefilterte Liste). Footer per CSS
> (`#app-modal-actions` flex): „Anmelden" links, „Schließen" rechts
> (`margin-left:auto` auf `.deny`). Verwaltungs-Modal `_manage`
> (`GET adventures/{adventure}/manage`, `can:events.edit`) mit 3 Tabs:
> Event-Daten (Editor `_form`, keine Selbst-Anmeldung), Anmeldungen mit Aktionen
> (bestätigen/bezahlt/bearbeiten/stornieren + Beitragssumme), Check-in.
> Geteilte Partials `_bookings` (Flag `$manage`) und `_checkin`. Neue
> Admin-Eventliste (`Admin\AdventureController`, Verwaltung → Abenteuer) öffnet
> das Verwaltungs-Modal je Event. Tests: `EventManageModalTest` (5).

### ADV-17 · Event-Exprot und unterschreiben · ⏱ 4h · ✅
**Beschreibung:** per Tablet soll es möglich sein, eine Unterschrift bei teilnahme zu leisten und eine PDF zu exportieren
**Akzeptanzkriterien:**
- [x] Die Projektleitung, Admin und Bürokrat können das Event öffnen und auf dem 3. Tab für den Teilnehmer eine Unterschrift entgegen nehmen.
- [x] Tauglich mit Tablet und Stift. Ein Feld zum unterzeichnen.
- [x] eine PDF mit allen Teilnehmern die angemeldet sind soll runterladbar sein. Spalten der Liste: laufende Nr., Nachname, Vorname, Ort, Kontaktrufnummer, Unterschrift falls existiert
- [x] im Kopf der der Teilnehmerliste muss stehen, das Eventdatum, der Eventort, der Eventtyp und Anzahl von Männlich und weiblich

> Umgesetzt: Gate `take-signatures` (Projektleitung/Bürokrat + Admin). Spalte
> `bookings.signature` (base64-PNG). Auf dem Check-in-Tab (Tab 3) je Teilnehmer
> „erfassen/ändern" → Unterschriften-Pad als Unteransicht (`bookings/_signature`,
> Canvas mit Pointer-Events = Tablet/Stift/Maus, „Löschen"/„Entfernen").
> `SignatureController` (edit/update/destroy, Validierung `starts_with:data:image/png`).
> PDF via dompdf (`barryvdh/laravel-dompdf`): `AdventureController@participantsPdf`
> (`GET adventures/{adventure}/participants-pdf`, `take-signatures`), View
> `participants_pdf` mit Kopf (Datum, Ort, Typ, Anzahl männlich/weiblich/gesamt)
> und Tabelle (Nr., Nachname, Vorname, Ort, Kontaktrufnummer = `erreichbarkeit`,
> Unterschrift als Bild). Hinweis: „Ort" bleibt leer – Spieler haben kein
> Wohnort-Feld (Spalte ist für später vorhanden). Tests: `EventSignaturePdfTest` (7).

### ADV-18 · Event-Ansicht für Teilnehmer · ⏱ 4h · ✅
**Beschreibung:** kleine Korrekturen
**Akzeptanzkriterien:**
- [x] Unter dem Tab Anmeldungen für ein Event, muss der Nutzer sehen können welcher Status und Beitrag für die einzelnen Spieler sind.
- [x] Status können sein: offen, bestätigt, abgelehnt, abgemeldet
- [x] Unter Beitrag: offen, bezahlt
- [x] Für die Verwaltung zum Checkin, sollten die Teilnehmer als Liste angezeigt werden, pro Teilnehmer eine Zeile. Am ende zwei buttons: Check-in, Abmelden (abmelden soll den Status auf "abgemeldet" setzen, z.b. weil der Teilnehmer sich abgemeldet hat, obwohl angemeldet, frage auch den Grund ab. Krank, nicht erschienen, unentschuldigt)

> Umgesetzt: Migration `bookings.status` (offen/bestaetigt/abgelehnt/abgemeldet,
> Backfill aus `approved_at`) + `absence_reason` (krank/nicht_erschienen/
> unentschuldigt); Label-Accessoren am Booking-Model. Der Anmeldungen-Tab
> (`_bookings`) zeigt jetzt für **alle** (auch Teilnehmer) Spalten Status und
> Beitrag (offen/bezahlt). Bestätigen (BOOK-05) setzt Status `bestaetigt`;
> neue `BookingController@reject` (Toggle abgelehnt/offen, `approve-bookings`).
> Check-in-Tab als Teilnehmer-Tabelle: je Zeile Buttons „Check-in"
> (`AttendanceController@toggle`, Einzel-`event_visit`) und „Abmelden" mit
> Grund-Auswahl (`@deregister` → Status abgemeldet + Grund, entfernt Check-in).
> Tests: `BookingStatusTest` (7).

### ADV-19 · Event-Check-in · ⏱ 4h · ✅
**Beschreibung:** Anpassung des Checkins
**Akzeptanzkriterien:**
- [x] in der Teilnehmerliste soll neben der spalte Status, die Unterschrift sein.
- [x] das Feld Abmeldungsgrund, soll im Multimodal fenster abgefragt werden, wenn man auf abmelden klickt.
- [x] beim klick auf den Button Check-In, soll das multimodal Fenster für die Unterschrift kommen und dort wird der Check-in bestätigt.
- [x] dadurch wird die zweite Liste für die Unteschrift unnötig
- [x] das Modal soll stehtig auf 950px x 950px sein, damit das Fenster nicht springt beim Tab wechseln
- [x] die Teilnehmerliste als PDF soll als Popup window erscheinen im Browser, damit mal die liste gleich sieht und nicht erst speichern muss.

> Umgesetzt: Check-in-Tab ist eine einzige Teilnehmertabelle (Gate `manage-checkin`
> = Spielleiter/Teamer/Projektleitung/Bürokrat + Admin) mit Spalten Teilnehmer,
> Status, **Unterschrift** (Vorschaubild) und Aktionen. „Check-in" öffnet ein
> Multimodal mit Unterschriften-Pad (`#signature-modal`, `allowMultiple`);
> Speichern bucht Unterschrift **und** Check-in in einem (`SignatureController@update`
> legt zugleich den `event_visit` an). „Abmelden" öffnet ein Multimodal mit
> Grund-Auswahl (`#deregister-modal`) → `deregister`. Die separate
> Unterschriften-Liste (ADV-17) ist entfallen. Modal fest `modal-event`
> (950px, Content min-height 820px). Teilnehmer-PDF wird inline gestreamt
> (`Pdf::stream`, Link `target=_blank`) statt Download. Tests: `EventCheckinTest` (4).
