# Coding Standards – LARP Heldenregister

## 1. Ziel

Diese Datei beschreibt die Entwicklungsregeln für das Laravel-Projekt **LARP Heldenregister**.

Sie gilt für alle Entwickler und für Claude Code. Ziel ist ein wartbares, sicheres und konsistentes Laravel-Projekt.

---

## 2. Grundregeln

- Laravel-Konventionen haben Vorrang.
- Code soll einfach, lesbar und wartbar sein.
- Keine unnötig komplexe Architektur.
- Kleine Änderungen statt großer Umbauten.
- Bestehende Funktionalität darf nicht unbeabsichtigt verändert werden.
- Jede Aufgabe soll möglichst als eigener Git-Commit abgeschlossen werden.
- Keine Neben-Refactorings ohne ausdrückliche Anforderung.
- Vor größeren Änderungen immer zuerst Plan und betroffene Dateien nennen.

---

## 3. Sprache

### Benutzeroberfläche

Die Benutzeroberfläche ist auf Deutsch.

Beispiele:

- Held wurde gespeichert.
- Abenteuer bearbeiten
- Zurück zur Übersicht
- Der Held konnte nicht gelöscht werden.

### Code

Technische Namen im Code sind Englisch.

Gute Beispiele:

- `HeroController`
- `AdventureController`
- `EventStatus`
- `AuditLog`
- `HeroGroup`

Nicht verwenden:

- `HeldenController`
- `AbenteuerController`
- `EreignisStatus`
- `PruefLog`

---

## 4. PHP-Standards

- PSR-12 verwenden.
- Typen verwenden, wo sinnvoll.
- Methoden klein und verständlich halten.
- Keine langen verschachtelten Bedingungen.
- Keine Magic Numbers.
- Keine toten Codeblöcke stehen lassen.
- Keine Debug-Ausgaben committen.
- Keine sensiblen Daten im Code speichern.

Nicht committen:

- `dd($data)`
- `dump($request)`
- `var_dump($data)`
- `print_r($data)`
- temporäre Testausgaben

---

## 5. Laravel-Regeln

### Controller

Controller sollen schlank bleiben.

Controller dürfen:

- Requests entgegennehmen
- Form Requests verwenden
- Models oder Services aufrufen
- Views oder Redirects zurückgeben

Controller sollen nicht:

- komplexe Geschäftslogik enthalten
- direkte SQL-Abfragen enthalten
- große Datenstrukturen unnötig selbst zusammenbauen
- Validierung mehrfach duplizieren
- ungeprüfte Request-Daten speichern

Bevorzugtes Muster:

- Validierung im Form Request
- Geschäftslogik in Service oder Model-Methode
- Rückgabe über Redirect oder View

---

## 6. Form Requests

Für Create- und Update-Formulare sollen Form Requests verwendet werden.

Beispiele:

- `StoreHeroRequest`
- `UpdateHeroRequest`
- `StoreAdventureRequest`
- `UpdateAdventureRequest`
- `StoreHeroGroupRequest`
- `UpdateHeroGroupRequest`

Form Requests enthalten:

- Validierungsregeln
- deutsche Fehlermeldungen, falls nötig
- Berechtigungsprüfung, wenn sinnvoll

Regel:

- Bei Form Requests immer `$request->validated()` verwenden.
- Keine direkte Nutzung von `$_POST`, `$_GET` oder `$_REQUEST`.

---

## 7. Models

Models enthalten:

- Beziehungen
- Casts
- Scopes
- einfache Hilfsmethoden

Models sollen nicht enthalten:

- große Geschäftsprozesse
- HTML-Ausgaben
- Request-Verarbeitung
- komplexe Controller-Logik

Beispiele für Model-Namen:

- `User`
- `Hero`
- `HeroClass`
- `HeroGroup`
- `Adventure`
- `EventStatus`
- `AuditLog`

---

## 8. Services

Services werden verwendet, wenn Logik zu groß für Controller oder Model wird.

Beispiele:

- `HeroArchiveService`
- `AdventureParticipationService`
- `AuditLogService`
- `HeroGroupAssignmentService`

Services sollen:

- fachliche Abläufe kapseln
- wiederverwendbar sein
- testbar bleiben
- keine View-Logik enthalten

---

## 9. Blade Views

Blade-Dateien sollen nur Darstellung enthalten.

Blade-Dateien dürfen:

