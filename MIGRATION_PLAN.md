# Migrationsplan – LARP Heldenregister

> Migration des bestehenden Plain‑PHP‑Portals **Heldenregister** (Waldritter‑Gießen e.V.)
> nach **Laravel 10**. Stand der Analyse: 2026‑06‑08.

## Fortschritt
- ✅ **Phase 0 – Fundament:** Laravel Breeze (Blade) installiert, Auth-Routen aktiv.
- ✅ **Phase 1 – Benutzer/Rollen/Auth:** `users` erweitert, `roles`+`role_user`, Rollen-Gates, RoleSeeder.
- ✅ **Phase 2 – Spieler & Helden:** Lookups (hero_classes, perl_colors, ep_transaction_types) + Seeder; Tabellen players/heroes/skills + Pivots + ep_transactions; Models mit Relationen & EP-/Klassen-Accessoren (ersetzen `view_heroT1*`); HeroController (CRUD) + Views + Tests (31 grün).
- ✅ **Phase 3 – Events/Abenteuer:** Lookups (locations, event_categories, event_statuses, event_clients, event_roles) + Seeder; Tabellen adventures/bookings/event_visits; Models mit Belegungs-/Warteliste-Logik; AdventureController (CRUD) + BookingController (Anmeldung mit Kapazitäts-/Wartelisten-Logik) + Views + Tests (40 grün).
- ✅ **Phase 4 – ETL aus `larp_buerokrat`:** Read-only `legacy`-DB-Verbindung; idempotentes Artisan-Command `php artisan migrate:legacy [--fresh]` migriert alle 13 Daten-/Pivot-Tabellen in FK-Reihenfolge über `legacy_id`-Anker. on/off→Boolean, `class_id`-Slug-Auflösung, Hash-Erhalt (Non-bcrypt-Report), Validierungs-Report (Quelle vs. Ziel). Verifiziert: alle Zeilenzahlen identisch, Held „Tilix" inkl. Klassen/Skills/EP=22, Idempotenz beim Re-Run.
- 🟡 **Phase 5 – Mail/QR/Matrix/Abschluss:** Mail erledigt (E-Mail-Verifizierung als Aktivierung; Admin-Benachrichtigung bei Neuregistrierung). QR verworfen (Legacy-Dependency ungenutzt). **Matrix umgesetzt** (User-DB für den Matrix-Server, siehe unten). Go-Live: dokumentiert, ausstehende Infrastruktur.

> Tests laufen gegen eine separate DB `larp_heldenregister_laravel_test` (siehe `phpunit.xml`).

---

## 1. Ausgangslage

Es existieren drei relevante Codebasen auf dem Server:

| Pfad | Stack | Status | Datenbank |
|------|-------|--------|-----------|
| `/var/www/html/larp-heldenregister/` | Plain PHP, Smarty 4, Semantic UI, PDO | **Produktiv (Legacy)** | `larp_buerokrat` |
| `/var/www/html/heldenregister/` | Laravel (06/2024) | Abgebrochener erster Migrationsversuch | – |
| `/var/www/heldenregister/` | **Laravel 10.50 / PHP 8.1** | **Ziel dieser Migration** (aktuell leeres Gerüst) | `larp_heldenregister_laravel` |

Diese Datei liegt im Ziel‑Projekt (`/var/www/heldenregister`). Ziel ist, das Legacy‑Portal
funktional und datentechnisch nach Laravel zu überführen.

### Legacy‑Stack im Detail
- Routing über `config/_parmalinks.php` (eigenes Mini‑Router‑Array, kein Framework).
- Geschäftslogik in Service‑Klassen `config/_user.php` (`UserService`), `config/_hero.php` (`HeroService`), `config/_database.php` (`portaldb` PDO‑Wrapper), `config/_mail.php` (`SendMail` via PHPMailer + Smarty).
- Views: Smarty‑Templates + Semantic UI, AJAX‑Endpunkte unter `ajax/*.php`.
- DB‑Zugriff: roher PDO/SQL, teils über DB‑Views (`view_heroes`, `view_heroT1`, `view_heroT1Statistic`).
- Zusatzfeatures: PHPMailer (SMTP), QR‑Codes (`chillerlan/php-qrcode`), Matrix‑Chat‑Integration (`matrix_*` Tabellen).

