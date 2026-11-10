# Deployment-Anleitung · Heldenregister

Schritt-für-Schritt-Anleitung für das Einrichten und Aktualisieren der Produktivumgebung.
Voraussetzung: PHP 8.3+, Composer, Node 20+, MySQL 8+, **Supervisor**, ein vhost auf `public/`.

---

## Ersteinrichtung (Erstmalige Installation)

```bash
# 0. Supervisor installieren (einmalig, als root)
apt-get install -y supervisor

# 1. Repository klonen
git clone <repo-url> /var/www/heldenregister
cd /var/www/heldenregister

# 2. PHP-Abhängigkeiten (ohne Dev-Pakete)
composer install --no-dev --optimize-autoloader

# 3. Frontend-Assets bauen
npm ci
npm run build

# 4. Umgebungsdatei anlegen und befüllen
cp .env.example .env
# → .env manuell bearbeiten (DB, Mail, Matrix, APP_URL, …)

# 5. App-Schlüssel generieren (nur einmalig!)
php artisan key:generate

# 6. Datenbankmigrationen ausführen
php artisan migrate --force

# 7. Datenbank-Seeder (Stammdaten / Standard-Settings)
php artisan db:seed --class=SettingsSeeder
php artisan db:seed --class=TaskTypeDefinitionSeeder   # Task-Typ-Standardkonfiguration
# weitere Seeder nach Bedarf: --class=EventStatusSeeder usw.

# 8. Storage-Symlink anlegen
php artisan storage:link

# 9. Berechtigungen setzen
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## Konfiguration für den Produktivbetrieb cachen

Nach jeder Änderung an `.env` oder Config-Dateien:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Cache leeren (z. B. nach einem Deploy):

```bash
php artisan optimize:clear
```

---

## Update / Redeploy (laufende Instanz)

```bash
git pull

composer install --no-dev --optimize-autoloader

npm ci
npm run build

php artisan migrate --force

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue-Worker neu starten (falls INFRA-04 umgesetzt)
php artisan queue:restart
```

---

## Webserver-Konfiguration

Docroot zeigt auf `public/` (nicht auf das Projekt-Root).

**Apache-Beispiel (`.htaccess` ist bereits enthalten):**

```apache
<VirtualHost *:443>
    ServerName heldenregister.example.de
    DocumentRoot /var/www/heldenregister/public

    <Directory /var/www/heldenregister/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx-Beispiel:**

