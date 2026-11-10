# Architektur – LARP Heldenregister

## Ziel der Anwendung

Das LARP Heldenregister ist eine Laravel-Webanwendung zur Verwaltung von:

- Benutzern
- Helden
- Heldenklassen
- Helden-Gruppen
- Abenteuern / Events
- Event-Status
- Audit-Logs
- administrativen Stammdaten

Die Anwendung dient dem Waldritter-Gießen e.V. zur Organisation von Kinder- und Jugend-LARP-Veranstaltungen.

## Technologie

- Laravel
- PHP
- MySQL / MariaDB
- Blade Templates
- JavaScript nur dort, wo nötig
- GitHub zur Versionsverwaltung

## Grundprinzipien

- Laravel-Konventionen bevorzugen
- Keine unnötige Eigenarchitektur
- Business-Logik nicht in Blade-Dateien
- Controller schlank halten
- Datenbankzugriffe über Eloquent Models
- Validierung über Form Requests
- Rechte über Policies / Gates
- Wiederverwendbare UI-Elemente als Blade Components

## Verzeichnisstruktur

```text
app/
├── Models/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Policies/
└── Services/

resources/
├── views/
│   ├── layouts/
│   ├── components/
│   ├── heroes/
│   ├── adventures/
│   ├── admin/
│   └── profile/