### Aktueller Stand des Ziel‑Projekts (`/var/www/heldenregister`)
- Frisches `laravel/laravel`‑Skeleton mit Sanctum.
- Bereits angelegt (aber **leere Platzhalter**, noch nicht committet):
  - Models `Hero`, `Skill`, `Adventure` – ohne `$fillable`, Casts oder Relationen.
  - Migrationen `create_heroes_/skills_/adventures_table` – enthalten nur `id()` + `timestamps()`.
  - Resource‑Controller `HeroController`, `SkillController`, `AdventureController` – alle Methoden leer.
  - `routes/web.php` – nur Default‑Welcome‑Route; Resource‑Routen noch nicht registriert.
- Kein Auth‑Scaffolding (Breeze/Jetstream/Fortify) installiert.

---

## 2. Legacy‑Datenmodell (Quelle: `larp_buerokrat`)

25 Basistabellen + 3 Views. Kern‑Domänen:

### Benutzer & Rollen
- `portal_user` – Login‑Accounts (email, name, lastname, phone, password, activated, verified, lastlogin, session_id).
- `type_role` – Rollen: `keine, Admin, Registrar, Projektleiter, Spielleiter, Teamer, Event buchen, Teilnehmer`.
- `user2role` – n:m User↔Rolle.
- `user2player` – n:m User↔Spieler (`self`‑Flag, ob es der eigene Spieler ist).
- `tmp_password_reset` – Passwort‑Reset‑Tokens.

### Spieler & Helden
- `player` – reale Person (name, lastname, email, dayofbirth, gender, active, `hero_active`, soft‑delete via `deleted`).
- `hero` – Charakter (player_id, character_name, born, died, homeplace, active).
- `hero2classes` – Held↔Klasse (`class_id` referenziert `type_classes.idname`, **String‑Key!**).
- `hero2ep` – EP‑Transaktionsbuch (ep_count float, transEP_id, date_trans).
- `hero2skill` – gelernte Fertigkeiten (hero_id, skill_id, trained).
- `type_classes` – Klassen: warrior, ranger, wizard, healer, alchemist.
- `type_transEP` – EP‑Buchungsarten (Initiale EP, Fertigkeit erworben, Abenteuer bestritten …; `type` = „EP erworben“/„EP Kosten“).

### Fertigkeiten (Skills)
- `skills` – name, description, epcosts, level, icon (blob), masterclass→`type_classes`, perlcolor→`type_perlcolor`, perlcount. (78 Datensätze)
- `skills2class` – n:m Skill↔Klasse.
- `type_perlcolor` – Perlenfarben‑Lookup.

### Events / Abenteuer
- `event` – name, location→`location`, eventStartDate, eventEndeDate, loot_ep_day, gamemaster_id, eventleader_id, status→`type_eventStatus`, auftraggeber→`event_auftraggeber`, max_player, waitlist, category→`event_category`, fee.
- `event_booking` – Buchung (event_id, player_id, event_role→`type_event_role`, fotoerlaubnis, vegetarier, leih_tunika, leih_waffe, nsc, agb, allergien, medikamente, erreichbarkeit, created, approved, paid).
- `event_visit` – tatsächliche Teilnahme (event_id, player_id).
- `event_category`, `event_auftraggeber`, `location`, `type_eventStatus`, `type_event_role` – Lookups.

### Infrastruktur / sonstiges
- `matrix_account`, `matrix_joinedRoomIds`, `matrix_managedRoomIds` – Matrix‑Chat‑Anbindung (kann in Phase 2 nachgezogen oder als eigener Service entkoppelt werden).
- `portal_config` – Key/Value‑Config (version, root, logo, url …) → in Laravel durch `config/` + `.env` zu ersetzen.

### DB‑Views (in Laravel durch Eloquent‑Relationen/Accessoren ersetzen)
- `view_heroes` / `view_heroT1` – Held + Spielername + komma‑separierte Klassenliste (`GROUP_CONCAT`).
- `view_heroT1Statistic` – aggregierte Held‑Statistik (EP‑Summen etc.).

