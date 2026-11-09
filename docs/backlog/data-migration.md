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

### ETL-04 · `hero2classes.class_id`-Inkonsistenz bereinigen · ⏱ 3h · 🔲
**Beschreibung:** Legacy speichert mal `id`, mal `idname` (slug). Migration löst
aktuell via slug auf; Quelldaten bzw. Mapping endgültig verifizieren.
**Akzeptanzkriterien:**
- [ ] Prüfskript meldet nicht auflösbare Klassenzuordnungen.
- [ ] Alle Helden-Klassen korrekt migriert (Stichprobe + Zähler).

### ETL-05 · Trockenlauf-/Dry-Run-Modus · ⏱ 3h · 🔲
**Beschreibung:** ETL ohne Schreibzugriff zur Vorab-Prüfung.
**Akzeptanzkriterien:**
- [ ] `--dry-run` zeigt geplante Inserts/Updates + Konflikte ohne Schreiben.
- [ ] Report wie im Echtlauf.

### ETL-06 · Datenqualitäts-Report (verwaiste FKs, Dubletten) · ⏱ 3h · 🔲
**Beschreibung:** Vor Go-Live Auffälligkeiten in den Legacy-Daten finden.
**Akzeptanzkriterien:**
- [ ] Report über verwaiste Referenzen, leere Pflichtfelder, doppelte E-Mails.
- [ ] Ausgabe als Liste/CSV.

### ETL-07 · Re-Run-Sicherheit nach Go-Live dokumentieren · ⏱ 2h · 🔲
**Beschreibung:** Klarstellen, wann/ob `migrate:legacy` nach Produktivbetrieb
erneut laufen darf (Überschreiben vs. Erhalt manueller Änderungen).
**Akzeptanzkriterien:**
- [ ] Verhalten je Tabelle dokumentiert (update vs. insert-only).
- [ ] Schutz produktiv geänderter Daten beschrieben/umgesetzt.
