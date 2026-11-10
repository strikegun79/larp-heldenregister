# Backlog · ETL / Legacy-Migration (ETL)

Datenübernahme aus dem Legacy-Portal (`larp_buerokrat`).

## Inventar (✅)

### ETL-01 · Legacy-DB-Verbindung · ⏱ 2h · ✅
Read-only Connection `legacy`.

### ETL-02 · `migrate:legacy` (idempotent, alle Tabellen) · ⏱ 4h · ✅
Users/Players/Heroes/Skills/Events/Bookings/Matrix; legacy_id-Anker; Report.

### ETL-03 · Validierungs-Report Quelle↔Ziel · ⏱ 2h · ✅
Zeilenzahlen-Vergleich; Non-bcrypt-Passwort-Hinweis.

## Offen (🔲)

### ETL-04 · `hero2classes.class_id`-Inkonsistenz bereinigen · ⏱ 3h · ✅
**Beschreibung:** Legacy speichert mal `id`, mal `idname` (slug). Migration löst
aktuell via slug auf; Quelldaten bzw. Mapping endgültig verifizieren.
**Akzeptanzkriterien:**
- [x] Prüfskript meldet nicht auflösbare Klassenzuordnungen.
- [x] Alle Helden-Klassen korrekt migriert (Stichprobe + Zähler).

> Umgesetzt: `etl:check-hero-classes` Audit-Command. `migrateHeroClasses()`
> löst numerische class_id über `type_classes.idname` auf; nicht auflösbare
> Einträge werden gewarnt und übersprungen.

## Gestrichen (~~)

### ~~ETL-05 · Trockenlauf-/Dry-Run-Modus~~ · entfällt
Legacy-DB enthält keine Produktivdaten — das neue Portal ist bereits live.
Ein Dry-Run-Modus bietet keinen Mehrwert mehr.

### ~~ETL-06 · Datenqualitäts-Report (verwaiste FKs, Dubletten)~~ · entfällt
Vor-Go-Live-Analyse der Legacy-Daten obsolet, da Migration abgeschlossen.

### ~~ETL-07 · Re-Run-Sicherheit nach Go-Live dokumentieren~~ · entfällt
Re-Run-Szenarien nicht mehr relevant, da keine Nachlieferung aus Legacy geplant.
