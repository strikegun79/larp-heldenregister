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

### ADV-05 · Event-Status-Workflow · ⏱ 4h · ✅
**Beschreibung:** `type_eventStatus` (unbekannt→geplant→Anmeldung offen→…→
Abgeschlossen) als geführter Workflow statt freier Auswahl.
**Akzeptanzkriterien:**
- [x] Erlaubte Status-Übergänge definiert; ungültige Sprünge unterbunden.
- [x] Statusabhängige Aktionen (Anmeldung nur bei „offen", Abrechnung bei „Abrechnung").
- [x] Statusfarbe (`color`) in Liste/Detail.
- [x] Tests.

> Umgesetzt: `EventStatus::TRANSITIONS` (erlaubte Übergänge; „abgesagt"/70 aus
> jedem aktiven Status, „Abgeschlossen"/60 terminal). `Adventure::allowedStatusIds()`
> / `canTransitionTo()`. `AdventureController@update` weist ungültige Sprünge mit
> 422 ab; das Status-Dropdown (`_form`) zeigt bei bestehenden Events nur erlaubte
> Folgestatus (Neuanlage: alle). Statusabhängige Aktionen bestehen bereits:
> Anmeldung nur bei Status 30 (`registrationOpen`), Check-in/EP erst ab 40
> (`checkinAllowed`, ADV-14). Statusfarbe als Badge (`_status_badge.blade.php`,
> nutzt `color`) in Liste, Admin-Liste und Event-Detail. Tests:
> `EventStatusWorkflowTest` (6).

### ADV-06 · Event-Verwaltung in der Verwaltung verlinken · ⏱ 2h · ✅
**Beschreibung:** „Verwaltung → Veranstaltungen" zeigt aktuell die normale Liste.
Eine Admin-Eventliste mit Verwaltungsaktionen (anlegen/bearbeiten/absagen).
**Akzeptanzkriterien:**
- [x] Admin-Eventliste mit Status, Belegung, Aktionen.
- [x] Trennung Browsen (Spieler) vs. Verwalten (Admin) klar.

> Umgesetzt: Eigene Verwaltungsliste `AdventureController@manageIndex`
> (`GET adventures-manage`, `adventures.manage-index`, `can:events.edit` – also
> alle Event-Verwalter, nicht nur Admin) mit Status-Badge, Belegung und Aktionen
> je Zeile: „Neues Abenteuer" (anlegen), „Verwalten" (Modal/Editor), „Absagen"
> (ADV-07, nur wenn erlaubt). Der frühere admin-only `Admin\AdventureController`
> + View wurden dadurch ersetzt. Klare Trennung: Browse-Liste (`adventures.index`,
> `adventure.access`) ohne Management-Buttons – nur Link „Zur Event-Verwaltung";
> Verwaltungsliste für `events.edit`. Verwaltungs-Karte verweist auf die neue
> Liste. Tests: `EventManageListTest` (3) + angepasste `EventManageModalTest`.

### ADV-07 · Event absagen (mit Folgeaktionen) · ⏱ 3h · ✅
**Beschreibung:** Status „abgesagt" inkl. Benachrichtigung der Gebuchten.
**Akzeptanzkriterien:**
- [x] Aktion „Event absagen" setzt Status + sperrt Buchungen.
- [ ] Trigger für Absage-Mails (siehe NOTI-04). → offen in NOTI-04 (Hook gesetzt).
- [x] Tests.
**Abhängig von:** ADV-05, NOTI-04.

> Umgesetzt: `AdventureController@cancel` (`PATCH adventures/{adventure}/cancel`,
> `can:events.edit`) setzt Status 70 „abgesagt" (respektiert ADV-05-Workflow:
> aus aktiven Status erlaubt, aus „Abgeschlossen"/60 abgelehnt; doppelte Absage
> abgewiesen). Da Status ≠ 30, sind danach keine neuen Anmeldungen mehr möglich
> (`registrationOpen`/`BookingController@store`). Button „Event absagen" im
> Verwaltungs-Modal (Event-Daten-Tab) mit Bestätigung; abgesagte Events zeigen
> einen Hinweis. NOTI-04 als Kommentar-Hook vorbereitet. Tests:
> `EventCancelTest` (5).