> ⚠️ **Datenqualitäts‑Stolpersteine**
> - `hero2classes.class_id` ist `varchar` und referenziert mal `type_classes.id`, mal `type_classes.idname` (die Views nutzen unterschiedliche Joins!). Vor Migration vereinheitlichen.
> - `player.active` / `user2player.self` sind `varchar` („on“/„off“) statt boolean.
> - Mehrere Tabellen ohne Primärschlüssel (`event_booking`, `event_visit`, `hero2classes`, `skills2class`, `user2role`, `user2player`, `portal_config`).
> - Passwörter in `portal_user.password` – Hash‑Verfahren prüfen (vermutlich `password_hash()`/bcrypt → direkt Laravel‑kompatibel; sonst Rehash‑on‑Login nötig).
> - Datenmengen sind klein (Helden 1, Spieler 4, Skills 78, Events 3) → einmalige ETL‑Migration unkritisch, gut testbar.

---

## 3. Ziel‑Datenmodell (Laravel)

Vorschlag für die Eloquent‑Modelle und Tabellen. Englische, Laravel‑konventionelle Namen; deutsche Fachbegriffe nur als Spalten/Labels.

| Laravel‑Model | Tabelle | Ersetzt Legacy |
|---|---|---|
| `User` | `users` | `portal_user` (+ `user2role`, `tmp_password_reset`) |
| `Role` | `roles` | `type_role` |
| `Player` | `players` | `player` (+ `user2player` als Pivot) |
| `Hero` | `heroes` | `hero` |
| `HeroClass` | `hero_classes` (Lookup) | `type_classes` |
| `Skill` | `skills` | `skills` |
| `PerlColor` | `perl_colors` | `type_perlcolor` |
| `EpTransaction` | `ep_transactions` | `hero2ep` (+ `type_transEP`) |
| `Adventure` (Event) | `adventures` | `event` |
| `Location` | `locations` | `location` |
| `EventCategory` | `event_categories` | `event_category` |
| `Booking` | `bookings` | `event_booking` |
| Pivot `hero_skill` | `hero_skill` | `hero2skill` |
| Pivot `hero_hero_class` | `hero_hero_class` | `hero2classes` |
| Pivot `skill_hero_class` | `skill_hero_class` | `skills2class` |

**Kern‑Relationen**
- `User` ↔ `Role` (belongsToMany), `User` ↔ `Player` (belongsToMany, Pivot‑Feld `self`).
- `Player` hasMany `Hero`; `Player` hasOne aktiver Held (`hero_active`).
- `Hero` belongsTo `Player`; belongsToMany `Skill` (Pivot `trained`); belongsToMany `HeroClass`; hasMany `EpTransaction`.
- `Skill` belongsTo `PerlColor`; belongsTo `HeroClass` (masterclass); belongsToMany `HeroClass` (skills2class).
- `Adventure` belongsTo `Location`, `EventCategory`, `EventStatus`; hasMany `Booking`; belongsToMany `Player` (Buchungen/Visits).
- EP‑Stand eines Helden = `sum(ep_transactions.ep_count nach Vorzeichen/Typ)` → Accessor statt DB‑View.

---

## 4. Migrationsstrategie

Inkrementell, in Phasen. Jede Phase ist lauffähig und testbar. Englische `snake_case`‑Spalten,
Boolean statt „on/off“, echte FKs + Soft‑Deletes (`deleted_at`) wo das Legacy `deleted`/`died` nutzt.

### Phase 0 – Fundament (Setup)
1. Auth‑Scaffolding wählen & installieren (Empfehlung: **Laravel Breeze**, Blade‑Stack – leichtgewichtig, passt zu serverseitigem Legacy‑UI). Alternativen siehe Abschnitt 7.
2. `.env` prüfen (DB `larp_heldenregister_laravel` ist bereits konfiguriert), `php artisan migrate` muss grün sein.
3. Code‑Style/Tooling: Pint, PHPUnit‑Setup verifizieren.
4. Layout‑Grundgerüst (Blade‑Layout, Navigation analog `functions/header.php`).

### Phase 1 – Benutzer, Rollen, Auth
- Migrationen: `roles`, `users` erweitern (phone, activated, verified, lastlogin), `role_user` Pivot.
- Models + Policies/Gates für Rollen (`Admin`, `Registrar`, `Projektleiter`, `Spielleiter`, `Teamer`, `Event buchen`, `Teilnehmer`).
- Login/Registrierung/Passwort‑Reset über Breeze; Legacy‑`tmp_password_reset` → Laravel‑Standard.
- **Passwort‑Migration:** bestehende Hashes übernehmen; falls nicht bcrypt → „rehash on next login“.

