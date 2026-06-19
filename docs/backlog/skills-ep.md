# Backlog · Fertigkeiten & EP-Ökonomie (SKILL / EP)

Das LARP-Kernfeature: Helden lernen Fertigkeiten gegen Erfahrungspunkte.
Datenmodell vorhanden (`skills`, `hero_skill`, `skills2class` →
`skill_hero_class`, `ep_transactions`, `ep_transaction_types`, `perl_colors`),
**Oberfläche fehlt fast vollständig** (`SkillController` ist ein Stub).

## Inventar (✅)

### SKILL-01 · Skills-Schema + Model + Relationen · ⏱ 3h · ✅
`skills` mit Kosten/Level/Masterclass/Perlenfarbe; Pivots; 78 Datensätze migriert.

### EP-01 · EP-Transaktionsbuch + Saldo-Accessor · ⏱ 3h · ✅
`ep_transactions` + `EpTransactionType.is_credit`; `Hero::ep_balance`.

## Offen (🔲)

### SKILL-02 · Fertigkeiten-Verwaltung CRUD (Admin) · ⏱ 4h · ✅
**Beschreibung:** `SkillController` ausbauen (aktuell Stub): Liste + Anlegen/
Bearbeiten/Löschen von Fertigkeiten.
**Akzeptanzkriterien:**
- [x] Routen + Controller-Methoden + Views (Liste, Formular, Modal).
- [x] Felder: Name, Beschreibung, EP-Kosten, Level, Masterclass, Perlenfarbe, Perlenanzahl.
- [x] Nur mit `heldenregister.edit` (oder eigener `skills.manage`) editierbar.
- [x] Tests.

### SKILL-03 · Skill↔Klasse-Zuordnung pflegen · ⏱ 3h · ✅
**Beschreibung:** `skill_hero_class` (welche Klasse welche Fertigkeit lernen darf).
**Akzeptanzkriterien:**
- [x] Im Skill-Formular Klassen-Mehrfachauswahl.
- [x] Fertigkeitsliste je Klasse filterbar.
- [x] Tests für Sync.

### SKILL-04 · Fertigkeiten-Katalog (read-only, nach Klasse) · ⏱ 3h · ✅
**Beschreibung:** Übersichtsseite aller Fertigkeiten gruppiert nach Klasse/Level,
inkl. Perlenfarbe – für Spieler zum Stöbern.
**Akzeptanzkriterien:**
- [x] Gruppierte Ansicht nach Klasse, je Klasse nach Level/Name sortiert; filterbar per Klassen-Dropdown.
- [x] Perlenfarbe visuell (Farbpunkt + Name) dargestellt.

### EP-02 · EP-Buchung manuell (Admin/Bürokrat) · ⏱ 4h · ✅
**Beschreibung:** Oberfläche zum Gutschreiben/Abziehen von EP mit Buchungsart
(`type_transEP`).
**Akzeptanzkriterien:**
- [x] Formular: Held, Betrag, Buchungsart, Datum.
- [x] Erzeugt `ep_transactions`-Eintrag; Saldo aktualisiert sich.
- [x] Berechtigung (`heldenregister.edit`/Admin); Tests.

