# Backlog · Tests & Qualität (QA)

Testabdeckung, Codequalität, nicht-funktionale Anforderungen.

## Inventar (✅)

### QA-01 · Feature-Test-Suite (Basis) · ⏱ 4h · ✅
77 Tests über Auth, Rollen, Spieler, Helden, Abenteuer, Buchungen, Matrix,
Permission-Matrix, AJAX.

### QA-02 · Pint-Konformität · ⏱ 1h · ✅
Code-Style durchgängig via Laravel Pint.

## Offen (🔲)

### QA-03 · Browser-/E2E-Tests für Modals & AJAX · ⏱ 4h · ✅
**Beschreibung:** Feature-Tests decken JS nicht ab. Dusk/Playwright für
Modal-Öffnen, AJAX-Submit, Toasts.
**Akzeptanzkriterien:**
- [x] Laravel Dusk installiert; ChromeDriver automatisch via `dusk:chrome-driver --detect`.
- [x] 3 Tests in `tests/Browser/ModalAjaxTest`: Modal öffnen, AJAX-Submit → Toast, data-confirm → Abbrechen.
- [x] Separater `dusk`-Job in CI (headless, nach `test`-Job, Screenshot-Artefakt bei Fehler).
- [x] Reguläre PHPUnit-Suite schließt `tests/Browser/` nicht ein.
**Abhängig von:** INFRA-03.

> Lokal starten: `.env.dusk.local` anlegen (Vorlage: `.env.dusk.local.example`),
> dann `php artisan serve --env=dusk.local &` + `php artisan dusk`.

### QA-04 · Test-Coverage-Messung & Zielwert · ⏱ 2h · ✅
**Beschreibung:** Coverage erfassen und Mindestziel definieren.
**Akzeptanzkriterien:**
- [x] Coverage-Report (Xdebug/PCOV) lokal + CI.
- [x] Zielwert dokumentiert (z. B. ≥ 70 % der App-Klassen).

> Umgesetzt: PCOV (php8.3-pcov) als Coverage-Driver. Baseline (668 Tests):
> Lines 75,88 % (2171/2861) · Methods 69,37 % (308/444) · Classes 47,12 %.
> CI-Gate: `--coverage --min=70` (Zeilen-Coverage). Lokal:
> `php artisan test --coverage` oder `./vendor/bin/phpunit --coverage-text`.
> Zielwert: ≥ 70 % Zeilen-Coverage als CI-Pflichtschranke.

### QA-05 · Statische Analyse (PHPStan/Larastan) · ⏱ 3h · ✅
**Beschreibung:** Statische Typprüfung einführen.
**Akzeptanzkriterien:**
- [x] Larastan auf Level 5 eingerichtet, grün (0 Fehler).
- [x] In CI als Gate.

> Umgesetzt: `larastan/larastan` v3 installiert, `phpstan.neon` auf Level 5.
> 3 echte Bugs behoben: `abort_unless` mit `string|null` (IdCardController),
> PHPDoc `array<int, string>` → `list<string>` für `$fillable`/`$hidden`
> (User-Model). 71 Larastan-Inferenzprobleme (Eloquent-Collections, nullable
> Datum-Casts, `HasOne::withTrashed()`, dynamische View-Namen) in
> `phpstan-baseline.neon` erfasst. `.github/workflows/ci.yml` um
> PHPStan-Gate erweitert.

### QA-06 · N+1-Queries auditieren · ⏱ 3h · ✅
**Beschreibung:** Listen/Modals auf Eager-Loading prüfen.
**Akzeptanzkriterien:**
- [x] `preventLazyLoading` in Dev aktiv; gefundene N+1 behoben.
- [x] Spot-Checks dokumentiert.

> Umgesetzt: `Model::preventLazyLoading(!app()->isProduction())` in
> `AppServiceProvider`. Gefundene N+1:
> 1. `Auth::user()->roles` doppelt in `navigation.blade.php` → `loadMissing`
>    am Dateianfang vorangestellt.
> 2. `EpTransaction.type` auf jedem Hero-EP-Zugriff lazy-geladen
>    (`ep_balance`, `ep_total`, `signedAmount`) → `->with('type')` direkt
>    in `Hero::epTransactions()` Relationship-Definition.
> Testsuite: 661 grün, 7 vorbestehende Fehler (unverändert).

### QA-07 · Factories & Seeder für Demo-/Testdaten · ⏱ 3h · ✅
**Beschreibung:** Vollständige Factories (Skill, Adventure-Beziehungen, Matrix)
und ein Demo-Seeder für Schulung/Tests.
**Akzeptanzkriterien:**
- [x] Factories für alle Kern-Entitäten.
- [x] `DemoSeeder` erzeugt konsistenten Beispieldatensatz.

> Umgesetzt: `EpTransactionFactory` (States: credit/debit/adventure/initial) und
> `MatrixAccountFactory` (State: inactive) neu. `UserFactory` um States
> `admin()`, `teamer()`, `withRole(slug)` erweitert. `PlayerFactory` um
> `minor()` und `inactive()`. `HeroFactory` um `inactive()` und `private()`.
> `SkillFactory` mit Null-Fallback abgesichert. `DemoSeeder` erzeugt 4 Konten
> (Admin, Bürokrat, 2 Teamer), 5 Spieler (3 Minderjährige), je 1 Held mit
> Klassen/Skills/EP, 1 vergangenes + 1 kommendes Abenteuer.

### QA-08 · DSGVO-/Datenschutz-Review · ⏱ 3h · ✅
**Beschreibung:** Personenbezogene Daten (Minderjährige!) prüfen: Speicherung,
Löschkonzept, Exporte.
**Akzeptanzkriterien:**
- [x] Lösch-/Anonymisierungskonzept dokumentiert.
- [x] Datensparsamkeit in Exporten (REP) sichergestellt.

> Umgesetzt: `docs/dsgvo.md` mit vollständigem Datenschutzkonzept (Inventar,
> Rechtsgrundlagen, Aufbewahrungsfristen, Löschkonzept, Export-Dokumentation,
> offene Punkte). `User::anonymize()` und `Player::anonymize()` für DSGVO
> Art. 17 implementiert: überschreiben Klardaten, löschen Fotos, soft-delete.
> Alle Admin-Exporte (CSV, PDF) sind hinter `can:`-Middleware; Inhalte auf
> das gesetzlich erforderliche Minimum geprüft.

### QA-09 · Developer-Onboarding-Doku · ⏱ 2h · ✅
**Beschreibung:** README/Setup für neue Entwickler.
**Akzeptanzkriterien:**
- [x] Lokales Setup (DB, `migrate --seed`, Test-DB, `migrate:legacy`).
- [x] Architektur-/Berechtigungs-Überblick verlinkt (roadmap/permissions).

> Umgesetzt: README.md vollständig überarbeitet — Laravel-Boilerplate ersetzt
> durch projektspezifische Doku: Voraussetzungen, Setup-Schritte, Admin-Account
> via Tinker, Testdatenbank, Legacy-Migration, Architektur-Überblick mit
> Verzeichnisstruktur, Rollenmodell-Tabelle, wichtige Artisan-Befehle,
> Code-Qualitäts-Befehle.