### Phase 2 – Spieler & Helden (Kernfunktion „Heldenregister“)
- Migrationen: `players`, `heroes`, `hero_classes`, `perl_colors`, `ep_transactions`, Pivots `hero_skill`, `hero_hero_class`, `player_user`.
- `skills` ausbauen (statt leerem Platzhalter): name, description, epcosts, level, masterclass, perlcolor, perlcount, icon.
- Models mit Relationen + Accessoren für EP‑Summe & Klassen‑Liste (ersetzt `view_heroT1*`).
- `HeroController` (resource) füllen: index/show/create/store/edit/update/destroy.
- Seeder für Lookups (`hero_classes`, `perl_colors`, `type_transEP`, `roles`).

### Phase 3 – Events / Abenteuer
- Migrationen: `locations`, `event_categories`, `event_statuses`, `event_auftraggeber`, `event_roles`, `adventures` (ausbauen), `bookings`, `event_visits`.
- `AdventureController` + `BookingController` (Buchung mit Foto‑Erlaubnis, Vegetarier, Leihwaffe, Allergien, AGB usw.).
- Anmelde‑/Warteliste‑Logik (`max_player`, `waitlist`, `approved`, `paid`).

### Phase 4 – Daten‑Migration (ETL `larp_buerokrat` → `larp_heldenregister_laravel`)
- Einmaliges Artisan‑Command (z.B. `php artisan migrate:legacy`) das pro Tabelle liest und ins neue Schema schreibt; idempotent, in Transaktion, mit Logging.
- Reihenfolge gemäß FK‑Abhängigkeit: roles → users → players → user↔player/role → hero_classes/perl_colors → skills → heroes → hero2classes/hero2skill/hero2ep → locations/categories → events → bookings/visits.
- `class_id`‑Inkonsistenz (id vs. idname) beim Lesen normalisieren.
- „on/off“‑ und Datums‑Felder in Boolean/`deleted_at` mappen.
- Validierungs‑Report nach Lauf (Zeilen je Tabelle Quelle vs. Ziel).

### Phase 5 – Zusatzfeatures & Abschluss
**Mail (erledigt).** Die Legacy-`SendMail`-Logik (PHPMailer + Smarty) wurde durch Laravel-Bordmittel ersetzt:
- `email_user_register_activate.tpl` → **E-Mail-Verifizierung** (`User implements MustVerifyEmail`; Breeze versendet die Aktivierungsmail über den `Registered`-Event automatisch).
- `email_resetpw.tpl` → Breeze-Passwort-Reset (bereits in Phase 0 vorhanden).
- `email_admin_new-user.tpl` → `App\Notifications\NewUserRegistered`, ausgelöst vom Listener `NotifyAdminsOfNewUser` an alle Admins. Getestet (`RegistrationNotificationTest`).

**QR-Codes (verworfen).** Die Legacy-Dependency `chillerlan/php-qrcode` ist in `composer.json` eingetragen, wird im Code aber **nirgends verwendet** (`grep` ohne Treffer). Kein Feature zu portieren – wird nicht übernommen.

