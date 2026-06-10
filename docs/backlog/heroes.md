# Backlog · Helden & Klassen (HERO)

Charaktere eines Spielers, mit Klassen, Fertigkeiten und EP.
(EP-/Skill-Logik im Detail: siehe [skills-ep.md](skills-ep.md).)

## Inventar (✅)

### HERO-01 · Heroes-Schema + Model + Relationen · ⏱ 4h · ✅
`heroes`, Pivots zu Klassen/Skills, EP-Transaktionen.

### HERO-02 · HeroController (CRUD) + Klassen-Auswahl · ⏱ 4h · ✅
Anlegen/Bearbeiten inkl. Klassen-Checkboxen; `ep_balance`/`class_list`-Accessoren.

### HERO-03 · Helden-Modal (Detail, AJAX) · ⏱ 2h · ✅
Stammdaten, Fertigkeiten, EP-Verlauf im Modal.

### HERO-04 · Rechte (view/edit) für Heldenregister · ⏱ 2h · ✅
`heldenregister.view` / `heldenregister.edit`.

## Offen (🔲)

### HERO-05 · Helden-Klassen-Lookup-CRUD (Admin) · ⏱ 3h · ✅
**Beschreibung:** `hero_classes` ist geseedet, aber nicht pflegbar.
**Akzeptanzkriterien:**
- [x] Admin kann Klassen anlegen/umbenennen/deaktivieren (`disabled`).
- [x] Deaktivierte Klassen erscheinen nicht mehr in Helden-Auswahl.
- [x] Tests.

> Umgesetzt: `Admin\HeroClassController` (index/create/store/edit/update) im
> Admin-Bereich unter `can:portal.manage` (= nur Admin). Modal-Formular
> `admin/hero_classes/_form.blade.php` (Name, Slug, Deaktiviert); Listenseite
> mit Heldenzähler + „Neue Klasse"; Karte „Helden-Klassen" in der Verwaltung.
> IDs fortlaufend (`max(id)+1`, da `hero_classes.id` legacy-bedingt nicht
> auto-inkrementiert). Slug eindeutig (Self-Ignore beim Update). Deaktivierte
> Klassen sind in `HeroController@create/@edit` bereits durch
> `where('disabled', false)` aus der Auswahl ausgeschlossen.
> Tests: `HeroClassAdminTest` (6).

### HERO-06 · Klassenwechsel mit EP-Kosten verbuchen · ⏱ 4h · ✅
**Beschreibung:** Legacy `type_transEP` 40 = „Klasse hinzugefügt" (EP-Kosten).
Das Hinzufügen einer Klasse soll EP kosten und gebucht werden.
**Akzeptanzkriterien:**
- [x] Konfigurierbare EP-Kosten je Klasse (oder Pauschale).
- [x] Beim Hinzufügen einer Klasse wird eine EP-Transaktion (Typ 40) erzeugt.
- [x] Saldo darf nicht negativ werden (Validierung) – oder Override für Admin.
- [x] Tests.
**Abhängig von:** EP-02.

