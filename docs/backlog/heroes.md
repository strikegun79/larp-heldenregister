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

### HERO-05 · Helden-Klassen-Lookup-CRUD (Admin) · ⏱ 3h · 🔲
**Beschreibung:** `hero_classes` ist geseedet, aber nicht pflegbar.
**Akzeptanzkriterien:**
- [ ] Admin kann Klassen anlegen/umbenennen/deaktivieren (`disabled`).
- [ ] Deaktivierte Klassen erscheinen nicht mehr in Helden-Auswahl.
- [ ] Tests.

### HERO-06 · Klassenwechsel mit EP-Kosten verbuchen · ⏱ 4h · 🔲
**Beschreibung:** Legacy `type_transEP` 40 = „Klasse hinzugefügt" (EP-Kosten).
Das Hinzufügen einer Klasse soll EP kosten und gebucht werden.
**Akzeptanzkriterien:**
- [ ] Konfigurierbare EP-Kosten je Klasse (oder Pauschale).
- [ ] Beim Hinzufügen einer Klasse wird eine EP-Transaktion (Typ 40) erzeugt.
- [ ] Saldo darf nicht negativ werden (Validierung) – oder Override für Admin.
- [ ] Tests.
**Abhängig von:** EP-02.

### HERO-07 · Held aktiv/inaktiv + aktiver Held je Spieler · ⏱ 3h · 🔲
**Beschreibung:** `players.active_hero_id` aus Legacy; den aktiven Helden
über die UI setzen.
**Akzeptanzkriterien:**
- [ ] Spieler-Ansicht erlaubt „als aktiven Helden setzen".
- [ ] Nur ein aktiver Held je Spieler.
- [ ] Tests.

### HERO-08 · Held status ändern "Erste Erblickung" & "Verschollen" als Status-Workflow · ⏱ 2h · 🔲
**Beschreibung:** `died` und 'born'-Datum existieren; Workflow + Anzeige fehlen.
**Akzeptanzkriterien:**
- [ ] Aktion „Held verstorben" auf "Verschollen" ändern und setzt `died` und deaktiviert ihn.
- [ ] Verschollene Helden im Register markiert/filterbar.
- [ ] statt geboren, soll es nun heissen: "Erste Erblickung"

### HERO-09 · Charakter-Steckbrief (Beschreibung/Bild) · ⏱ 4h · 🔲
**Beschreibung:** Erweiterung um Freitext-Hintergrund und optionales Bild
(Avatar) je Held.
**Akzeptanzkriterien:**
- [ ] Migration: `description` (text), `image` (Pfad/Disk).
- [ ] Upload + Validierung (Größe/Typ), Anzeige im Helden-Modal.
- [ ] Tests.

### HERO-10 · Heldenregister: Filter nach Klasse/Spieler/aktiv · ⏱ 3h · 🔲
**Beschreibung:** Liste filter- und sortierbar machen.
**Akzeptanzkriterien:**
- [ ] Filter Klasse, Aktiv-Status, Spieler.
- [ ] Server-seitig, paginierungsfest.
- [ ] Es soll nach Namen des Spielers oder Charakters gesucht werden können
**Abhängig von:** UI-06.

### HERO-11 · Abenteuerhistorie je Held (Vision) · ⏱ 3h · 🔲
**Beschreibung:** Welche Abenteuer ein Held bestritten hat (aus Teilnahme/
`event_visits` bzw. Buchungen des Spielers), chronologisch.
**Akzeptanzkriterien:**
- [ ] Helden-Detail listet besuchte Abenteuer (Datum, Event, ggf. erhaltene EP).
- [ ] Verknüpfung zu EP-Buchungen vom Typ „Abenteuer bestritten".
- [ ] Tests der Aggregation.
**Abhängig von:** BOOK-08, BOOK-09.

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
