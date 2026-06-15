# Backlog · Auswertungen & Exporte (REP)

Statistiken und Exporte (ersetzt u. a. Legacy-View `view_heroT1Statistic`).

## Erledigt (✅)

### REP-01 · Helden-Statistik (EP, Fertigkeiten, Klassen) · ⏱ 4h · ✅
**Akzeptanzkriterien:**
- [x] Aggregierte Kennzahlen je Held (über Accessoren/Query).
- [x] Anzeige im Helden-Modal/Detail.
- [x] Tests der Aggregationslogik.

> Accessoren `Hero::ep_spent` (= ep_total − ep_balance), `skills_count`,
> `classes_count` (zusätzlich zu bestehenden ep_total/ep_balance). Anzeige im
> Übersicht-Tab (EP gesamt/ausgegeben, Fertigkeiten/Klassen). Tests:
> `HeroStatisticsTest` (2).

### REP-02 · EP-Konto-Auszug (Export) · ⏱ 3h · ✅
**Akzeptanzkriterien:**
- [x] Export-Endpoint (CSV mind.); Spalten Datum/Art/Betrag/Saldo.
- [x] Berechtigung; Test.

> `HeroController@epExport` (`GET heroes/{hero}/ep-export`, `heldenregister.view`):
> CSV (`;`, UTF-8-BOM) mit laufendem Saldo. Link im EP-Verlauf-Tab. Tests:
> `HeroEpExportTest` (2).

### REP-03 · Teilnahme-/Belegungsreport je Event · ⏱ 3h · ✅
**Akzeptanzkriterien:**
- [x] Report je Event mit Summen (Plätze, Warteliste, bezahlt, offen).
- [x] Export (CSV).

> `AdventureController@participationCsv` (`GET adventures/{adventure}/participation-csv`,
> `events.edit`): Zeilen je Anmeldung (Spieler/Rolle/Liste/Status/Beitrag/Anwesend)
> + Summenblock. Link im Verwaltungs-Modal (Anmeldungen-Tab). Tests:
> `ReportExportTest` (2).

### REP-04 · Mitglieder-/Spielerübersicht (Export) · ⏱ 3h · ✅
**Akzeptanzkriterien:**
- [x] Gefilterte Liste exportierbar (CSV).
- [x] DSGVO-konform (nur nötige Felder); Berechtigung.

> `Admin\PlayerController@export` (`admin.players.export`, `portal.manage`):
> CSV (Nachname, Vorname, E-Mail, Geburtsdatum, Geschlecht, Helden). Export-Button
> in der Admin-Spielerliste. Tests: `ReportExportTest` (2).

### REP-05 · Charakterbogen-PDF je Held · ⏱ 4h · ✅
**Akzeptanzkriterien:**
- [x] PDF-Generierung (dompdf) mit Vereins-Layout.
- [x] Download im Helden-Detail; Test (PDF wird erzeugt).

> `HeroController@sheetPdf` (`GET heroes/{hero}/sheet-pdf`, `heldenregister.view`):
> dompdf-View `heroes/sheet_pdf.blade.php` (Stammdaten, Klassen, EP, Fertigkeiten
> inkl. Perlen). Inline-Stream; Button im Übersicht-Tab. Tests:
> `CharacterSheetAndDashboardTest` (2).

### REP-06 · Dashboard-Kennzahlen für Admin · ⏱ 3h · ✅
**Akzeptanzkriterien:**
- [x] Kennzahl-Karten auf dem Dashboard (nur Admin).
- [x] Effiziente Queries (kein N+1); Test.

> `DashboardController@index` liefert für Admins `count()`-Kennzahlen (Spieler,
> Helden, kommende Events, offene Anmeldungen); Karten im Dashboard nur bei
> Admin. Tests: `CharacterSheetAndDashboardTest` (2).