> Umgesetzt: Spalte `hero_classes.ep_cost` (Migration, Standard 50, je Klasse
> im Admin pflegbar – HERO-05-Formular). `HeroClassController` (analog
> `HeroSkillController`, `can:heldenregister.edit`):
> `POST heroes/{hero}/classes` bucht Typ-40-Kosten und hängt die Klasse an;
> `DELETE heroes/{hero}/classes/{heroClass}` entfernt sie und erstattet (Typ 60).
> Saldo-Schutz: Nicht-Admin wird bei zu wenig EP abgewiesen (422), **Admin
> übersteuert** (Saldo darf ins Minus). Deaktivierte/bereits vorhandene Klassen
> werden abgelehnt. UI: Klassenverwaltung im Helden-Detail (Chips mit Entfernen-
> ×, Auswahl + „Hinzufügen"). Startklassen bei der Neuanlage bleiben kostenfrei
> (Charaktererstellung); das Helden-Bearbeiten-Formular synct keine Klassen mehr.
> Tests: `HeroClassAssignmentTest` (7) + angepasste `HeroClassAdminTest`.

### HERO-07 · Held aktiv/inaktiv + aktiver Held je Spieler · ⏱ 3h · ✅
**Beschreibung:** `players.active_hero_id` aus Legacy; den aktiven Helden
über die UI setzen.
**Akzeptanzkriterien:**
- [x] Spieler-Ansicht erlaubt „als aktiven Helden setzen".
- [x] Nur ein aktiver Held je Spieler.
- [x] Tests.

> Umgesetzt: `PlayerController@setActiveHero` (`PATCH players/{player}/active-hero`,
> `PlayerPolicy:update`); im Spieler-Detail je Held „Aktiv setzen" bzw.
> grünes „aktiv"-Label. Held muss zum Spieler gehören (sonst 422). Nur ein
> aktiver Held (single `active_hero_id`, wird beim Setzen ersetzt). AJAX +
> Modal-Refresh. Tests: PlayerTest (3).

### HERO-08 · Held status ändern "Erste Erblickung" & "Verschollen" als Status-Workflow · ⏱ 2h · ✅
**Beschreibung:** `died` und 'born'-Datum existieren; Workflow + Anzeige fehlen.
**Akzeptanzkriterien:**
- [x] Aktion „Held verstorben" auf "Verschollen" ändern und setzt `died` und deaktiviert ihn.
- [x] Verschollene Helden im Register markiert/filterbar.
- [x] statt geboren, soll es nun heissen: "Erste Erblickung"

> Umgesetzt: `HeroController@toggleMissing` (`PATCH heroes/{hero}/missing`,
> `heldenregister.edit`) setzt `died` + `active=false` (bzw. macht rückgängig);
> Button im Übersicht-Tab (AJAX, Modal-Refresh). Index: Status-Filter
> Alle/Aktive/Verschollene (`?status=`) und „verschollen"-Markierung (rote
> Schrift, abgedunkelte Zeile). Labels „Geboren→Erste Erblickung",
> „Gestorben→Verschollen" in Formular & Detail. Tests: HeroTest (4).

### HERO-09 · Charakter-Steckbrief (Beschreibung/Bild) · ⏱ 4h · 🔲
**Beschreibung:** Erweiterung um Freitext-Hintergrund und optionales Bild
(Avatar) je Held.
**Akzeptanzkriterien:**
- [ ] Migration: `description` (text), `image` (Pfad/Disk).
- [ ] Upload + Validierung (Größe/Typ), Anzeige im Helden-Modal.
- [ ] Tests.

### HERO-10 · Heldenregister: Filter nach Klasse/Spieler/aktiv · ⏱ 3h · ✅
**Beschreibung:** Liste filter- und sortierbar machen.
**Akzeptanzkriterien:**
- [x] Filter Klasse, Aktiv-Status, Spieler.
- [x] Server-seitig, paginierungsfest.
- [x] Es soll nach Namen des Spielers oder Charakters gesucht werden können
- [x] Suche findet auch Helden über erlernte Fertigkeiten (wer den Skill besitzt)

> Umgesetzt: Filterleiste über der Liste (GET): Suche (`q`, Charakter- ODER
> Spielername ODER erlernte Fertigkeit via `whereHas('skills')`), Klasse
> (`class_id`, `whereHas`), Spieler (`player_id`), Status
> (`active`/`inactive`/`missing`). Alles serverseitig kombinierbar,
> `paginate()->withQueryString()`. Tests: HeroTest (Suche inkl. Fertigkeit,
> Klasse, Spieler, Status).