### EP-03 · Fertigkeit lernen mit EP-Abzug · ⏱ 4h · ✅
**Beschreibung:** Held lernt eine Fertigkeit → Pivot `hero_skill` + EP-Transaktion
(Typ 20 „Fertigkeit erworben") über die EP-Kosten.
**Akzeptanzkriterien:**
- [x] Auswahl nur erlaubter (Klassen-passender, noch nicht gelernter) Fertigkeiten.
- [x] Saldo-Prüfung (genug EP) – sonst Fehler-Toast.
- [x] `trained_at` gesetzt; Transaktion verbucht; atomar (DB-Transaktion).
- [x] Tests (Erfolg, zu wenig EP, Doppel-Lernen).
**Abhängig von:** SKILL-02, EP-01.

### EP-04 · Fertigkeit entfernen / EP-Korrektur · ⏱ 3h · ✅
**Beschreibung:** Legacy „Bändchen verloren" (Typ 30). Entfernen einer Fertigkeit
und ggf. EP-Korrektur.
**Akzeptanzkriterien:**
- [x] Admin kann gelernte Fertigkeit entfernen.
- [x] Optionale Rückbuchung als EP-Transaktion (Typ 60 Allgemein-Gutschrift).
- [x] Tests.

### EP-05 · Perlenfarben-Lookup-CRUD · ⏱ 2h · ✅
**Beschreibung:** `perl_colors` pflegbar machen (für Fertigkeits-Visualisierung).
**Akzeptanzkriterien:**
- [ ] Admin-CRUD für Perlenfarben (Code/Hex + Name).
- [ ] Verwendung in Skill-Formular und Katalog.

### EP-06 · EP-Buchungsarten-Lookup-CRUD · ⏱ 2h · ✅
**Beschreibung:** `ep_transaction_types` pflegbar (Beschreibung, is_credit).
**Akzeptanzkriterien:**
- [ ] Admin-CRUD; Schutz der system-genutzten IDs (10–70) vor Löschung.
- [ ] Tests.

### EP-07 · Perlen-Übersicht je Held (Bändchen-Liste) · ⏱ 3h · ✅
**Beschreibung:** Aus gelernten Fertigkeiten die benötigten Perlen/Bändchen je
Farbe summieren (LARP-Repräsentation).
**Akzeptanzkriterien:**
- [x] Helden-Modal zeigt Perlen je Farbe (Anzahl).
- [x] Summenlogik getestet.

## Fertigkeiten-Baum (Vision: „Fertigkeiten-Baum pro Klasse")

### SKILL-05 · Voraussetzungen-Datenmodell (Skill-Tree) · ⏱ 4h · ✅
**Beschreibung:** Fertigkeiten bauen aufeinander auf. Modellierung von
Voraussetzungen (Skill → benötigt Skill(s) / Mindest-Level).
**Akzeptanzkriterien:**
- [x] `skill_prerequisites` (skill_id, required_skill_id) oder Level-Schwelle.
- [x] Model-Relationen + Validierung (keine Zyklen).
- [x] Tests.

> Umgesetzt: Migration `skill_prerequisites` (composite PK + FK cascadeOnDelete).
> `Skill::prerequisites()` (BelongsToMany auf sich selbst) + `prerequisiteFor()`
> (Inverse). Admin-Formular `_form.blade.php`: Checkbox-Liste aller anderen
> Fertigkeiten, gruppiert nach Klasse. `SkillController`: `store`/`update` synct
> Voraussetzungen; BFS-Zyklen-Prüfung in `hasCycle()` lehnt direkte und transitive
> Kreisbezüge mit 422 ab. Tests: `SkillPrerequisiteTest` (9).

### SKILL-06 · Voraussetzungen beim Lernen prüfen · ⏱ 3h · 🔲
**Beschreibung:** Eine Fertigkeit ist nur lernbar, wenn ihre Voraussetzungen
beim Helden erfüllt sind.
**Akzeptanzkriterien:**
- [ ] Lern-Aktion (EP-03) lehnt Fertigkeiten ohne erfüllte Voraussetzungen ab.
- [ ] UI zeigt gesperrte Fertigkeiten mit Begründung.
- [ ] Tests (gesperrt/freigeschaltet).
**Abhängig von:** SKILL-05, EP-03.

### SKILL-07 · Fertigkeiten-Baum-Visualisierung je Klasse · ⏱ 4h · 🔲
**Beschreibung:** Grafische/strukturierte Darstellung des Skill-Trees pro Klasse
(Level-Ebenen, Abhängigkeiten), inkl. „gelernt"-Markierung für einen Helden.
**Akzeptanzkriterien:**
- [ ] Baum-/Spalten-Ansicht je Klasse (nach Level/Abhängigkeit).
- [ ] Für einen Helden: gelernt / lernbar / gesperrt farblich unterschieden.
- [ ] Perlenfarbe je Knoten dargestellt.
**Abhängig von:** SKILL-05, SKILL-03.

### SKILL-08 · Fertigkeiten-Katalog mit Symbolen · ⏱ 3h · ✅
**Beschreibung:** Übersichtsseite aller Fertigkeiten mit den passenden Symbolen ergänzen.
**Akzeptanzkriterien:**
- [] möglichkeit zum hochladen des Symbols für die Fertigkeit
- [] nur png oder jpg
- [] reduziere das Symbol beim hochladen auf 100x100 pixel