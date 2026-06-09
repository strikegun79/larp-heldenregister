# Backlog · Betrieb, CI & Deployment (INFRA)

Produktivbetrieb, Pipeline, Umgebung.

## Offen (🔲)

### INFRA-01 · Prod-`.env` & Konfiguration · ⏱ 2h · 🔲
**Beschreibung:** Produktive Umgebung sauber konfigurieren.
**Akzeptanzkriterien:**
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, echte `APP_URL`, Mail-Credentials.
- [ ] DB-/Legacy-/Matrix-Token-Variablen gesetzt; `.env.example` aktualisiert.
- [ ] `key:generate`, `config:cache`/`route:cache`/`view:cache` dokumentiert.

### INFRA-02 · Deployment-Skript/Anleitung · ⏱ 3h · 🔲
**Beschreibung:** Reproduzierbares Deployment (Docroot `public/`, Rechte).
**Akzeptanzkriterien:**
- [ ] Schritt-für-Schritt-Deploy (composer `--no-dev`, `npm ci && build`, `migrate --force`).
- [ ] vhost auf `public/`; Storage-/Cache-Rechte für `www-data`.
- [ ] Rollback-Hinweis.

### INFRA-03 · CI-Pipeline (Tests + Pint) · ⏱ 3h · 🔲
**Beschreibung:** GitHub Actions: Tests, Pint, Build bei Push/PR.
**Akzeptanzkriterien:**
- [ ] Workflow installiert Abhängigkeiten, richtet Test-DB ein, `php artisan test`.
- [ ] `pint --test` als Lint-Gate; `npm run build`.
- [ ] Grünes Badge im README.

### INFRA-04 · Queue-Worker für Mails/Jobs · ⏱ 3h · 🔲
**Beschreibung:** Mails asynchron über Queue (statt sync).
**Akzeptanzkriterien:**
- [ ] `QUEUE_CONNECTION=database`/redis; `jobs`-Tabelle migriert.
- [ ] Worker als Supervisor/systemd-Service dokumentiert.
- [ ] Mailables `ShouldQueue`.

### INFRA-05 · Scheduler einrichten · ⏱ 2h · 🔲
**Beschreibung:** `schedule:run` Cron für Erinnerungen/Pflegejobs.
**Akzeptanzkriterien:**
- [ ] Cron-Eintrag dokumentiert; Beispiel-Task registriert.
- [ ] Funktioniert mit NOTI-05.

### INFRA-06 · Backups (DB + Uploads) · ⏱ 3h · 🔲
**Beschreibung:** Regelmäßige Sicherung der Produktiv-DB und Uploads.
**Akzeptanzkriterien:**
- [ ] Backup-Strategie/-Skript (z. B. `spatie/laravel-backup`).
- [ ] Wiederherstellung getestet/dokumentiert.

### INFRA-07 · Fehler-Monitoring/Logging · ⏱ 2h · 🔲
**Beschreibung:** Produktives Logging + optionales Error-Tracking.
**Akzeptanzkriterien:**
- [ ] Log-Channel/Level für Prod gesetzt; Rotation.
- [ ] Optional Sentry o. Ä. angebunden.

### INFRA-08 · Legacy-Abschaltung & Cutover-Plan · ⏱ 2h · 🔲
**Beschreibung:** Geordneter Wechsel vom Legacy-Portal.
**Akzeptanzkriterien:**
- [ ] Cutover-Checkliste (ETL-Endlauf, DNS/Docroot, corporal-URL umstellen).
- [ ] Legacy read-only/offline; Backup behalten.
- [ ] Kommunikation an Mitglieder.