### HERO-11 · Abenteuerhistorie je Held (Vision) · ⏱ 3h · ✅
**Beschreibung:** Welche Abenteuer ein Held bestritten hat (aus Teilnahme/
`event_visits` bzw. Buchungen des Spielers), chronologisch.
**Akzeptanzkriterien:**
- [x] Helden-Detail listet besuchte Abenteuer (Datum, Event, ggf. erhaltene EP).
- [x] Verknüpfung zu EP-Buchungen vom Typ „Abenteuer bestritten".
- [x] Tests der Aggregation.
**Abhängig von:** BOOK-08, BOOK-09.

> Umgesetzt: Migration `add_adventure_id_to_ep_transactions` (nullable FK) +
> `EpTransaction::adventure()`. `Hero::adventure_history` (EP-Buchungen Typ 50
> mit Abenteuer, chronologisch) + `adventures_ep_total`. Abenteuer-Tab im
> Detail zeigt „Bestrittene Abenteuer" (Datum/Event/EP + Summe) und darunter
> die Spieler-Anmeldungen. Tests: HeroTest-Aggregation.
>
> Hinweis: Befüllt wird die Historie, sobald BOOK-09 nach Teilnahme EP vom
> Typ 50 mit `adventure_id` bucht (Fundament dafür hiermit gelegt).

### HERO-12 · Helden-EP-anpassen · ⏱ 3h · ✅
**Beschreibung:** In der Heldenansicht soll ein Feld für die zuweisung oder verbrauch von EP möglich sein.
**Akzeptanzkriterien:**
- [x] Ein Input Feld für die Anzahl von EP, Minus oder Plus. dann eine Dropdown Auswahl für den Grund. Aus der ep_transaction_types Tabelle. Dann ein Button zum eintragen, per AJAX und dann leeren der Input und dropdown auswahl. Neu laden der EP Historie.
- [x] TEsts.

> Umgesetzt: `EpTransactionController@store` (`POST heroes/{hero}/ep`, Berechtigung
> `heldenregister.edit`); Buchungsformular im Helden-Modal (`heroes/_detail`);
> Vorzeichen aus `ep_transaction_types.is_credit`; AJAX-Submit mit Toast +
> Modal-Teil-Refresh (`refresh_modal`) lädt EP-Historie neu und leert das
> Formular. Tests: `EpTransactionTest` (4).

### HERO-14 · Helden-Klassen-Fertigkeitsbaum · ⏱ 3h · ✅
**Beschreibung:** In der Heldenansicht soll ein UI Fomantic Tab stehen und jeder aktivierte Klasse ist ein Tab mit dem Fertigkeitsbaum.
**Akzeptanzkriterien:**
- [x] Mindestens ein Tab existiert, weil eine Klasse immer Ausgewählt werden sein muss bei der Erstellung.
- [x] Beim anklicken einer Fertigkeit, soll ein Modal erscheinen mit der Beschreibung der Fertigkeit und die Optionen "Fertigkeit errungen" oder "Noch nicht" (Accept/Deny).
- [x] Beim Accept, soll die Fertigkeit per AJAX gebucht werden und die EP entsprechend verbraucht in der Hostorie des Helden.
- [x] Verfügbare EP Anzahl muss im Modal angezeigt werden.
- [x] Fertigkeitsbäume sind JPG Bilder, für jede Klasse gibt es eins. Unter /public/images/skilltree_*.jpg
- [x] Tests.

> Umgesetzt: Fomantic-Tabs je Helden-Klasse im Detail-Modal mit dem
> Skilltree-Bild (`HeroClass::skilltreeImage()`, Slug-Mapping wizard→mage) und
> einer klickbaren Fertigkeitsliste (gelernte grün markiert). Klick öffnet ein
> Bestätigungs-Modal (Beschreibung, Kosten, **verfügbare EP**, Accept/Deny).
> Accept → `HeroSkillController@store` (`POST heroes/{hero}/skills`,
> `heldenregister.edit`): legt Pivot an und bucht EP (Typ 20) ab, atomar; prüft
> Doppel-Lernen & EP-Deckung. AJAX + Toast + Modal-Refresh. Tests: `HeroSkillTest` (4).