- Daten anzeigen
- einfache Bedingungen enthalten
- Blade Components verwenden
- Berechtigungen über `@can` prüfen

Blade-Dateien sollen nicht:

- Datenbankabfragen ausführen
- komplexe Berechnungen enthalten
- Geschäftslogik enthalten
- Berechtigungslogik duplizieren

Regel:

- Wiederkehrende UI-Elemente als Blade Components auslagern.
- Bestehendes mittelalterliches Design erhalten.

---

## 10. Routen

Routen sollen sprechend und konsistent sein.

Allgemeine Routen:

- `/heroes`
- `/heroes/{hero}`
- `/adventures`
- `/profile`

Admin-Routen liegen unter:

- `/admin`

Beispiele für Controller-Namen:

- `HeroController`
- `AdventureController`
- `Admin\EventStatusController`
- `Admin\HeroGroupController`

Admin-Routen müssen geschützt sein durch:

- `auth`
- passende Rollen-/Rechteprüfung
- Middleware oder Policies

---

## 11. Datenbank

- Jede Strukturänderung erfolgt über Migrationen.
- Tabellen werden plural und auf Englisch benannt.
- Spaltennamen werden auf Englisch benannt.
- Fremdschlüssel verwenden, wenn sinnvoll.
- Pivot-Tabellen nach Laravel-Konvention benennen.
- Soft Deletes nur dort verwenden, wo sie fachlich sinnvoll sind.

Gute Tabellennamen:

- `users`
- `heroes`
- `hero_classes`
- `hero_groups`
- `hero_group_hero`
- `adventures`
- `adventure_hero`
- `event_statuses`
- `audit_logs`

Nicht verwenden:

- `helden`
- `abenteuer`
- `gruppen`
- `klassen`

---

## 12. Validierung

Alle Benutzereingaben werden validiert.

Regeln:

- Keine ungeprüften Request-Daten speichern.
- Keine direkte Nutzung von `$_POST`, `$_GET` oder `$_REQUEST`.
- Form Requests bevorzugen.
- Fehlermeldungen sollen für Benutzer verständlich sein.
- Validierung serverseitig ist Pflicht.
- JavaScript-Validierung darf nur ergänzend sein.

---

## 13. Sicherheit

- CSRF-Schutz aktiv lassen.
- Admin-Bereiche immer mit Middleware schützen.
- Rechte serverseitig prüfen.
- Keine sensiblen Daten ins Repository committen.
- `.env` niemals committen.
- Keine Passwörter oder Tokens im Code speichern.
- Keine ungeprüften Datei-Uploads erlauben.
- Keine unescaped Ausgaben mit `{!! !!}` außer bewusst und begründet.
- Keine direkten SQL-Abfragen mit ungeprüften Benutzereingaben.
- SQL nur über Eloquent oder Query Builder, außer es gibt einen guten Grund.

---

## 14. Berechtigungen

Berechtigungen sollen über Laravel Policies, Gates oder Middleware geregelt werden.

Beispiele für Rechte:

- Held anzeigen
- Held erstellen
- Held bearbeiten
- Held archivieren
- Abenteuer verwalten
- Administration betreten
- Event-Status ändern
- Audit-Log ansehen

Regel:

- Berechtigungen müssen serverseitig geprüft werden.
- Sichtbarkeit in Blade ersetzt keine serverseitige Prüfung.

---

## 15. Audit-Log

Wichtige Änderungen sollen protokolliert werden.

Beispiele:

- Held erstellt
- Held bearbeitet
- Held archiviert
- Abenteuer erstellt
- Abenteuer geändert
- Event-Status geändert
- Helden-Gruppe geändert
- Benutzerrolle geändert
- Admin-Stammdaten geändert

Ein Audit-Log-Eintrag soll enthalten:

- Benutzer
- Aktion
- betroffenes Objekt
- Zeitpunkt
- optionale Details

---

## 16. Frontend

- Blade bevorzugen.
- JavaScript nur einsetzen, wenn es echten Mehrwert bringt.
- Keine unnötigen neuen Frontend-Frameworks einführen.
- Bestehendes mittelalterliches Design erhalten.
- Wiederverwendbare UI als Blade Component auslagern.
- CSS-Klassen konsistent benennen.
- Keine Inline-Styles, wenn eine CSS-Klasse sinnvoller ist.
- Formulare sollen klar und mobil nutzbar sein.

---

## 17. Texte und Fehlermeldungen

Texte für Benutzer sollen deutsch, freundlich und verständlich sein.

