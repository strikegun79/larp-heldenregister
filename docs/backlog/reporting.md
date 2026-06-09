# Backlog · Auswertungen & Exporte (REP)

Statistiken und Exporte (ersetzt u. a. Legacy-View `view_heroT1Statistic`).

## Offen (🔲)

### REP-01 · Helden-Statistik (EP, Fertigkeiten, Klassen) · ⏱ 4h · 🔲
**Beschreibung:** Kennzahlen je Held (Gesamt-EP, ausgegebene EP, Anzahl
Fertigkeiten, Klassen) – ersetzt die Legacy-Statistik-View.
**Akzeptanzkriterien:**
- [ ] Aggregierte Kennzahlen je Held (über Accessoren/Query).
- [ ] Anzeige im Helden-Modal/Detail.
- [ ] Tests der Aggregationslogik.

### REP-02 · EP-Konto-Auszug (Export) · ⏱ 3h · 🔲
**Beschreibung:** Buchungsverlauf eines Helden als CSV/PDF exportieren.
**Akzeptanzkriterien:**
- [ ] Export-Endpoint (CSV mind.); Spalten Datum/Art/Betrag/Saldo.
- [ ] Berechtigung; Test.

### REP-03 · Teilnahme-/Belegungsreport je Event · ⏱ 3h · 🔲
**Beschreibung:** Übersicht Buchungen vs. Teilnahme, bezahlt/offen, Warteliste.
**Akzeptanzkriterien:**
- [ ] Report je Event mit Summen (Plätze, Warteliste, bezahlt, offen).
- [ ] Export (CSV).
**Abhängig von:** BOOK-06, BOOK-08.

### REP-04 · Mitglieder-/Spielerübersicht (Export) · ⏱ 3h · 🔲
**Beschreibung:** Liste aller Spieler mit Helden/Alter für Orga.
**Akzeptanzkriterien:**
- [ ] Gefilterte Liste exportierbar (CSV).
- [ ] DSGVO-konform (nur nötige Felder); Berechtigung.

### REP-05 · Charakterbogen-PDF je Held · ⏱ 4h · 🔲
**Beschreibung:** Druckbarer Charakterbogen (Stammdaten, Klassen, Fertigkeiten,
Perlen, EP) als PDF.
**Akzeptanzkriterien:**
- [ ] PDF-Generierung (z. B. dompdf) mit Vereins-Layout.
- [ ] Download im Helden-Detail; Test (PDF wird erzeugt).
**Abhängig von:** EP-07.

### REP-06 · Dashboard-Kennzahlen für Admin · ⏱ 3h · 🔲
**Beschreibung:** Startseiten-Widgets (Anzahl Spieler/Helden/kommende Events/
offene Buchungen) für Admin.
**Akzeptanzkriterien:**
- [ ] Kennzahl-Karten auf dem Dashboard (nur Admin).
- [ ] Effiziente Queries (kein N+1); Test.
