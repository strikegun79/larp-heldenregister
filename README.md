# Heldenregister

<p align="center">
<a href="https://github.com/strikegun79/larp-heldenregister/actions/workflows/ci.yml"><img src="https://github.com/strikegun79/larp-heldenregister/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
</p>

Vereinsportal der **Waldritter Gießen e. V.** für Kinder- und Jugend-LARP.
Mitglieder verwalten Spieler und Helden, melden sich zu Abenteuern an und
verfolgen Charakterfortschritt über EP und Fertigkeiten.

**Stack:** Laravel 12 · PHP 8.3+ · MySQL 8 · Blade · Fomantic-UI

---

## Lokales Setup

### Voraussetzungen

- PHP 8.3+ mit Extensions: `pdo_mysql`, `gd`, `mbstring`, `xml`, `bcmath`, `zip`, `intl`
- Composer 2
- Node 20+ / npm
- MySQL 8

### Einrichten

```bash
# 1. Abhängigkeiten
composer install
npm ci && npm run build

# 2. Umgebung
cp .env.example .env
php artisan key:generate
```

`.env` anpassen — mindestens:

```dotenv
APP_URL=http://localhost:8000
DB_DATABASE=heldenregister
DB_USERNAME=heldenregister
DB_PASSWORD=geheim
```

```bash
# 3. Datenbank anlegen und migrieren
mysql -uroot -e "CREATE DATABASE heldenregister CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -e "CREATE USER 'heldenregister'@'localhost' IDENTIFIED BY 'geheim';"
mysql -uroot -e "GRANT ALL ON heldenregister.* TO 'heldenregister'@'localhost';"

php artisan migrate
php artisan db:seed        # Stammdaten: Rollen, Klassen, EP-Typen, Orte, …
php artisan storage:link

# 4. Dev-Server starten
php artisan serve
```

Erster Admin-Account: nach der Registrierung per Tinker die Rolle setzen:

```bash
php artisan tinker
> $user = \App\Models\User::where('email', 'deine@mail.de')->firstOrFail();
> $admin = \App\Models\Role::where('slug', 'admin')->first();
> $user->roles()->sync([$admin->id]);
```

---

## Testdatenbank

```bash
# .env.testing anlegen (wird von phpunit.xml geladen)
cp .env.example .env.testing
# DB_DATABASE auf einen separaten Testdatenbankname setzen, z. B.:
#   DB_DATABASE=heldenregister_test

mysql -uroot -e "CREATE DATABASE heldenregister_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrationen für Testdatenbank
php artisan migrate --env=testing

# Tests ausführen
php artisan test
```

CI verwendet eine eigene MySQL-Instanz (`.github/workflows/ci.yml`).

---

## Legacy-Migration

Falls eine Kopie der alten Datenbank (`larp_buerokrat`) vorhanden ist, kann der
ETL-Befehl die Daten übernehmen:

```bash
# .env ergänzen:
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_DATABASE=larp_buerokrat
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=

# Migration ausführen (idempotent):
php artisan migrate:legacy

# Anschließend Konsistenzprüfung:
php artisan etl:check-hero-classes
```

Das neue Produktivsystem läuft bereits — `migrate:legacy` wird nur noch für
lokale Entwicklungsumgebungen mit einer Legacy-Datenkopie benötigt.

---

## Architektur-Überblick

```
app/
  Console/Commands/     Artisan-Befehle (ETL, Checks)
  Http/Controllers/     Feature-Controller (flach, kein Repository-Layer)
    Admin/              Verwaltungs-Controller (hinter can:portal.manage)
  Models/               Eloquent-Modelle
  Notifications/        Mail-Benachrichtigungen (alle queued)
  Policies/             Noch nicht genutzt — Rechte via Gates (config/permissions.php)
  Providers/            AppServiceProvider: N+1-Schutz, Auth-Mail-Templates
config/
  permissions.php       Rollen-Rechte-Matrix (Quelle der Wahrheit für alle Gates)
database/
  migrations/           Alle Schema-Änderungen
  seeders/              Stammdaten (Rollen, Klassen, EP-Typen, Orte, Events-Lookup)
docs/                   Architektur-, Deployment- und DSGVO-Dokumentation
resources/views/        Blade-Templates (Fomantic-UI)
```

Weiterführende Dokumente:

| Thema | Datei |
|---|---|
| Produktvision & Roadmap | [docs/roadmap.md](docs/roadmap.md) |
| Deployment (Produktion) | [docs/deployment.md](docs/deployment.md) |
| DSGVO / Datenschutz | [docs/dsgvo.md](docs/dsgvo.md) |
| Backlog (alle Tickets) | [docs/backlog/README.md](docs/backlog/README.md) |

---

## Rollenmodell

Rollen und ihre Rechte sind in [`config/permissions.php`](config/permissions.php)
definiert. Im `AuthServiceProvider` wird daraus automatisch je ein `Gate`
registriert.

| Rolle | Slug | Besonderheit |
|---|---|---|
| Admin | `admin` | alle Rechte (`*`) |
| Bürokrat | `registrar` | Spieler/Helden-Verwaltung, Events |
| Projektleitung | `project_lead` | Events verwalten |
| Spielleiter | `game_master` | Helden einsehen, Events |
| Lehrmeister | `lehrmeister` | Helden einsehen (ROLE-09) |
| Teamer | `teamer` | kein Heldenregister-Zugriff |
| Teilnehmer | `participant` | nur Profil + eigene Spieler |

---

## Wichtige Artisan-Befehle

```bash
php artisan migrate              # Schema aktualisieren
php artisan db:seed              # Stammdaten einspielen
php artisan migrate:legacy       # Legacy-Daten übernehmen (ETL)
php artisan etl:check-hero-classes  # Klassen-Mapping prüfen
php artisan backup:run           # Manuelles Backup (Produktion)
php artisan queue:work           # Queue-Worker starten
```

---

## Code-Qualität

```bash
./vendor/bin/pint                # Code-Style korrigieren (Laravel Pint)
./vendor/bin/phpstan analyse     # Statische Analyse (Larastan Level 5)
php artisan test                 # Feature-Tests (661+ Tests)
```

- **Standard:** PSR-12, Laravel-Konventionen, deutsche UI und Kommentare
- **N+1-Schutz:** `Model::preventLazyLoading()` wirft in Dev-/Test-Umgebung eine Exception
- **CI:** Pint + PHPStan + Tests laufen bei jedem Push (GitHub Actions)