Gute Beispiele:

- Der Held wurde erfolgreich gespeichert.
- Bitte prüfe die Eingaben.
- Du hast keine Berechtigung für diese Aktion.
- Das Abenteuer konnte nicht gefunden werden.

Schlechte Beispiele:

- Validation failed.
- SQL Error.
- Unknown exception.
- Forbidden.

---

## 18. Git-Regeln

Vor jeder Aufgabe:

- `git status` prüfen
- sicherstellen, dass keine fremden Änderungen überschrieben werden

Nach jeder abgeschlossenen Aufgabe:

- Änderungen prüfen
- Tests oder manuelle Prüfung durchführen
- Commit erstellen

Commit-Nachrichten sollen kurz und eindeutig sein.

Gute Beispiele:

- `HM-001 Add hero model`
- `HG-014 Add hero group assignment`
- `FIX Fix adventure status validation`
- `UI Update hero card layout`

Nicht verwenden:

- `Update`
- `fix`
- `changes`
- `alles neu`
- `test`

---

## 19. Claude-Code-Regeln

Claude Code soll bei jeder Aufgabe:

1. `CLAUDE.md` lesen.
2. passende Dokumentation in `docs/` lesen.
3. das relevante Ticket im Backlog lesen.
4. einen Plan anzeigen.
5. betroffene Dateien nennen.
6. nur die angeforderte Aufgabe bearbeiten.
7. keine Neben-Refactorings durchführen.
8. nach Abschluss Tests oder Prüfkommandos nennen.
9. einen Commit-Vorschlag liefern.
10. den Backlog-Status nur ändern, wenn die Aufgabe abgeschlossen ist.

Claude Code soll nicht:

- mehrere Tickets gleichzeitig bearbeiten
- große Architekturänderungen ohne Freigabe durchführen
- bestehende Funktionen ohne Auftrag umbauen
- unnötige Pakete installieren
- `.env` oder sensible Dateien verändern

Beispielprompt für Claude Code:

> Lies CLAUDE.md, docs/architecture.md, docs/coding-standards.md und docs/backlog/hero-groups.md.
>
> Implementiere Ticket HG-001.
>
> Wichtig:
> - nur dieses Ticket
> - keine Neben-Refactorings
> - zeige zuerst Plan und betroffene Dateien
> - nach Abschluss Tests nennen und Commit-Vorschlag liefern

---

## 20. Tests und Prüfung

Nach Änderungen sollen passende Prüfungen durchgeführt werden.

Allgemeine Prüfungen:

- `php artisan test`
- `php artisan route:list`
- `php artisan migrate:status`

Bei Cache-Problemen:

- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan view:clear`
- `php artisan route:clear`

Bei Frontend-Änderungen:

- `npm run build`

Manuelle Prüfung:

- Seite im Browser öffnen
- Formular testen
- Validierungsfehler testen
- Berechtigungen testen
- mobile Ansicht grob prüfen

---

## 21. Definition of Done

Eine Aufgabe gilt als erledigt, wenn:

- Akzeptanzkriterien erfüllt sind
- keine offensichtlichen Fehler bestehen
- bestehende Funktionen nicht beschädigt wurden
- relevante Tests oder manuelle Prüfungen durchgeführt wurden
- betroffene Dokumentation aktualisiert wurde
- Backlog-Status aktualisiert wurde
- Git-Commit erstellt wurde oder ein Commit-Vorschlag vorliegt

---

## 22. Umgang mit bestehenden Funktionen

Bestehende Funktionen haben Vorrang.

Bei Änderungen an bestehenden Modulen:

- vorhandenes Verhalten zuerst verstehen
- keine Datenstruktur ohne Migration ändern
- keine Routen ohne Prüfung entfernen
- keine Views löschen, wenn sie noch referenziert werden
- keine CSS-Klassen entfernen, wenn sie mehrfach genutzt werden

---

## 23. Projektbesonderheiten

Das LARP Heldenregister ist kein generisches Verwaltungssystem.

Fachliche Begriffe sollen konsistent verwendet werden:

- Held
- Abenteuer
- Heldenklasse
- Helden-Gruppe
- Heldenarchiv
- Event-Status
- Audit-Log
- Spielleitung
- Administration

Die technische Umsetzung bleibt trotzdem englisch:

- `Hero`
- `Adventure`
- `HeroClass`
- `HeroGroup`
- `EventStatus`
- `AuditLog`
