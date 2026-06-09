# Backlog – LARP Heldenregister

Feingranulares Aufgaben-Backlog. Jede Aufgabe ist auf **2–4 Stunden**
Arbeitszeit zugeschnitten und unabhängig umsetzbar. Übergeordneter Kontext
und Meilensteine: siehe [../roadmap.md](../roadmap.md).

## Konventionen

Jede Aufgabe hat das Format:

```
### PREFIX-NN · Titel · ⏱ Xh · Status
**Beschreibung:** Was und warum.
**Akzeptanzkriterien:**
- [ ] prüfbares Kriterium
**Abhängig von:** PREFIX-MM (optional)
```

- **Status:** ✅ erledigt · 🟡 teilweise · 🔲 offen
- **Schätzung:** 2 h, 3 h oder 4 h (netto Entwicklungszeit; ohne Review/QA-Overhead).
- Aufgaben > 4 h sind in Teilaufgaben zerlegt.
- IDs sind stabil; erledigte Aufgaben bleiben als Inventar erhalten.

## Dateien

| Datei | Bereich | Präfix |
|---|---|---|
| [auth-profile.md](auth-profile.md) | Auth, Profil, Passwörter | AUTH |
| [roles-permissions.md](roles-permissions.md) | Rollen & Rechte | ROLE |
| [players.md](players.md) | Spielerverwaltung | PLAY |
| [heroes.md](heroes.md) | Helden & Klassen | HERO |
| [skills-ep.md](skills-ep.md) | Fertigkeiten & EP | SKILL / EP |
| [adventures.md](adventures.md) | Events/Abenteuer | ADV |
| [bookings-visits.md](bookings-visits.md) | Buchungen & Teilnahme | BOOK |
| [matrix.md](matrix.md) | Matrix-Integration | MTX |
| [public-access.md](public-access.md) | Öffentliche Heldenansicht & Code | PUB |
| [groups.md](groups.md) | Gruppenverwaltung | GRP |
| [admin-lookups.md](admin-lookups.md) | Stammdaten/Verwaltung | ADM |
| [notifications.md](notifications.md) | Benachrichtigungen | NOTI |
| [reporting.md](reporting.md) | Auswertungen/Exporte | REP |
| [ui-ux.md](ui-ux.md) | Oberfläche | UI |
| [data-migration.md](data-migration.md) | ETL/Legacy | ETL |
| [infrastructure.md](infrastructure.md) | Betrieb/CI/Deploy | INFRA |
| [quality-testing.md](quality-testing.md) | Tests/Qualität | QA |

## Fortschritts-Schnellüberblick

- **Fertig** (Inventar): Auth-Basis, Rollen/Rechte, Spieler-CRUD, Helden-CRUD,
  Abenteuer/Buchungen-Basis, Matrix, ETL, UI-Theme/Modals.
- **Nächste Wertschöpfung:** SKILL/EP-Ökonomie, Event-Lebenszyklus (BOOK/ADV),
  Admin-Stammdaten (ADM), Go-Live (INFRA).