### HERO-15 · Helden-Übersicht · ⏱ 3h · ✅
**Beschreibung:** Anpassen der Heldenübersicht (vormals doppelt als HERO-14 geführt).
**Akzeptanzkriterien:**
- [x] Der Spielername soll in der ersten Spalte stehen, dann der Charaktername, die EP gesamt und verfügbaren, dann die Klassen und dann Aktivstatus
- [x] Per Klick auf die Zeile soll das Modalfenster öffnen und die Heldendetails zeigen. kein Bearbeiten knopf.

> Umgesetzt: Spalten neu geordnet (Spieler · Charakter · EP gesamt · EP
> verfügbar · Klassen · Aktiv). `Hero::ep_total` (Summe Gutschriften) neben
> `ep_balance`. Ganze Zeile ist klickbar (`data-modal-url` am `<tr>`) und öffnet
> das Detail-Modal; der Bearbeiten-Link in der Liste wurde entfernt
> (Bearbeiten weiterhin im Detail-Modal). Test: HeroTest-Overview.

### HERO-16 · Helden-Fertigkeitsbaum Checkboxen · ⏱ 3h · ✅
**Beschreibung:** Postion der Checkbox und aktion im Fertigkeitsbaum
**Akzeptanzkriterien:**
- [x] jede Fertigkeit der Klasse bekommt auf dem Bild des Fertigkeitsbaum ein button.
- [x] wenn eine button angeklickt wird, erscheint das Modal. Egal ob aktiviert oder nicht.
- [x] wenn die Fertigkeit schon erlernt wurde, kann diese mit dem Knopf "Fertigkeit aberkennen" deaktiviert werden und die EP werden wieder zurück gegeben.
- [x] die Auflistung unterhalb des Fertigkeitsbaum, soll nur erscheinen, wenn die Ansicht unter 1100 pixel breite ist.
- [x] die Postion des button auf dem Bild wird in der Datenbank unter der Tabelle skill_hero_class als x_percentage und y_percentage gespeichert, da mehrfach verwendete Skills auf Klassen unterschiedliche Positionen haben können.
- [x] der Button soll visual anzeigen, ob die Fertigkeit erlernt ist oder nicht

> Umgesetzt: Migration `add_position_to_skill_hero_class` (x/y_percentage,
> idempotent); Relationen `withPivot`. Marker (`.skill-marker`) je Skill am
> gespeicherten x/y-% auf dem Baum-Bild, grün=erlernt / gold=offen
> (`public/css/heldenregister.css`). Klick (Marker oder Liste) öffnet das
> Modal egal ob erlernt; je nach Status „Fertigkeit errungen" (Lernen) oder
> „Fertigkeit aberkennen" (`HeroSkillController@destroy`, EP-Rückerstattung
> Typ 60). Textliste nur < 1100 px (`.skill-list`). Tests: `HeroSkillTest` (7).
>
> Offen (Folgeaufgabe): UI zum **Setzen** der Marker-Positionen (Drag&Drop);
> bislang spreizen sich unkonfigurierte Marker per Default-Raster.

### HERO-17 · Helden-Detail Layout · ⏱ 3h · ✅
**Beschreibung:** Die Heldenansicht anpassen
**Akzeptanzkriterien:**
- [x] Im Modal Content sollen Tabs eingesetzt werden.
- [x] Erstes Tab ist die Übersicht. Spieler, Klassen, Heimatort, EP-Saldo, Geboren, Gestorben, Status, alle erworbenden Fertigkeiten.
- [x] Zweiter Tab die Besuchten Abenteuerübersicht.
- [x] weitere Tabs sind die Fertigkeitsbäume pro Klasse
- [x] letztes Tab ist der EP-Verlauf. Das Einstellen von EP und der EP-Verlauf soll hier angezeigt werden.

