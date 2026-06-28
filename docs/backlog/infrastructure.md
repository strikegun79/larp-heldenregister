# Backlog · Betrieb, CI & Deployment (INFRA)

Produktivbetrieb, Pipeline, Umgebung.

## Inventar (✅)

### INFRA-01 · Prod-`.env` & Konfiguration · ⏱ 2h · ✅
**Beschreibung:** Produktive Umgebung sauber konfigurieren.
**Akzeptanzkriterien:**
- [x] `APP_ENV=production`, `APP_DEBUG=false`, echte `APP_URL`, Mail-Credentials.
- [x] DB-/Legacy-/Matrix-Token-Variablen gesetzt; `.env.example` aktualisiert.
- [x] `key:generate`, `config:cache`/`route:cache`/`view:cache` dokumentiert.

> Umgesetzt: `.env.example` auf Projekt-Defaults aktualisiert (Heldenregister-spezifisch,
> alle generischen Laravel-Defaults entfernt). Neue Sektionen: `LEGACY_DB_*`,
> `MATRIX_DOMAIN`, `MATRIX_CORPORAL_TOKEN`. Deployment-Doku in `docs/deployment.md`
> (Ersteinrichtung, Update/Redeploy, vhost-Konfiguration, Rollback, Go-Live-Checkliste).

## Offen (🔲)

### INFRA-02 · Deployment-Skript/Anleitung · ⏱ 3h · ✅
**Beschreibung:** Reproduzierbares Deployment (Docroot `public/`, Rechte).
**Akzeptanzkriterien:**
- [x] Schritt-für-Schritt-Deploy (composer `--no-dev`, `npm ci && build`, `migrate --force`).
- [x] vhost auf `public/`; Storage-/Cache-Rechte für `www-data`.
- [x] Rollback-Hinweis.

> Umgesetzt als Teil von INFRA-01: `docs/deployment.md` enthält vollständige
> Ersteinrichtung, Update/Redeploy-Ablauf, Apache/Nginx-vhost-Beispiele,
> Rollback-Anleitung und Go-Live-Checkliste.

### INFRA-03 · CI-Pipeline (Tests + Pint) · ⏱ 3h · ✅
**Beschreibung:** GitHub Actions: Tests, Pint, Build bei Push/PR.
**Akzeptanzkriterien:**
- [x] Workflow installiert Abhängigkeiten, richtet Test-DB ein, `php artisan test`.
- [x] `pint --test` als Lint-Gate; `npm run build`.
- [x] Grünes Badge im README.

> Umgesetzt: `.github/workflows/ci.yml` mit MySQL-8-Service-Container,
> PHP 8.3, Composer-Cache, Node 20, `pint --test` vor PHPUnit.
> Badge in `README.md` auf dieses Repository gesetzt.

### INFRA-04 · Queue-Worker für Mails/Jobs · ⏱ 3h · ✅
**Beschreibung:** Mails asynchron über Queue (statt sync).
**Akzeptanzkriterien:**
- [x] `QUEUE_CONNECTION=database`/redis; `jobs`-Tabelle migriert.
- [x] Worker als Supervisor/systemd-Service dokumentiert.
- [x] Mailables `ShouldQueue`.

> Umgesetzt: `jobs`-Tabellen-Migration angelegt (`queue:table`).
> `NewUserRegistered` um `implements ShouldQueue` ergänzt (alle anderen
> Notifications hatten es bereits). `.env.example` auf `QUEUE_CONNECTION=database`
> umgestellt. `docs/deployment.md` um Supervisor- und systemd-Konfiguration
> inkl. `queue:restart`-Hinweis erweitert.

### INFRA-05 · Scheduler einrichten · ⏱ 2h · ✅
**Beschreibung:** `schedule:run` Cron für Erinnerungen/Pflegejobs.
**Akzeptanzkriterien:**
- [x] Cron-Eintrag dokumentiert; Beispiel-Task registriert.
- [x] Funktioniert mit NOTI-05.

> Umgesetzt: `Console\Kernel::schedule` registriert `events:send-reminders`
> täglich um 08:00 (NOTI-05). Cron-Eintrag (im Kernel dokumentiert):
> `* * * * * cd /var/www/heldenregister && php artisan schedule:run >> /dev/null 2>&1`.
> `php artisan schedule:list` zeigt den Task. Verifiziert über die NOTI-05-Tests.

### INFRA-06 · Backups (DB + Uploads) · ⏱ 3h · ✅
**Beschreibung:** Regelmäßige Sicherung der Produktiv-DB und Uploads.
**Akzeptanzkriterien:**
- [x] Backup-Strategie/-Skript (z. B. `spatie/laravel-backup`).
- [x] Wiederherstellung getestet/dokumentiert.

> Umgesetzt: `spatie/laravel-backup` installiert. `config/backup.php` sichert
> MySQL-DB (gzip, useSingleTransaction) + `storage/app`-Uploads in den
> `backup`-Disk (`BACKUP_DISK_PATH`). Scheduler: `backup:clean` 01:00,
> `backup:run` 02:00. Fehler-Benachrichtigung per Mail (`BACKUP_MAIL_TO`).
> Wiederherstellungs-Anleitung in `docs/deployment.md`.

### INFRA-07 · Fehler-Monitoring/Logging · ⏱ 2h · ✅
**Beschreibung:** Produktives Logging + optionales Error-Tracking.
**Akzeptanzkriterien:**
- [x] Log-Channel/Level für Prod gesetzt; Rotation.
- [x] Optional Sentry o. Ä. angebunden.

> Umgesetzt: `.env.example` auf `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`,
> `LOG_DAILY_DAYS=30` umgestellt. `config/logging.php` liest `LOG_DAILY_DAYS`
> aus Umgebung (Standard 30 Tage). `docs/deployment.md` um Logging-Abschnitt
> erweitert: logrotate-Konfiguration + optionale Sentry-Anbindung dokumentiert.

### INFRA-08 · Legacy-Abschaltung & Cutover-Plan · ⏱ 2h · 🔲
**Beschreibung:** Geordneter Wechsel vom Legacy-Portal.
**Akzeptanzkriterien:**
- [ ] Cutover-Checkliste (ETL-Endlauf, DNS/Docroot, corporal-URL umstellen).
- [ ] Legacy read-only/offline; Backup behalten.
- [ ] Kommunikation an Mitglieder.
