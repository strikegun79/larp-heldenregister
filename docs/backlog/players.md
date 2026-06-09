# Backlog · Spielerverwaltung (PLAY)

Reale Personen (Spieler), die ein Nutzer betreut (`player_user`, self-Flag).

## Inventar (✅)

### PLAY-01 · PlayerController (eigene Spieler, CRUD) · ⏱ 4h · ✅
Index/Create/Store/Show/Edit/Update/Destroy, an Nutzer gebunden.

### PLAY-02 · PlayerPolicy (Besitz-Scoping) · ⏱ 2h · ✅
Nur eigene Spieler; Admin via `Gate::before`.

### PLAY-03 · Spieler-Modal (Detailansicht) · ⏱ 2h · ✅
Detail + Heldenliste als Fomantic-Modal.

### PLAY-04 · Admin-Spielerliste · ⏱ 2h · ✅
Alle Spieler (inkl. soft-deleted), Heldenzahl, Betreuer, Matrix-Status.

## Offen (🔲)

### PLAY-05 · „self"-Spieler eindeutig erzwingen · ⏱ 2h · 🔲
**Beschreibung:** Das self-Flag markiert den eigenen Spieler; pro Nutzer sollte
es höchstens einen geben.
**Akzeptanzkriterien:**
- [ ] Beim Setzen von `self=true` werden andere self-Markierungen des Nutzers zurückgesetzt.
- [ ] Test: nur ein self-Spieler pro Nutzer.

### PLAY-06 · Mehrere Betreuer je Spieler verwalten · ⏱ 3h · 🔲
**Beschreibung:** Legacy `user2player` erlaubt mehrere Nutzer pro Spieler
(z. B. Eltern). Aktuell wird nur der anlegende Nutzer verknüpft.
**Akzeptanzkriterien:**
- [ ] Admin kann einem Spieler weitere Betreuer zuordnen/entfernen.
- [ ] Betreuer sehen den Spieler in „Deine Spieler".
- [ ] Tests für Zuordnung/Entfernen.

### PLAY-07 · Pflichtangaben für Minderjährige (Geburtsdatum) · ⏱ 3h · 🔲
**Beschreibung:** Jugend-LARP – Alter ist relevant (Event-Kategorien nach Alter).
**Akzeptanzkriterien:**
- [ ] Geburtsdatum-Validierung (plausibel, nicht Zukunft).
- [ ] Helper `Player::age()` (Accessor) verfügbar.
- [ ] Anzeige des Alters in Spieler-Modal/Admin-Liste.

### PLAY-08 · Spieler-Soft-Delete + Wiederherstellung im Admin · ⏱ 3h · 🔲
**Beschreibung:** Gelöschte Spieler erscheinen in der Admin-Liste; Restore fehlt.
**Akzeptanzkriterien:**
- [ ] Admin kann soft-gelöschte Spieler wiederherstellen.
- [ ] Löschen prüft offene Buchungen/aktive Helden (Warnung).
- [ ] Tests.

### PLAY-09 · Spielerliste: Suche & Sortierung · ⏱ 3h · 🔲
**Beschreibung:** Filter nach Name/aktiv; Sortierung.
**Akzeptanzkriterien:**
- [ ] Volltext-Suche (Name/Nachname) in eigener + Admin-Liste.
- [ ] Sortierbare Spalten, paginierte Ergebnisse bleiben erhalten.
**Abhängig von:** UI-Suche (UI-06).
