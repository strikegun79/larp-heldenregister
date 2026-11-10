# Roadmap – LARP Heldenregister

Produkt-Roadmap für das Waldritter-Gießen-Vereinsportal.

> Stand: 2026-09-10 · Basis: `master` (345 Commits seit Erstversion 2026-06-09)

---

## 1. Vision

Das Heldenregister ist das Vereinsportal für **Kinder- und Jugend-LARP**:
Mitglieder verwalten **Spieler** (reale Personen) und deren **Helden**
(Charaktere), melden sich zu **Abenteuern** (Veranstaltungen) an und verfolgen
ihren Charakterfortschritt über **Erfahrungspunkte (EP)** und **Fertigkeiten**.
Die Vereinsverwaltung pflegt Nutzer, Rollen, Events und die Matrix-Chat-Konten.

---

## 2. Aktueller Stand – vollständig umgesetzt ✅

### Auth & Profil
- Registrierung, Login, E-Mail-Verifizierung (Breeze)
- Profilseite mit Adress-, Telefon- und Benachrichtigungseinstellungen
- Kontolöschung mit DSGVO-konformer Anonymisierung (`User::anonymize()`)
- Datenexport Art. 20 DSGVO (JSON-Download aller personenbezogenen Daten)
- Passwort-Migration aus Legacy-Klartext

### Rollen & Rechte
- Permission-Matrix (`config/permissions.php`), 10+ Rollen
- Rollenübersicht für Bürokrat/Admin mit DSGVO-Zugriffsrechten
- Pflichtbenachrichtigungen je Rolle; Teamer-Rollen mit eigenem Rechteumfang

### Spielerverwaltung
- Spieler-CRUD mit Eltern-Kind-Zuordnung (`self`-Flag, Guardian-Adresse)
- Profilbild, Gesundheitsdaten (Art. 9 DSGVO, verschlüsselt), Altersprüfung
- Admin-CSV-Export, Spielerliste mit Filtern
- Profilfotos beim Hard-Delete sauber aus Storage entfernt

### Heldenverwaltung
- Helden-CRUD, Klassen, Fertigkeiten-Baum (Voraussetzungen, Stufen)
- EP-Buchungsbuch: Gutschriften, Kosten, Saldo, Abenteuer-EP
- Galerie-Bilder pro Held, öffentliche Heldenansicht (opt-in) + 6-stelliger Code
- Helden-API (öffentlich, ohne Realnamen)
- Heldenausweise generieren & zuweisen (PDF + QR-Code)
- Abenteuerhistorie je Held

### Abenteuerverwaltung
- Event-CRUD (Ort, Kategorie, Status, Eventleiter, Spielleiter)
- Anmeldungen: Teilnehmer, Gäste, Gruppenanmeldung, Teamer-Bypass
- Wartelisten-System mit automatischem Aufrücken + Benachrichtigung
- Altersgrenzen (Mindestalter/Höchstalter) mit Wartelisten-Override
- Wartelistenmodus-Toggle in der Verwaltungsansicht
- Teilnehmerlisten-PDF, GiroCode-QR für Zahlungen
- Fotoerlaubnis je Buchung – Widerruf jederzeit (Art. 7 DSGVO)
- E-Mail an alle Teilnehmer einer Veranstaltung

### Check-in & Teilnahme
- Unterschriften-Pad (Tablet/Canvas, AES-256-verschlüsselt, 30-Tage-Prune)
- Direkter Löschen-Button für Unterschriften in der Check-in-Übersicht
- Anwesenheitserfassung (`event_visits`), EP-Automatik nach Teilnahme
- Aus- und Einchecken in der Verwaltungsansicht

### Benachrichtigungen
- E-Mail: Anmeldebestätigung (Pflicht), Annahme/Ablehnung/Stornierung,
  Warteliste, Veranstaltungsabsage, Zahlungsbestätigung, Erinnerung
- Abmeldebericht-Mail an Projektleitung
- Fotoerlaubnis-Widerruf benachrichtigt Projektleitung + Kontakt-E-Mail
- In-App-Benachrichtigungen (Glocke, Markierung gelesen/ungelesen)
- Fehlerseite für abgelaufene Bestätigungslinks

### Newsletter
- Admin-CRUD (Erstellen, Bearbeiten, Vorschau, Versenden, Löschen)
- WYSIWYG-Editor (Summernote) mit Tabellen, Farben, Bild-Upload
- Abonnenten-Verwaltung mit Double-Opt-in per E-Mail
- Dashboard-Banner für Nicht-Abonnenten
- Abonnenten-Statusanzeige im Profil

### Umfragen & Feedback
- Template-basierte Umfragen (Jugendförderungs-Fokus)
- Öffentlicher Teilnahme-Flow mit Link-Token
- Admin-Ergebnisauswertung, Vorlagen-Verwaltung

### Gruppen
- Gruppen-CRUD (Gilden, Trupps), Mitgliederverwaltung mit Rollen
- Gruppenanmeldung für Abenteuer

### Admin-Werkzeuge
- Stammdaten-CRUDs: Orte, Kategorien, Status, Event-Rollen, Perlenfarben,
  EP-Buchungsarten, Auftraggeber, Matrix-Räume, Skill-Icons
- Audit-Log (alle Admin-Aktionen auf Buchungen und Spielerprofilen)
- Datenpannen-Protokoll Art. 33/34 DSGVO
- Verarbeitungsverzeichnis Art. 30 DSGVO (druckbar)
- Portal-Einstellungen (Vereinsname, Kontakt-E-Mail, etc.)
- Nutzer-CRUD: Rollen vergeben, aktivieren, Profil bearbeiten

### Matrix-Integration
- Corporal-Policy (Provisionierung neuer Mitglieder)
- Matrix-Konto-Verwaltung pro Spieler (Admin-CRUD)
- Matrix-Räume pflegen

