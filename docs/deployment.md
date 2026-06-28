# Deployment-Anleitung · Heldenregister

Schritt-für-Schritt-Anleitung für das Einrichten und Aktualisieren der Produktivumgebung.
Voraussetzung: PHP 8.3+, Composer, Node 20+, MySQL 8+, ein vhost auf `public/`.

---

## Ersteinrichtung (Erstmalige Installation)

```bash
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

Einmalig als `www-data`-Cron eintragen:

```cron
* * * * * cd /var/www/heldenregister && php artisan schedule:run >> /dev/null 2>&1
```

---

## Queue-Worker (INFRA-04)

Alle Notifications implementieren `ShouldQueue`; Mails werden asynchron
versendet, sobald `QUEUE_CONNECTION=database` gesetzt ist.

**Voraussetzung:** `jobs`-Tabelle migrieren (einmalig, bereits in Migration enthalten):

```bash
php artisan migrate
```

### Supervisor (empfohlen für Produktivbetrieb)

```ini
# /etc/supervisor/conf.d/heldenregister-worker.conf
[program:heldenregister-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/heldenregister/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/heldenregister-worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start heldenregister-worker:*
```

### systemd (Alternative)

```ini
# /etc/systemd/system/heldenregister-worker.service
[Unit]
Description=Heldenregister Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/heldenregister
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable heldenregister-worker
systemctl start heldenregister-worker
systemctl status heldenregister-worker
```

### Worker nach Deploy neu starten

```bash
php artisan queue:restart
```

Supervisor/systemd startet den Worker danach automatisch neu.

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
