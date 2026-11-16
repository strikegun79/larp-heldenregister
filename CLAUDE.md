# LARP Heldenregister

## Projekt

Webanwendung zur Verwaltung von LARP-Helden, Abenteuern und Charakterentwicklung.

## Technologie

- Laravel 12
- PHP 8.3+
- MySQL
- Blade Templates
- GitHub
- Fomantic-UI

## Entwicklungsregeln

- Laravel-Konventionen bevorzugen
- Deutsche Benutzeroberfläche
- Kommentare auf Deutsch
- PSR-12 Standard
- Keine unnötigen Frameworks

## Vorgehensweise

Vor jeder Änderung:

1. Plan beschreiben
2. Betroffene Dateien nennen
3. Auf Freigabe warten

Nach jeder Änderung:

1. Zusammenfassung liefern
2. Git-Commit-Vorschlag erstellen

## Module

- Benutzerverwaltung
- Heldenverwaltung
- Skillsystem
- Abenteuerverwaltung
- Heldenarchiv
- Administration

## Ziel

Das bestehende PHP/MySQL-System wird schrittweise nach Laravel migriert.
Bestehende Funktionalität soll erhalten bleiben.
## Entwicklungsumgebung und Produktionsschutz

Dieses Projekt wird ausschließlich in der Entwicklungsumgebung bearbeitet.

**Entwicklungsverzeichnis:**
`/home/laraveldev/apps/heldenregister`

**Produktionsverzeichnis (geschützt):**
`/var/www/heldenregister`

### Verbindliche Regeln für alle Agenten

1. Sämtliche Codeänderungen erfolgen ausschließlich im Entwicklungsverzeichnis.
2. Das Produktionsverzeichnis darf nicht verändert werden.
3. Agenten-Memory-Dateien werden ausschließlich unter `.claude/agent-memory/` innerhalb des Entwicklungsprojekts gepflegt.
4. Datenbankoperationen dürfen ausschließlich die Entwicklungsdatenbank betreffen.
5. Migrationen, Tests und Seeders dürfen niemals gegen die Produktionsdatenbank ausgeführt werden.
6. Es dürfen keine produktiven Dienste, Scheduler, Queues oder Deployments eigenständig gestartet oder verändert werden.
7. Git-Commits und Pushes dürfen ausschließlich auf Entwicklungsbranches erfolgen. Änderungen am Produktionsbranch benötigen eine explizite Freigabe.
8. Deployments in Produktion werden ausschließlich nach manueller Freigabe durchgeführt.
9. Besteht Unsicherheit darüber, ob ein Befehl Produktionsdaten oder Produktivdienste beeinflusst, darf der Befehl nicht ausgeführt werden.

Diese Regeln gelten auch für alle Subagenten.

### Entwicklungsdaten erhalten

Die Entwicklungsdatenbank enthält manuell angelegte Benutzer, Spieler und Helden,
die beim Entwickeln und Testen erhalten bleiben müssen.

1. Keine löschenden Befehle: `migrate:fresh`, `migrate:refresh`, `migrate:reset`,
   `db:wipe` und `migrate:legacy --fresh` sind verboten. Sie sind zusätzlich technisch
   gesperrt (`DB::prohibitDestructiveCommands` im `AppServiceProvider`).
   Die Sperre (`ALLOW_DESTRUCTIVE_DB_COMMANDS`) darf von Agenten nicht aufgehoben werden.
2. Schemaänderungen nur über neue Migrationen mit `php artisan migrate`.
   Rollbacks (`migrate:rollback`) sind ebenfalls technisch gesperrt und nur nach Rückfrage erlaubt.
   Seeder (`db:seed`) arbeiten mit `firstOrCreate` und dürfen jederzeit laufen; geänderte
   Standardwerte für bestehende Datenbanken werden per Migration ausgerollt.
3. Keine Testläufe gegen die Entwicklungsdatenbank. Tests mit `RefreshDatabase`
   laufen nur gegen eine Datenbank mit Endung `_test` (Schutz in `tests/TestCase.php`).
4. Funktionen am echten Datenbestand nur in einer Transaktion mit anschließendem
   Rollback prüfen (z. B. per `php artisan tinker`), sofern keine dauerhafte Änderung
   freigegeben wurde.
5. Vor riskanten Datenänderungen einen Snapshot anlegen:
   `scripts/dev-db-snapshot.sh save <name>`; Wiederherstellung mit
   `scripts/dev-db-snapshot.sh restore <name>` nur nach Rückfrage.