### Daten-Migration
- Legacy-Import (`migrate:legacy`) vollständig

### DSGVO-Compliance
- Verschlüsselung biometrischer Daten (Unterschriften AES-256)
- Datenschutzerklärung, lokale Fonts (kein Google-Tracking)
- Einwilligungsverwaltung: Gesundheitsdaten-Consent, Fotoerlaubnis
- Automatischer Prune (`dsgvo:prune`, täglich 03:00):
  Unterschriften (30 Tage), Gesundheitsdaten (2 Jahre), Buchungsdaten (3 Jahre),
  Audit-Logs (3 Jahre), Notifications (1 Jahr), Spieler-Hard-Delete (3 Jahre),
  User-Hard-Delete (3 Jahre)
- Profilfotos und Galerie-Bilder beim Löschen sauber aus Storage entfernt
- Datenexport Art. 20 (JSON-Download)

### Infrastruktur & Qualität
- Queue-Worker via Supervisor (database-Driver)
- CI/CD-Pipeline, Deployment-Dokumentation
- Lokale Fomantic-UI-Assets (kein CDN)
- Save-Data / Light-Mode für schlechte Netzverbindungen
- Onboarding-Dashboard mit Fortschrittsanzeige
- Browser-Test-Suite (Feature-Tests für alle Kernflows)

---

## 3. Meilensteine – alle abgeschlossen ✅

| # | Meilenstein | Abgeschlossen |
|---|---|---|
| M1 | Go-Live-Reife (Auth, Profile, Deployment) | Jun 2026 |
| M2 | EP-Ökonomie & Fertigkeiten-Baum | Jun/Jul 2026 |
| M3 | Event-Lebenszyklus (Check-in, EP-Vergabe, Warteliste) | Jun/Jul 2026 |
| M4 | Kommunikation (Mails, Queue, Newsletter) | Jul/Aug 2026 |
| M5 | Admin-Werkzeuge (Stammdaten, Audit, Datenpannen) | Aug 2026 |
| M6 | Auswertungen & Exporte | Aug 2026 |
| M7 | Qualität, Accessibility, Mobile, Performance | Aug 2026 |
| M8 | Öffentlichkeit & Community (Heldenansicht, Gruppen) | Aug 2026 |
| DSGVO | Vollständige DSGVO-Compliance | Sep 2026 |

---

## 4. Mögliche nächste Themen

Das Kernprodukt ist feature-complete. Mögliche Folge-Iterationen:

### Erweiterungen (konkret benannt, noch nicht priorisiert)

| Thema | Beschreibung |
|---|---|
| Selbstauskunft im Portal | Art.-17/20-Anfragen direkt im Profil stellen (statt per E-Mail) |
| Eltern-Zugang für Kinder | Minderjährige können sich selbst einloggen; Eltern erhalten Übersicht |
| Öffentlicher Veranstaltungskalender | Events ohne Login sichtbar (opt-in per Event) |
| Wartelisten-Priorisierung | Reihenfolge auf Warteliste manuell anpassbar |
| Wiederholende Events | Serie/Template für regelmäßige Veranstaltungen |
| Charakterbogen-PDF | Druckbarer Heldenbogen mit Fertigkeiten und EP |
| Inventar-Verwaltung | Ausrüstungsgegenstände pro Held erfassen |

### Technische Schulden (niedrig, kein akuter Handlungsbedarf)

| # | Beschreibung |
|---|---|
| T-1 | Feature-Tests für Admin-CRUD-Flows (Orte, Kategorien etc.) fehlen weitgehend |
| T-2 | API-Versionierung fehlt (aktuell `/api/` ohne Version) |
| T-3 | Keine Rate-Limiting-Konfiguration für öffentliche Routen |
| T-4 | Queue-Job-Monitoring (failed jobs, Alerting) nicht eingerichtet |

---

## 5. Backlog-Übersicht

| Datei | Bereich | Präfix | Stand |
|---|---|---|---|
| [auth-profile](backlog/auth-profile.md) | Auth, Profil | AUTH | ✅ alle |
| [roles-permissions](backlog/roles-permissions.md) | Rollen & Rechte | ROLE | ✅ alle |
| [players](backlog/players.md) | Spielerverwaltung | PLAY | ✅ alle |
| [heroes](backlog/heroes.md) | Helden & Klassen | HERO | ✅ alle |
| [skills-ep](backlog/skills-ep.md) | Fertigkeiten & EP | SKILL/EP | ✅ alle |
| [adventures](backlog/adventures.md) | Events/Abenteuer | ADV | ✅ alle |
| [bookings-visits](backlog/bookings-visits.md) | Buchungen & Teilnahme | BOOK | ✅ alle |
| [matrix](backlog/matrix.md) | Matrix-Integration | MTX | ✅ alle |
| [public-access](backlog/public-access.md) | Öffentliche Heldenansicht | PUB | ✅ alle |
| [groups](backlog/groups.md) | Gruppenverwaltung | GRP | ✅ alle |
| [admin-lookups](backlog/admin-lookups.md) | Stammdaten | ADM | ✅ alle |
| [notifications](backlog/notifications.md) | Benachrichtigungen | NOTI | ✅ alle |
| [reporting](backlog/reporting.md) | Auswertungen/Exporte | REP | ✅ alle |
| [ui-ux](backlog/ui-ux.md) | Oberfläche | UI | ✅ alle |
| [data-migration](backlog/data-migration.md) | ETL/Legacy | ETL | ✅ alle |
| [infrastructure](backlog/infrastructure.md) | Betrieb/CI/Deploy | INFRA | ✅ alle |
| [quality-testing](backlog/quality-testing.md) | Tests/Qualität | QA | ✅ alle |
