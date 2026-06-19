# Roadmap – LARP Heldenregister

Produkt-Roadmap für die Laravel-Neuentwicklung des Heldenregisters
(Waldritter-Gießen e.V.). Sie ordnet die Funktionsbereiche in Meilensteine
und verweist auf das feingranulare [Backlog](backlog/README.md) (Aufgaben
à 2–4 h).

> Stand: 2026-06-09 · Quelle der Analyse: aktueller `master` (PR #1).

---

## 1. Vision

Das Heldenregister ist das Vereinsportal für **Kinder- und Jugend-LARP**:
Mitglieder verwalten **Spieler** (reale Personen) und deren **Helden**
(Charaktere), melden sich zu **Abenteuern** (Veranstaltungen) an und verfolgen
ihren Charakterfortschritt über **Erfahrungspunkte (EP)** und **Fertigkeiten**.
Die Vereinsverwaltung pflegt Nutzer, Rollen, Events und die Matrix-Chat-Konten.

Quelle der Produktziele ist [vision.md](vision.md). Daraus ergeben sich über den
bereits gebauten Kern hinaus folgende Zielfunktionen, die im Backlog abgebildet
sind:

- **Fertigkeiten-Baum pro Klasse** (mit Voraussetzungen), nicht nur flache Liste.
- **Abenteuerhistorie** je Held/Spieler.
- **Gruppenverwaltung** (LARP-Gruppen/Trupps).
- **Öffentliche Heldenansicht ohne Realnamen** + **Heldensuche per 6-stelligem
  Code**, den jedes Kind real erhält und im Heldenprofil sieht.

## 2. Aktueller Stand (✅ umgesetzt)

| Bereich | Stand |
|---|---|
| Authentifizierung & Registrierung (Breeze) | ✅ inkl. E-Mail-Verifizierung |
| Rollen & Rechte (Permission-Matrix, `config/permissions.php`) | ✅ |
| Spielerverwaltung (eigene Spieler, self-Flag) | ✅ Basis |
| Heldenregister (Helden-CRUD + Klassen) | ✅ Basis |
| Abenteuer & Buchungen (anlegen, buchen, stornieren, Warteliste) | ✅ Basis |
| Verwaltung (Nutzer-Rollen/Aktivierung, Spielerliste) | ✅ Basis |
| Matrix-Integration (corporal-Policy + Provisionierung) | ✅ |
| Daten-Migration aus Legacy (`migrate:legacy`) | ✅ |
| Mail (Verifizierung, Admin-Benachrichtigung) | ✅ |
| UI (Fomantic UI, Mittelalter-Theme, Modals, AJAX-Toasts) | ✅ |

## 3. Wesentliche Lücken (🔲 offen)

- **Fertigkeiten & EP-Ökonomie** – `SkillController` ist ein Stub; kein
  Skill-Lernen mit EP-Abzug, keine EP-Buchungs-Oberfläche, keine Klassenkosten.
- **Event-Lebenszyklus** – keine Teilnahme-Erfassung (`event_visit`), keine
  Buchungsbearbeitung (`adventure.modify`), keine automatische EP-Vergabe.
- **Stammdaten-/Lookup-Verwaltung** – Orte, Kategorien, Status, Event-Rollen,
  Perlenfarben, Matrix-Räume haben kein Admin-CRUD.
- **Benachrichtigungen** – keine Buchungsbestätigung/Warteliste/Erinnerung.
- **Auswertungen** – keine Helden-Statistik/EP-Übersichten/Exporte.
- **Vision-Funktionen** – Fertigkeiten-Baum, Gruppenverwaltung, öffentliche
  Heldenansicht + 6-stelliger Code, Abenteuerhistorie fehlen vollständig.
- **Go-Live & Qualität** – Profil-Felder, Passwort-Migration, CI/Deployment,
  Suche/Filter, Browser-Tests fehlen.

---

## 4. Meilensteine

Jeder Meilenstein bündelt Epics; die Details stehen in den Backlog-Dateien.

### M1 · Go-Live-Reife
Produktivschaltung des bereits gebauten Funktionsumfangs.
- Profil um `lastname`/`phone` erweitern · Passwort-Migration (Legacy-Klartext)
- Deployment, Prod-`.env`, Queue-Worker, CI-Pipeline
- Resthärtung Auth/Autorisierung
→ Backlog: [auth-profile](backlog/auth-profile.md), [infrastructure](backlog/infrastructure.md)

### M2 · EP-Ökonomie & Fertigkeiten
Das namensgebende LARP-Kernfeature.
- Fertigkeiten-Verwaltung (CRUD, Klassenzuordnung, Perlenfarbe)
- **Fertigkeiten-Baum pro Klasse** (Voraussetzungen) · Skill-Lernen mit EP-Abzug
- EP-Buchungsbuch-Oberfläche · Klassenkosten
→ Backlog: [skills-ep](backlog/skills-ep.md), [heroes](backlog/heroes.md)

### M3 · Event-Lebenszyklus
Vom geplanten Event bis zur abgerechneten Teilnahme.
- Buchung bearbeiten · Teilnahme erfassen (`event_visit`)
- Automatische EP-Vergabe nach Teilnahme · Status-Workflow · Wartelisten-Aufrücken
→ Backlog: [adventures](backlog/adventures.md), [bookings-visits](backlog/bookings-visits.md)

### M4 · Kommunikation
- Buchungsbestätigung, Wartelisten-/Abmelde-/Erinnerungs-Mails (Mailables, Queue)
→ Backlog: [notifications](backlog/notifications.md)

### M5 · Admin-Werkzeuge
- Lookup-CRUD (Orte, Kategorien, Status, Event-Rollen, Perlenfarben)
- Matrix-Raum-Verwaltung · Audit-Log
→ Backlog: [admin-lookups](backlog/admin-lookups.md), [matrix](backlog/matrix.md)

### M6 · Auswertungen
- Helden-Statistik · EP-Übersichten · Teilnahme-Reports · Charakterbogen-PDF
→ Backlog: [reporting](backlog/reporting.md)

### M7 · Härtung & Qualität
- Suche/Filter/Sortierung · Browser-Tests · i18n · Accessibility · Performance
→ Backlog: [ui-ux](backlog/ui-ux.md), [quality-testing](backlog/quality-testing.md)

### M8 · Öffentlichkeit & Community (Vision)
Die spielerseitigen Vision-Funktionen.
- Öffentliche Heldenansicht ohne Realname · 6-stelliger Helden-Code + Suche
- Abenteuerhistorie je Held · Gruppenverwaltung (Trupps)
→ Backlog: [public-access](backlog/public-access.md), [groups](backlog/groups.md)

---

## 5. Priorisierung (Kurzfassung)

1. **M1** zuerst – das Bestehende live bringen (höchster Nutzen, geringes Risiko).
2. **M2 + M3** parallel als nächste große Wertschöpfung (Charakterfortschritt
   + Event-Abwicklung sind der eigentliche Vereinsalltag).
3. **M4/M5** begleitend, sobald Events real laufen.
4. **M6/M7** als Ausbau, wenn der Kernbetrieb stabil ist.

## 6. Backlog-Übersicht

| Datei | Bereich | Präfix |
|---|---|---|
| [auth-profile](backlog/auth-profile.md) | Auth, Profil, Passwörter | AUTH |
| [roles-permissions](backlog/roles-permissions.md) | Rollen & Rechte | ROLE |
| [players](backlog/players.md) | Spielerverwaltung | PLAY |
| [heroes](backlog/heroes.md) | Helden & Klassen | HERO |
| [skills-ep](backlog/skills-ep.md) | Fertigkeiten & EP | SKILL/EP |
| [adventures](backlog/adventures.md) | Events/Abenteuer | ADV |
| [bookings-visits](backlog/bookings-visits.md) | Buchungen & Teilnahme | BOOK |
| [matrix](backlog/matrix.md) | Matrix-Integration | MTX |
| [public-access](backlog/public-access.md) | Öffentliche Heldenansicht & Code | PUB |
| [groups](backlog/groups.md) | Gruppenverwaltung | GRP |
| [admin-lookups](backlog/admin-lookups.md) | Stammdaten/Verwaltung | ADM |
| [notifications](backlog/notifications.md) | Benachrichtigungen | NOTI |
| [reporting](backlog/reporting.md) | Auswertungen/Exporte | REP |
| [ui-ux](backlog/ui-ux.md) | Oberfläche | UI |
| [data-migration](backlog/data-migration.md) | ETL/Legacy | ETL |
| [infrastructure](backlog/infrastructure.md) | Betrieb/CI/Deploy | INFRA |
| [quality-testing](backlog/quality-testing.md) | Tests/Qualität | QA |
| [anforderungen] (backlog/anforderungen.md) | Anforderungen vom Vorstand | REQ |