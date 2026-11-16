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
