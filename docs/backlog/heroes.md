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