### ADV-08 · Orte-Lookup-CRUD (locations) · ⏱ 3h · ✅
**Beschreibung:** Veranstaltungsorte pflegbar (Titel, GPS, PLZ, Stadt, Adresse, Bild).
**Akzeptanzkriterien:**
- [x] Admin-CRUD; Ort im Event-Formular wählbar.
- [x] Tests.

> Umgesetzt: `Admin\LocationController` (index/create/store/edit/update/destroy)
> im Admin-Bereich unter `can:portal.manage`. Modal-Formular
> `admin/locations/_form.blade.php` (Titel, PLZ, Stadt, Adresse, GPS, Bild);
> Listenseite mit Event-Zähler + „Neuer Ort"; Karte „Orte" in der Verwaltung.
> Löschen setzt `adventures.location_id` per `nullOnDelete` (Events bleiben
> erhalten). Ort ist im Event-Formular bereits wählbar (`_form` location-Select).
> Tests: `LocationAdminTest` (6).

### ADV-09 · Event-Kategorien & Auftraggeber CRUD · ⏱ 3h · ✅
**Beschreibung:** `event_categories` (Soft-Delete) + `event_clients` pflegbar.
**Akzeptanzkriterien:**
- [x] Admin-CRUD für beide Lookups.
- [x] Kategorie-Soft-Delete respektiert; Auswahl im Event-Formular.

> Umgesetzt: `Admin\EventCategoryController` + `Admin\EventClientController`
> (index/create/store/edit/update/destroy) unter `can:portal.manage`, Modal-
> Formulare + Listen mit Event-Zähler; Karten „Kategorien"/„Auftraggeber" in der
> Verwaltung. IDs fortlaufend (`max(id)+1`, Lookups nicht auto-inkrementiert).
> Kategorie-Löschung ist Soft-Delete → verschwindet aus der Event-Auswahl
> (`EventCategory::orderBy` nutzt Default-Scope), bestehende Events bleiben gültig.
> Auftraggeber-Löschung nur ohne referenzierende Events (FK RESTRICT, 422-Hinweis).
> Tests: `EventLookupAdminTest` (7).

### ADV-10 · Event-Rollen-Lookup-CRUD (event_roles) · ⏱ 2h · ✅
**Beschreibung:** Teilnahme-Rollen (Spieler, NSC, Teamer A–C) pflegbar.
**Akzeptanzkriterien:**
- [x] Admin-CRUD; Verwendung im Buchungsformular.

> Umgesetzt: `Admin\EventRoleController` (index/create/store/edit/update/destroy)
> unter `can:portal.manage`; Modal-Formular + Liste mit Anmeldungszähler; Karte
> „Teilnahme-Rollen" in der Verwaltung. IDs fortlaufend (`max(id)+1`). Löschen
> nur ohne referenzierende Anmeldungen (FK RESTRICT, 422-Hinweis). Rollen sind
> im Buchungsformular bereits wählbar (`bookings/_create`). Tests:
> `EventRoleAdminTest` (6).

### ADV-11 · Gamemaster/Eventleiter zuweisen · ⏱ 3h · ✅
**Beschreibung:** `gamemaster_id`/`eventleader_id` (FK users) im Formular setzbar.
**Akzeptanzkriterien:**
- [x] Auswahl berechtigter Nutzer (z. B. Spielleiter-Rolle).
- [x] Anzeige in Event-Detail.
- [x] Tests.

> Umgesetzt: Spielleiter- und Eventleiter-Select im Event-Formular (`_form`),
> befüllt mit berechtigten Nutzern (`eligibleUsers` = Rollen Spielleiter/
> Projektleitung/Teamer/Admin) inkl. „— keine(r) —"-Option. `validateAdventure`
> validiert `gamemaster_id`/`eventleader_id` (nullable, `exists:users`); leere
> Auswahl wird zu null. Anzeige im Event-Detail (Event-Tab: Spielleiter,
> Eventleiter). `show()` lädt die Relationen. Tests: `EventStaffTest` (4).

### ADV-12 · Event-Kalenderansicht · ⏱ 4h · ✅
**Beschreibung:** Kommende Events als Kalender/Liste nach Datum.
**Akzeptanzkriterien:**
- [x] Chronologische Ansicht kommender Events mit Status/Belegung.
- [x] Optional Monats-/Listen-Umschaltung.

