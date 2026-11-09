# Backlog · Tests & Qualität (QA)

Testabdeckung, Codequalität, nicht-funktionale Anforderungen.

## Inventar (✅)

### QA-01 · Feature-Test-Suite (Basis) · ⏱ 4h · ✅
77 Tests über Auth, Rollen, Spieler, Helden, Abenteuer, Buchungen, Matrix,
Permission-Matrix, AJAX.

### QA-02 · Pint-Konformität · ⏱ 1h · ✅
Code-Style durchgängig via Laravel Pint.

## Offen (🔲)

### QA-03 · Browser-/E2E-Tests für Modals & AJAX · ⏱ 4h · 🔲
**Beschreibung:** Feature-Tests decken JS nicht ab. Dusk/Playwright für
Modal-Öffnen, AJAX-Submit, Toasts.
**Akzeptanzkriterien:**
- [ ] E2E-Setup; Test: Liste → Modal öffnet → Formular absenden → Toast.
- [ ] Läuft in CI (headless).
**Abhängig von:** INFRA-03.

### QA-04 · Test-Coverage-Messung & Zielwert · ⏱ 2h · 🔲
**Beschreibung:** Coverage erfassen und Mindestziel definieren.
**Akzeptanzkriterien:**
- [ ] Coverage-Report (Xdebug/PCOV) lokal + CI.
- [ ] Zielwert dokumentiert (z. B. ≥ 70 % der App-Klassen).

### QA-05 · Statische Analyse (PHPStan/Larastan) · ⏱ 3h · 🔲
**Beschreibung:** Statische Typprüfung einführen.
**Akzeptanzkriterien:**
- [ ] Larastan auf Level n eingerichtet, grün.
- [ ] In CI als Gate.

### QA-06 · N+1-Queries auditieren · ⏱ 3h · 🔲
**Beschreibung:** Listen/Modals auf Eager-Loading prüfen.
**Akzeptanzkriterien:**
- [ ] `preventLazyLoading` in Dev aktiv; gefundene N+1 behoben.
- [ ] Spot-Checks dokumentiert.

### QA-07 · Factories & Seeder für Demo-/Testdaten · ⏱ 3h · 🔲
**Beschreibung:** Vollständige Factories (Skill, Adventure-Beziehungen, Matrix)
und ein Demo-Seeder für Schulung/Tests.
**Akzeptanzkriterien:**
- [ ] Factories für alle Kern-Entitäten.
- [ ] `DemoSeeder` erzeugt konsistenten Beispieldatensatz.

### QA-08 · DSGVO-/Datenschutz-Review · ⏱ 3h · 🔲
**Beschreibung:** Personenbezogene Daten (Minderjährige!) prüfen: Speicherung,
Löschkonzept, Exporte.
**Akzeptanzkriterien:**
- [ ] Lösch-/Anonymisierungskonzept dokumentiert.
- [ ] Datensparsamkeit in Exporten (REP) sichergestellt.

### QA-09 · Developer-Onboarding-Doku · ⏱ 2h · 🔲
**Beschreibung:** README/Setup für neue Entwickler.
**Akzeptanzkriterien:**
- [ ] Lokales Setup (DB, `migrate --seed`, Test-DB, `migrate:legacy`).
- [ ] Architektur-/Berechtigungs-Überblick verlinkt (roadmap/permissions).
