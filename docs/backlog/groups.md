# Backlog · Gruppenverwaltung (GRP)

Vision: **Gruppenverwaltung** – LARP-Gruppen/Trupps, denen Helden/Spieler
angehören. (Quelle: [../vision.md](../vision.md).) Im Legacy nicht vorhanden →
neues Datenmodell.

## Offen (🔲)

### GRP-01 · Gruppen-Schema + Model · ⏱ 3h · ✅
**Beschreibung:** Entität „Gruppe" (Trupp/Gilde) mit Name, Beschreibung, ggf. Bild.
**Akzeptanzkriterien:**
- [x] Migration `groups` + Model; `group_hero` Pivot (Mitgliedschaft auf Heldenebene).
- [x] Entscheidung: Heldenebene (`group_hero`) — Gruppen sind LARP-Gilden/Trupps für Charaktere.
- [x] Factory + 7 Tests (Erstellung, Relationen, Pivot, Cascade-Delete).

### GRP-02 · Gruppen-CRUD (Verwaltung) · ⏱ 3h · ✅
**Beschreibung:** Anlegen/Bearbeiten/Löschen von Gruppen.
**Akzeptanzkriterien:**
- [x] Controller + Views (Liste/Modal/Formular) unter `/admin/groups`.
- [x] Berechtigung `groups.manage` (Admin, Bürokrat, Spielleiter); eigener Route-Block außerhalb `portal.manage`.
- [x] 12 Tests (CRUD, Zugriffsschutz, AJAX-Formular, Cascade).
**Abhängig von:** GRP-01.

### GRP-03 · Mitglieder verwalten · ⏱ 3h · ✅
**Beschreibung:** Helden/Spieler einer Gruppe zuordnen/entfernen, Rolle in der
Gruppe (z. B. Anführer) optional.
**Akzeptanzkriterien:**
- [x] Mitglieder hinzufügen/entfernen über Modal.
- [x] Optional Gruppenrolle (Anführer/Mitglied).
- [x] Tests.
**Abhängig von:** GRP-01.

### GRP-04 · Gruppenansicht (Mitglieder, Helden) · ⏱ 3h · ✅
**Beschreibung:** Detailseite einer Gruppe mit Mitgliedern und deren Helden.
**Akzeptanzkriterien:**
- [x] Gruppen-Detail listet Mitglieder + Helden (verlinkt auf heroes.show).
- [x] Kennzahlen (Anzahl Mitglieder), Spalten: Held, Spieler, Klasse(n), EP, Rolle, Beigetreten.
- [x] Mobil als Karten (x-mobile.cards-or-table), Leerzustand mit CTA.

### GRP-05 · Gruppe in Heldenansicht & öffentlicher Seite · ⏱ 2h · 🔲
**Beschreibung:** Gruppenzugehörigkeit beim Helden anzeigen (auch öffentlich,
ohne Realnamen).
**Akzeptanzkriterien:**
- [ ] Helden-Detail zeigt Gruppe(n).
- [ ] Öffentliche Heldenseite zeigt Gruppennamen (keine Personendaten).
**Abhängig von:** GRP-01, PUB-02.

### GRP-06 · Gruppen-basierte Event-Buchung (optional) · ⏱ 4h · 🔲
**Beschreibung:** Mehrere Mitglieder einer Gruppe gesammelt zu einem Event anmelden.
**Akzeptanzkriterien:**
- [ ] Sammelanmeldung einer Gruppe (Auswahl der teilnehmenden Mitglieder).
- [ ] Respektiert Kapazität/Warteliste je Einzelbuchung.
- [ ] Tests.
**Abhängig von:** GRP-03, BOOK-02.