> Umgesetzt: `AdventureController@calendar` (`GET adventures-calendar`,
> `adventure.access`) lädt kommende Events (`start_at >= heute`) chronologisch
> und gruppiert sie nach Monat. View `adventures/calendar.blade.php`:
> Monatsüberschriften (deutsche Namen), je Event Datum-Kachel, Name (öffnet das
> Event-Modal), Uhrzeit/Ort, Status-Badge und Belegung. Verlinkung „Kalender ⇄
> Listenansicht" in den Kopfzeilen. Tests: `EventCalendarTest` (3).

### ADV-13 · Event-Spieleransicht · ⏱ 4h · ✅
**Beschreibung:** Wer nur die Rollen Teilnehmer,Event-Buchen und Teamer hat, darf nur seine eigenen Spieler unter seinem Nutzer sehen.
**Akzeptanzkriterien:**
- [x] Berücksichtigen der Rolle.
- [x] Filtern der Spieler die am Event angemeldet sind.
- [x] Spieler die schon angemeldet sind, erscheinen in der Dropdown auswahl nicht mehr.

> Umgesetzt: Rolle berücksichtigt (BOOK-10 `book-any-player`: nur Bürokrat/Admin
> sehen alle, sonst eigene/betreute Spieler; Anmeldeliste rollengefiltert via
> `view-all-bookings`, ADV-15). Neu: `BookingController@create` blendet bereits
> für das Event angemeldete Spieler aus dem Dropdown aus (`whereNotIn`
> player_id); Hinweis, wenn alle wählbaren Spieler schon angemeldet sind.
> Doppelbuchung war serverseitig bereits abgewiesen. Tests:
> `BookingPlayerExclusionTest` (3) + angepasste `EventLayoutTest`.

### ADV-14 · Event-unter Verwaltung · ⏱ 4h · ✅
**Beschreibung:** Das Öffnen eines Events unter der Verwaltung soll direkt die Editieren-Ansicht des Events zeigen.
**Akzeptanzkriterien:**
- [x] Event öffnen unter Abenteuer öffnet ein Modal für die Anmeldung an einem Event
- [x] Event öffnen unter der Verwaltung-Abenteuer öffnet das Modal zum editieren des Events als solches.
- [x] Event Status dropdown soll durchnummeriert sein: 0 unbekannt, 10 in Bearbeitung, 20 geplant, 30 Anmeldung offen, 40 Anmeldung geschlossen, 50 Abrechnung, 60 Abgeschlossen, 70 abgesagt
- [x] die Spieler Check-In können nur nach Status 40 erfolgen und nur durch die Rollen Admin, Projektleitung und Bürokrat
- [x] wenn im Check-in der Button zum zuordnen von anwesenden Spielern die EP zugesichert bekommen soll, muss das auch passieren. Und im EP-Verlauf des aktiven Helden zu finden sein.
- [x] Bei der event Anmeldung vom Teilnehmer, muss neben den Spieler der passende Held, falls es einen gibt, ausgewählt werden. Wenn es keinen Helden gibt, bleibt das Feld leer mit dem Hinweis, Wende dich im nächsten Spiel an den Bürokraten.