> Umgesetzt: Detail-Modal in Fomantic-Tabs gegliedert – Übersicht ·
> Abenteuer · [Fertigkeitsbaum je Klasse] · EP-Verlauf. Abenteuer-Tab zeigt
> die Anmeldungen des Spielers (`Player::bookings`; per-Held-Historie ⇒ HERO-11).
> EP-Buchen + EP-Verlauf im letzten Tab. Aktiver Tab bleibt nach AJAX-Aktionen
> (EP/Skill) erhalten (`loadModalContent(url, true)`). Test: HeroTest-Tabs.

### HERO-18 · Fertigkeitsbaum-Positions-Editor · ⏱ 3h · ✅
**Beschreibung:** Drag&Drop-Editor zum Setzen der Marker-Positionen je Klasse
(ursprünglich im Chat als „HERO-17" angefragt; HERO-17 war im Backlog bereits
für das Detail-Layout vergeben).
**Akzeptanzkriterien:**
- [x] Eigene Editor-Seite je Klasse mit dem Baum-Bild und ziehbaren, nummerierten Markern.
- [x] Position wird per Drag&Drop gesetzt und als x/y-% in `skill_hero_class` gespeichert.
- [x] Nur mit `heldenregister.edit`; Einstieg aus dem Helden-Detail je Klassen-Tab.
- [x] Tests.

> Umgesetzt: `SkilltreeController` (`GET skilltree/{class}/edit`,
> `PATCH skilltree/{class}`); Editor-View mit Pointer-Drag (vanilla JS, via
> `@push('scripts')`/`@stack`), Speichern per AJAX + Toast; Validierung 0–100 %.
> Link „Positionen bearbeiten" je Klassen-Tab im Helden-Detail. Tests: `SkilltreeTest` (5).

### HERO-19 · Helden-detail Modal · ⏱ 3h · ✅
**Beschreibung:** Die Heldenansicht Modal soll immer eine einheitliche Größe haben um zuvermeiden dass das Modal hin und her springt , wenn man die Tab wechselt.
**Akzeptanzkriterien:**
- [x] Mind. 950px x 950px
- [x] Der button "Positionen bearbeiten" soll unterhalb der Fertigkeitenbaums stehen

> Umgesetzt: Klasse `modal-hero` (in `loadModalContent` gesetzt, sobald
> `#skilltree` im Inhalt ist) gibt dem Detail-Modal Breite 950px (max 95vw)
> und Inhalt-`min-height` 840px → kein Springen beim Tab-Wechsel. Der Button
> „Positionen bearbeiten" steht jetzt unterhalb des Baums/der Liste je
> Klassen-Tab. Test: HeroTest-Reihenfolge.

### HERO-20 Helden-Klasse hinzufügen · ✅
**Beschreibung:** Die EP für eine neue Klasse dem Held hinzuzufügen
- [x] Die EP für eine neue Klasse dem Held hinzuzufügen kostet 5 EP
- [x] Es soll abgefragt werden, ob die EP wirklich abgezogen werden soll, da wenn es ein Fehler war soll man auch für 0 EP wieder hinzufügen.

> Umgesetzt: Standard-Klassenkosten auf **5 EP** gesenkt (Migration: Default 5 +
> bestehende 50→5; Admin-Formular-Default 5). Im Helden-Detail zwei Buttons:
> „Hinzufügen" (mit `confirm`-Abfrage „EP-Kosten wirklich abziehen?") und
> „Korrektur (0 EP)" – Letzteres fügt über `free=1` ohne EP-Abzug/Saldo-Prüfung
> hinzu (`HeroClassController@store`, keine Typ-40-Buchung). Modal-Submit
> respektiert jetzt `defaultPrevented`, sodass abgebrochene `confirm`-Dialoge
> kein AJAX auslösen (gilt auch für bestehende Storno-/EP-Bestätigungen).
> Tests: `HeroClassAssignmentTest` (Standardkosten 5, Korrektur ohne Abzug,
> Korrektur trotz fehlender EP).