**Matrix-Anbindung (umgesetzt – User-DB für den Matrix-Server).** Das Legacy-`corporal.php` ist ein [matrix-corporal](https://github.com/devture/matrix-corporal) **Policy-Provider**: matrix-corporal ruft periodisch eine JSON-Policy ab und gleicht damit den Matrix-/Synapse-Server ab (welche User existieren, deren Passwörter/`authType: plain`, Anzeigenamen, Raum-Mitgliedschaften, verwaltete Räume, Hooks). Diese DB ist also die Quelle der Wahrheit der Matrix-Benutzer. Migriert wurde:
- **Schema:** `matrix_managed_rooms` (← `matrix_managedRoomIds`), `matrix_accounts` (← `matrix_account`, „true/false"-Strings → Boolean, `password` → `auth_credential`, `deleted` → SoftDeletes), `matrix_room_memberships` (← `matrix_joinedRoomIds`).
- **Models:** `MatrixAccount` (PK = Matrix-User-ID, Relationen zu `Player` und `rooms`), `MatrixManagedRoom`.
- **Policy-Endpoint:** `GET /api/matrix/corporal/policy`, geschützt per Bearer-Token-Middleware (`VerifyMatrixCorporalToken`, Token aus `MATRIX_CORPORAL_TOKEN`). `CorporalPolicyController` erzeugt das exakte corporal-JSON (Flags & Hooks aus `config/matrix.php`). Gelöschte Konten werden ausgeschlossen.
- **ETL:** `migrate:legacy` migriert die drei Tabellen mit (verifiziert: 15 Räume, 3 Konten, 25 Mitgliedschaften; Policy-Ausgabe deckt sich 1:1 mit dem Legacy-`corporal.php`).
- **Tests:** `MatrixCorporalTest` (Token-Schutz, Policy-Struktur, Ausschluss gelöschter Konten).
- **Go-Live-Hinweis:** in der matrix-corporal-Konfiguration die Policy-URL auf `https://heldenregister.waldritter-giessen.de/api/matrix/corporal/policy` umstellen und `MATRIX_CORPORAL_TOKEN` setzen.

**Go-Live-Checkliste.**
1. `.env` für Produktion: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://heldenregister.waldritter-giessen.de`, echte Mail-Credentials (statt Mailpit), DB-Zugang.
2. `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` und einmalig `php artisan migrate:legacy` (ETL der Echtdaten).
4. **Legacy-Passwörter bereinigen:** `php artisan app:migrate-legacy-passwords` ausführen. Das Command erkennt alle Non-bcrypt-Passwörter (Klartext aus der ETL-Migration), setzt sie auf `null`, markiert die Konten (`needs_password_reset=true`) und verschickt automatisch Reset-Mails. Betroffene Nutzer werden beim nächsten Login-Versuch auf „Passwort vergessen" hingewiesen. Dry-Run vorab möglich: `--dry-run`.
5. `php artisan config:cache route:cache view:cache`, Storage-/Bootstrap-Cache-Rechte für `www-data`.
6. Queue-Worker einrichten (Notifications laufen sonst synchron) **oder** `QUEUE_CONNECTION=sync` belassen.
7. Webserver-Docroot auf `/var/www/heldenregister/public` umstellen (vhost), TLS prüfen.
8. Legacy unter `/var/www/html/larp-heldenregister/` read-only/offline nehmen (Backup behalten).

---

## 5. Konkrete nächste Schritte (Quick‑Win, Phase 0→1)

1. `composer require laravel/breeze --dev && php artisan breeze:install blade`
2. Platzhalter‑Migrationen `heroes`/`skills`/`adventures` mit echten Spalten füllen **oder** verwerfen und durch das vollständige Phasen‑Schema ersetzen (empfohlen, da noch nicht migriert/committet).
3. Resource‑Routen in `routes/web.php` registrieren:
   ```php
   Route::resource('heroes', HeroController::class);
   Route::resource('skills', SkillController::class);
   Route::resource('adventures', AdventureController::class);
   ```
4. Models mit `$fillable`, Casts und Relationen ausstatten.
5. Lookup‑Seeder anlegen (Klassen, Rollen, EP‑Typen, Perlenfarben, Event‑Status).

---

## 6. Risiken & offene Entscheidungen

- **Auth‑Stack:** Breeze (Blade) vs. Jetstream vs. eigenes – beeinflusst UI‑Aufwand. (Empfehlung: Breeze/Blade.)
- **Passwort‑Hashes** des Legacy‑Systems: Verfahren verifizieren, bevor Migration startet.
- **`hero2classes.class_id`** Doppeldeutigkeit (id vs. idname) – muss vor ETL bereinigt werden.
- **Matrix‑Integration**: behalten oder abtrennen? Größter Unsicherheitsfaktor im Scope.
- **DB‑Views** werden nicht migriert, sondern als Eloquent‑Logik nachgebaut – Aggregationen (EP‑Summen, Statistik) gegen Legacy gegenprüfen.
- **UI‑Framework:** Semantic UI (Legacy) vs. Tailwind (Laravel‑Default/Breeze) – Redesign vs. 1:1‑Nachbau abwägen.
- **Tabellen ohne PK** im Legacy → in Laravel saubere PKs/Composite‑Keys einführen.

---

## 7. Empfehlung

Die leeren Platzhalter (`Hero`/`Skill`/`Adventure`) noch **nicht** als Wahrheit nehmen, sondern
durch das vollständige Phasen‑Schema aus Abschnitt 3/4 ersetzen (sie sind noch nicht committet und
noch nicht migriert – jetzt der günstigste Zeitpunkt). Reihenfolge: **Phase 0 → 1 → 2** zuerst, da
„Heldenregister“ (Spieler + Helden + Skills + EP) der namensgebende Kern ist; Events (Phase 3) und
ETL (Phase 4) danach.