> Umgesetzt: Anmelde-Modal (ADV-15) und Verwaltungs-Modal (ADV-16) bestanden
> bereits. Status-Nummerierung korrigiert (Migration + Seeder: 50 Abrechnung,
> 60 Abgeschlossen, 70 abgesagt; Dropdown `orderBy id`). Check-in nur ab Status
> ≥ 40 (`Adventure::checkinAllowed()`, `EventStatus::REGISTRATION_CLOSED`):
> Guard in `AttendanceController` (toggle/update/awardEp) und
> `SignatureController@update`; UI blendet Check-in-Aktionen sonst aus +
> Hinweis. Gate `manage-checkin` auf **Projektleitung/Bürokrat + Admin**
> verengt (Spielleiter/Teamer raus). EP-Vergabe (BOOK-09) bucht Typ 50 an den
> aktiven Helden → erscheint im EP-Verlauf/der Abenteuerhistorie. Anmeldung:
> Spalte `bookings.hero_id`; im Formular wird der aktive Held des gewählten
> Spielers per JS vorgewählt (ohne Held → Hinweis „… an den Bürokraten");
> Server prüft Held↔Spieler. Tests: `EventCheckinRulesTest` (5),
> `BookingHeroTest` (4) + angepasste Attendance-/Signature-/Status-Tests.


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

### ADV-20 · Event-PDf Listen Anpassung · ⏱ 4h · ✅
**Beschreibung:** Anpassung des PDF
**Akzeptanzkriterien:**
- [x] ergänze zu den Geschlechtern auch "Divers"

> Umgesetzt: `participantsPdf` zählt zusätzlich `$diverse` (gender „divers"); die
> PDF-Kopfzeile zeigt Männlich/Weiblich/**Divers**/Gesamt. Test:
> `EventGuestBookingTest::test_participants_pdf_view_shows_diverse_count`.

### ADV-21 · Event-Nutzer Anmeldung stornieren und Gäste · ⏱ 4h · ✅
**Beschreibung:** Nutzer dürfen eine Anmeldung stornieren und Gäste
**Akzeptanzkriterien:**
- [x] angemeldete User auch wenn diese schon bezahlt sind, dürfen storniert werden.
- [x] Info an den Projektleiter bei stornierung
- [x] Nutzer darf auch Gäste anmnelden, die nicht als Spieler im Account hinterlegt ist
- [x] Wenn ein Gast angemeldet werden soll, wird mit einem Freien Textfelder nach Name, Nachname, Alter, Ort abgefragt.
- [x] Es soll ein Hinweis angezeigt werden, das für Gäste kein Erfahrungspunkte gesammelt werden kann.
- [x] Unterscheidung soll über den "Anmeldung" und "GAST-Anmeldung" mit Hinweis-Popup um anzumelden im Event-Modal
- [x] Es können mehrere Gäste pro Nutzer und Event angemeldet werden.
- [x] in der Anmeldungsübersicht und im PDF Export soll eine Markierung/Hinweis auf den GAST-Status geben
- [x] bei der EP Zuteilung nach einem Event, werden Gäste nicht mit berücksichtig.

> Umgesetzt: Migration – `bookings.player_id` nullable + Gastfelder
> (`guest_name/lastname/age/place`) + `booked_by_user_id`. Stornieren (auch
> bezahlter) Anmeldungen funktioniert weiterhin (kein Block); `destroy`
> benachrichtigt die Projektleitung (`BookingCancelled`, Rolle Projektleitung +
> ggf. Eventleiter). Gast-Anmeldung über eigenen „GAST-Anmeldung"-Button →
> Unteransicht `bookings/_create_guest` (Freitext Name/Nachname/Alter/Ort +
> Rolle/Flags/AGB) mit prominentem EP-Hinweis; mehrere Gäste je Nutzer/Event
> möglich (player_id NULL, kein Unique-Konflikt). „Gast"-Label in der
> Anmeldeliste (`participant_name`/`is_guest`) und im PDF (+ Ort). Gäste haben
> keinen `event_visit`/aktiven Helden → werden bei der EP-Vergabe automatisch
> übersprungen; aus dem Check-in-Tab ausgeblendet. Eigene Gäste sind für den
> Bucher sichtbar (`booked_by_user_id` in `$visibleBookings`). Tests:
> `EventGuestBookingTest` (7).

### ADV-22 · Event-Anmeldung als Multiples modal Fenster · ⏱ 4h · ✅
**Beschreibung:** Anmeldung als Multiples Modal Fenster
**Akzeptanzkriterien:**
- [x] beim klicken auf Anmeldung, GAST-Anmeldung, Editieren-Button erscheint das Anmeldungs-Modal über dem Event-Modal. 
- [x] kein Schliess Icon oben rechts. Nur schliessbar durch Speichern oder Schließen Button
- [x] Speichern und Schließen Button sollen in den Footer des Modals zu Anmeldung.

> Umgesetzt: Zweites, gestapeltes Modal `#app-modal-2` (ohne Schließ-Icon,
> `closable:false`, `allowMultiple:true`). Neuer Trigger `data-modal-stack` lädt
> Anmeldung/Gast/Editieren als Overlay über dem Event-Modal (statt
> Content-Ersatz). Partials liefern `[data-modal-title]` (Header) und
> `[data-modal-actions]` (Speichern, per `form="…"` mit dem Formular verknüpft);
> „Schließen" wird automatisch im Footer ergänzt. Globaler Submit-Handler
> behandelt Stack-Formulare: bei Erfolg `#app-modal-2` schließen und Event-Modal
> aktualisieren. „Zurück"-Links entfernt. Tests: `EventStackedModalTest` (4).
