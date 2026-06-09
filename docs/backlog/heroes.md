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

### HERO-08 · Held „verstorben" (died) als Status-Workflow · ⏱ 2h · 🔲
**Beschreibung:** `died`-Datum existiert; Workflow + Anzeige fehlen.
**Akzeptanzkriterien:**
- [ ] Aktion „Held verstorben" setzt `died` und deaktiviert ihn.
- [ ] Verstorbene Helden im Register markiert/filterbar.

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

### HERO-14 · Helden-Klassen-Fertigkeitsbaum · ⏱ 3h · 🔲
**Beschreibung:** In der Heldenansicht soll ein UI Fomantic Tab stehen und jeder aktivierte Klasse ist ein Tab mit dem Fertigkeitsbaum.
**Akzeptanzkriterien:**
- [ ] Mindestens ein Tab existiert, weil eine Klasse immer Ausgewählt werden sein muss bei der Erstellung.
- [ ] Beim anklicken einer Fertigkeit, soll ein Modal erscheinen mit der Beschreibung der Fertigkeit und die Optionen "Fertigkeit errungen" oder "Noch nicht" (Accept/Deny).
- [ ] Beim Accept, soll die Fertigkeit per AJAX gebucht werden und die EP entsprechend verbraucht in der Hostorie des Helden.
- [ ] Verfügbare EP Anzahl muss im Modal angezeigt werden.
- [ ] Fertigkeitsbäume sind JPG Bilder, für jede Klasse gibt es eins. Unter /public/images/skilltree_*.jpg
- [ ] Tests.