```nginx
server {
    listen 443 ssl;
    server_name heldenregister.example.de;
    root /var/www/heldenregister/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Scheduler (Cron)

Einmalig als `www-data`-Cron eintragen (**Pflicht** — ohne diesen Eintrag laufen
keine automatischen Aufgaben: kein Taskmanager, keine Erinnerungsmails, keine Backups):

```cron
* * * * * cd /var/www/heldenregister && php artisan schedule:run >> /dev/null 2>&1
```

Eintragen via:

```bash
(crontab -l -u www-data; echo "* * * * * cd /var/www/heldenregister && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
```

Prüfen ob alle Tasks korrekt registriert sind:

```bash
php artisan schedule:list
```

### Geplante Aufgaben (Übersicht)

| Zeit | Befehl | Funktion |
|---|---|---|
| 07:00 | `adventures:run-tasks` | Fällige Event-Aufgaben ausführen (Taskmanager) |
| 08:00 | `events:send-reminders` | Erinnerungsmails an bestätigte Teilnehmer |
| 01:00 | `backup:clean` | Alte Backups bereinigen |
| 02:00 | `backup:run` | Neues Backup anlegen |
| 03:00 | `dsgvo:prune` | DSGVO-Aufbewahrungsfristen prüfen |
| 04:00 | `queue:prune-failed` | Failed Jobs älter als 7 Tage löschen |

---

## Queue-Worker

Alle Notifications implementieren `ShouldQueue`. Mails und andere Jobs werden
**asynchron** versendet — der HTTP-Request kehrt sofort zurück, der Worker
erledigt den Versand im Hintergrund. `QUEUE_CONNECTION=database` muss in `.env`
gesetzt sein.

Die benötigten Tabellen (`jobs`, `failed_jobs`) sind in den Migrationen enthalten
und werden mit `php artisan migrate` angelegt.

### Supervisor einrichten (Pflicht für Produktivbetrieb)

```bash
# Supervisor installieren (falls noch nicht vorhanden)
apt-get install -y supervisor
systemctl enable supervisor
systemctl start supervisor
```

Konfigurationsdatei anlegen:

```ini
# /etc/supervisor/conf.d/heldenregister-worker.conf
[program:heldenregister-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/heldenregister/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
directory=/var/www/heldenregister
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/heldenregister/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=60
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl status   # → heldenregister-worker_00 RUNNING, _01 RUNNING
```

### Worker nach Deploy neu starten

Nach jedem `git pull` müssen die Worker neu gestartet werden, damit sie den
aktualisierten Code laden:

```bash
# Graceful restart (Worker beenden laufende Jobs, dann Neustart durch Supervisor)
php artisan queue:restart

# Oder direkt via Supervisor:
supervisorctl restart heldenregister-worker:*
```

### Fehlgeschlagene Jobs prüfen

```bash
php artisan queue:failed          # Liste aller fehlgeschlagenen Jobs
php artisan queue:retry all       # Alle fehlgeschlagenen Jobs erneut versuchen
php artisan queue:flush           # Fehlgeschlagene Jobs löschen
```

Worker-Log: `storage/logs/worker.log`

---

## Backups (INFRA-06)

Backups werden täglich via `spatie/laravel-backup` erstellt:
- **01:00** — alte Backups bereinigen (`backup:clean`)
- **02:00** — neues Backup anlegen (`backup:run`): DB-Dump (gzip) + `storage/app`-Uploads

Backup-Dateien liegen unter `BACKUP_DISK_PATH` (Standard: `/var/backups/heldenregister`).

**Aufbewahrung:** täglich 7 Tage, täglich 30 Tage, wöchentlich 8 Wochen,
monatlich 6 Monate, jährlich 2 Jahre.

### Manuelle Befehle

```bash
# Backup sofort ausführen
php artisan backup:run

# Nur DB sichern (ohne Dateien)
php artisan backup:run --only-db

# Altes Backups aufräumen
php artisan backup:clean

# Backup-Status prüfen
php artisan backup:monitor
php artisan backup:list
```

### Wiederherstellung

```bash
# 1. Gewünschtes Backup-Archiv aus BACKUP_DISK_PATH entpacken
cd /var/backups/heldenregister/Heldenregister/
unzip YYYY-MM-DD-HH-II-SS.zip -d /tmp/restore

# 2. DB wiederherstellen (Achtung: überschreibt die bestehende DB!)
mysql -u heldenregister -p heldenregister < /tmp/restore/*.sql.gz | gunzip | mysql -u heldenregister -p heldenregister
# Oder direkt mit gunzip:
gunzip -c /tmp/restore/*.sql.gz | mysql -u heldenregister -p heldenregister

# 3. Uploads wiederherstellen
rsync -av /tmp/restore/var/www/heldenregister/storage/app/ /var/www/heldenregister/storage/app/
chown -R www-data:www-data /var/www/heldenregister/storage/app
```

---

## Logging & Fehler-Monitoring (INFRA-07)

### Log-Konfiguration

Produktion verwendet den `daily`-Channel (rotierende Tagesdateien in `storage/logs/`):

```dotenv
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=30
```

Log-Dateien liegen unter `storage/logs/laravel-YYYY-MM-DD.log`.

### OS-seitige Log-Rotation (logrotate)

Als zusätzliches Sicherheitsnetz, falls `LOG_DAILY_DAYS` nicht ausreicht:

```
# /etc/logrotate.d/heldenregister
/var/www/heldenregister/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
    sharedscripts
}
```

### Optionales Error-Tracking mit Sentry

Falls ein zentrales Error-Tracking gewünscht wird:

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=https://<key>@sentry.io/<project>
```

In `.env` ergänzen:

```dotenv
SENTRY_LARAVEL_DSN=https://<key>@sentry.io/<project>
SENTRY_TRACES_SAMPLE_RATE=0.1
```

Ohne `SENTRY_LARAVEL_DSN` bleibt das Paket inaktiv — kein Pflichtbestandteil.

---

## Rollback

Bei einem fehlgeschlagenen Deploy:

```bash
# Vorherigen Commit auschecken
git checkout <letzter-guter-commit>

composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Falls Migration rückgängig gemacht werden muss:
php artisan migrate:rollback

php artisan optimize:clear
php artisan config:cache
```

---

## Checkliste vor Go-Live

- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] `APP_KEY` gesetzt (`php artisan key:generate`)
- [ ] `APP_URL` auf echte Domain gesetzt
- [ ] Datenbank-Credentials korrekt
- [ ] `MAIL_*`-Werte für echten SMTP-Server gesetzt
- [ ] `MATRIX_CORPORAL_TOKEN` gesetzt
- [ ] `php artisan config:cache` + `route:cache` + `view:cache` ausgeführt
- [ ] `storage/` und `bootstrap/cache/` beschreibbar für `www-data`
- [ ] `php artisan storage:link` ausgeführt
- [ ] SSL-Zertifikat aktiv
- [ ] Cron-Eintrag für Scheduler gesetzt
- [ ] Supervisor installiert und aktiv (`systemctl status supervisor`)
- [ ] Queue-Worker läuft (`supervisorctl status heldenregister-worker:*`)
- [ ] `QUEUE_CONNECTION=database` in `.env` gesetzt
