# Mobile-Roadmap · LARP Heldenregister

> Synthese aller Mobile-relevanten Review-Runden (laravel-architect,
> ui-ux-reviewer, child-experience-reviewer, mobile-app-architect) zu einer
> priorisierten Roadmap mit Epics, User Stories und Phasen.
> Stand: 2026-06-20.
>
> **Quellen:** [backlog/arch.md](backlog/arch.md) (ARCH-001–007),
> [backlog/ui-ux.md](backlog/ui-ux.md) (UI-29–UI-44),
> [backlog/groups.md](backlog/groups.md) (GRP-04–06),
> [backlog/public-access.md](backlog/public-access.md) (PUB-05),
> [backlog/skills-ep.md](backlog/skills-ep.md) (SKILL-07),
> [begriffe.md](begriffe.md), [vision.md](vision.md), [roadmap.md](roadmap.md).
>
> **Diese Datei ändert keinen Anwendungscode.** Sie bündelt und ordnet
> bestehende Tickets; einzig `backlog/arch.md` wurde um ARCH-006/007 ergänzt.

---

## 1. Kurzfassung (Executive Summary)

Das Heldenregister ist heute ein „Desktop-mit-Mobil-Pflaster": fast alle Details
laufen als gestapelte AJAX-Modals mit horizontalen Tabs, obwohl die Zielgruppe
(Kinder 8–17 und ihre Eltern) primär am Smartphone unterwegs ist. Die bisherigen
Reviews haben die Grundprobleme bereits erkannt und mit UI-08/19/25/26/27/28
viele Pflaster-Fixes erledigt — der eigentliche Mobile-First-Umbau steht aber noch
aus. Die strategische Entscheidung ist getroffen und dokumentiert (Ansatz E:
Responsive Blade + Tailwind/Fomantic + schlankes Vanilla-JS, plus Muster
„Modal → echte Seite"; kein Inertia/Livewire, keine native App, keine Mobile-
Subdomain). Der kritische Pfad ist eine kleine Architektur-Basis (ARCH-001/002/003),
auf der die sichtbaren Mobile-Gewinne (eigene Detail-Seiten UI-38/39, Bottom-Nav
UI-42, Accordion UI-40, Karten-Tabellen UI-29/41, Bottom-Sheet-Formulare UI-44)
ohne Code-Duplizierung umsetzbar werden. Die wichtigsten Eltern-Flows (Anmeldung
mit Kosten- und Datenschutz-Transparenz, UI-31/32/35) und Kind-Flows
(Fertigkeitsbaum auffindbar, EP erklärt, UI-33/34) sind günstige, hochwirksame
Quick Wins. Eine installierbare PWA (ARCH-006) ist der größte gefühlte Sprung am
Ende und wird erst sinnvoll, wenn stabile verlinkbare Seiten existieren. Native
App und vollständige Offline-Datenhaltung bleiben bewusst out-of-scope, bis ein
konkreter Bedarf entsteht (ARCH-007 hält die Tür offen). Erfolg misst sich daran,
dass ein 10-Jähriges den Fertigkeitsbaum allein findet und ein Elternteil eine
Anmeldung am Handy in unter 5 Minuten abschließt.

---

## 2. Architekturentscheidung (Kontext)

| Aspekt | Entscheidung | Quelle |
|--------|--------------|--------|
| Render-Ansatz | **Ansatz E**: Responsive Blade + Tailwind + Fomantic + schlankes Vanilla-JS | ARCH-Review |
| Leitmuster | **Modal → echte Seite** für große, verlinkbare Entitäten | UI-38/39, ARCH-002 |
| Tabs auf Mobil | **Accordion** statt horizontaler Tab-Leiste (< `sm`) | UI-40 |
| Kurzformulare | **Bottom-Sheet** (Anmeldung, EP, Bearbeiten) | UI-44 |
| Navigation | **Bottom-Nav** (5 Punkte + „Mehr") < `sm` | UI-42 |
| Kein | Inertia/Livewire, native App, Mobile-Subdomain | ARCH-004 (ADR) |
| Später (optional) | PWA (ARCH-006), API-First (ARCH-007) | bei Bedarf |

**Warum keine Mobile-Subdomain:** Eine gemeinsame Laravel-Anwendung mit
responsiven Views + dem Dual-Render-Vertrag (ARCH-002) ist langfristig deutlich
wartbarer: ein Controller-/Routing-Layer, kein doppelter View-Baum, keine
SEO-/Canonical-Probleme, keine Geräte-Weiche. Eine `m.`-Subdomain würde
Code-Duplizierung und Pflegeaufwand vervielfachen, ohne Mehrwert für die
Zielgruppe — die Skalierung auf mehrere Vereine ginge über Multi-Tenancy in
*einer* App, nicht über Subdomains pro Gerät.

---

## 3. Epic-Übersicht

| Epic | Titel | Tickets | Phase | Treiber-Review |
|------|-------|---------|-------|----------------|
| **EPIC-ARCH-1** | Modal-System modernisieren (JS-Auslagerung + Dual-Render) | ARCH-001, ARCH-002 | 1 | laravel-architect |
| **EPIC-ARCH-2** | Mobile-Primitive-Bibliothek | ARCH-003 | 1 | laravel-architect |
| **EPIC-ARCH-3** | Strategie dokumentieren & zukunftssichern | ARCH-004, ARCH-006, ARCH-007 | 1 / 3 | laravel-architect / mobile-app-architect |
| **EPIC-MOB-1** | Navigation Mobile-First | UI-42 | 2 | ui-ux + child-experience |
| **EPIC-MOB-2** | Detailseiten statt Modals | UI-38, UI-39 | 2 | ui-ux + mobile-app-architect |
| **EPIC-MOB-3** | Kind- & elternfreundliche Flows | UI-31, UI-32, UI-33, UI-34, UI-35 | 1 / 2 | child-experience |
| **EPIC-MOB-4** | Formulare & Eingaben mobil | UI-44, UI-37, UI-30 | 2 / 3 | ui-ux + child-experience |
| **EPIC-MOB-5** | Tabellen mobil (Karten statt Quer-Scroll) | UI-29, UI-41 | 2 | ui-ux |
| **EPIC-MOB-6** | Tabs → Accordion | UI-40 | 2 | ui-ux + child-experience |
| **EPIC-MOB-7** | Mobiles Dashboard & Profil | UI-43, UI-36 | 2 / 3 | ui-ux + child-experience |
| **EPIC-MOB-8** | PWA & Zukunft (Installierbarkeit, Teilen) | ARCH-006, PUB-05, ARCH-007 | 3 | mobile-app-architect |

> Nicht-Mobile-spezifische, aber mobil profitierende Vision-Tickets (GRP-04/05/06,
> SKILL-07, PUB-01–04) sind in dieser Roadmap **bewusst nachgeordnet** — sie
> sollten die in Phase 1 entstehenden Mobile-Primitive (Karten/Accordion/Seiten)
> direkt nutzen, statt eigenes Mobile-Markup zu erfinden. Siehe Abschnitt 6.

---

## 4. User Stories je Epic

### EPIC-ARCH-1 · Modal-System modernisieren
- Als **Entwickler** möchte ich das Modal-/AJAX-JS aus `app.blade.php` in eine
  gebündelte Datei auslagern, damit jeder Mobile-Umbau nur eine Pflegestelle
  anfasst statt 500 Zeilen Inline-Skript. *(ARCH-001)*
- Als **Entwickler** möchte ich einen einheitlichen Vertrag „dieselbe Ansicht als
  Modal-Partial UND als verlinkbare Vollseite", damit UI-38/39 reine Anwendung
  statt Architektur-Bastelei werden. *(ARCH-002)*
- Als **Spielleitung** möchte ich, dass ein Abenteuer-Link direkt eine echte Seite
  öffnet (kein Redirect-Hack), damit ich Abenteuer per Link weitergeben kann. *(ARCH-002)*

### EPIC-ARCH-2 · Mobile-Primitive-Bibliothek
- Als **Entwickler** möchte ich wiederverwendbare Bausteine (Accordion,
  Tabelle→Karte, Sticky-Footer, Bottom-Sheet), damit UI-40/41/42/44 nicht
  4-fach dupliziertes Markup erzeugen. *(ARCH-003)*
- Als **Spielleitung/Admin** möchte ich konsistentes Verhalten dieser Bausteine
  über alle Module, damit ich die Bedienung nur einmal lernen muss. *(ARCH-003)*
- Als **Nutzer mit Tastatur/Screenreader** möchte ich, dass diese Bausteine
  barrierefrei sind, damit auch ich sie bedienen kann. *(ARCH-003, vgl. UI-11/20)*

### EPIC-ARCH-3 · Strategie dokumentieren & zukunftssichern
- Als **künftiger Entwickler** möchte ich einen ADR zur Mobile-Entscheidung,
  damit die Grundsatzfrage nicht in 12 Monaten erneut diskutiert wird. *(ARCH-004)*
- Als **Produktverantwortlicher** möchte ich klare „Revisit-wenn"-Kriterien,
  damit ich erkenne, wann Livewire/native App/Offline doch sinnvoll werden. *(ARCH-004/006/007)*
- Als **Entwickler** möchte ich eine optionale API-Grundlage vorbereitet wissen,
  damit eine spätere App-/Offline-Option keine Architektur-Sackgasse ist. *(ARCH-007)*

### EPIC-MOB-1 · Navigation Mobile-First
- Als **Kind (8–12)** möchte ich die wichtigsten Bereiche mit einem Daumen-Tap
  unten erreichen, damit ich mich nicht durch ein verstecktes Hamburger-Menü
  suchen muss. *(UI-42)*
- Als **Jugendlicher (13–17)** möchte ich ein app-typisches Bedien-Gefühl mit
  permanent sichtbarer Navigation, damit sich das Portal wie eine echte App
  anfühlt. *(UI-42)*
- Als **Elternteil** möchte ich Profil, Benachrichtigungen und Abmelden ohne
  Suchen finden, damit ich schnell zum Ziel komme. *(UI-42, „Mehr"-Sheet)*

### EPIC-MOB-2 · Detailseiten statt Modals
- Als **Elternteil** möchte ich ein Abenteuer per Link teilen und mit dem
  Browser-Zurück navigieren, damit ich nicht in einem Modal-Stack feststecke. *(UI-38)*
- Als **Spielleitung** möchte ich die Abenteuer-Verwaltung (Check-in,
  Anmeldungen) am Handy/Tablet vor Ort auf einer Vollseite bedienen, damit
  nichts vom Modal-Rahmen abgeschnitten wird. *(UI-39)*
- Als **Kind** möchte ich „eine Sache pro Bildschirm" mit flüssigem Scrollen,
  damit ich nicht im verschachtelten Modal-im-Modal die Orientierung verliere. *(UI-38)*

### EPIC-MOB-3 · Kind- & elternfreundliche Flows
- Als **Elternteil** möchte ich Kosten, Datum und Ort direkt im Anmeldeformular
  sehen, damit ich die Anmeldung sicher (statt unsicher) abschließe. *(UI-31)*
- Als **Elternteil** möchte ich bei jeder Dateneingabe meines Kindes wissen,
  wofür und für wen sie erhoben wird, damit ich der Plattform vertraue. *(UI-32)*
- Als **Kind (8–12)** möchte ich den Fertigkeitsbaum sofort finden und verstehen,
  was EP sind, damit das Spielen Freude macht statt zu verwirren. *(UI-33, UI-34)*
- Als **Elternteil** möchte ich beim Bearbeiten einer Anmeldung dieselben
  Hilfetexte wie beim Anlegen, damit ich den Kontext nicht neu erschließen muss. *(UI-35)*

### EPIC-MOB-4 · Formulare & Eingaben mobil
- Als **Elternteil** möchte ich das Anmeldeformular in einer vollbreiten
  Vom-Rand-Ansicht (Bottom-Sheet) mit Abschicken-Bereich in Daumenreichweite
  ausfüllen, damit ich nicht mit einem gestapelten Desktop-Modal kämpfe. *(UI-44)*
- Als **Kind/Elternteil** möchte ich im mehrstufigen Anmelde-Flow immer sehen,
  für welches Abenteuer ich gerade anmelde, damit ich nicht das falsche buche. *(UI-37)*
- Als **Nutzer mit Tastatur** möchte ich, dass alle Footer-Aktionen einheitliche
  `<button>`-Elemente sind, damit Auslösung und Fokusring überall gleich sind. *(UI-30)*

### EPIC-MOB-5 · Tabellen mobil
- Als **Spielleitung** möchte ich Anmeldungs- und Check-in-Tabellen am Handy ohne
  erzwungenes Quer-Scrollen bedienen, damit ich vor Ort schnell arbeiten kann. *(UI-29)*
- Als **Spielleitung** möchte ich Aktions-Icons mit sichtbarem Label statt nur
  Hover-Tooltip, damit ich sie auf Touch ohne Raten verstehe. *(UI-29)*
- Als **Elternteil/Kind** möchte ich Helden-Historie und EP-Verlauf am Handy als
  Karten lesen, damit ich nicht seitlich scrollen muss. *(UI-41)*

### EPIC-MOB-6 · Tabs → Accordion
- Als **Kind** möchte ich auf dem Handy alle Bereiche eines Helden untereinander
  sehen (Accordion), damit ich nicht horizontal nach versteckten Tabs suche. *(UI-40)*
- Als **Kind mit einem Helden mit mehreren Klassen** möchte ich, dass die
  Bereiche nicht endlos seitlich wachsen, damit ich den Überblick behalte. *(UI-40, vgl. UI-33)*
- Als **Nutzer mit Screenreader** möchte ich Accordion-Sektionen mit Enter/Space
  öffnen und ihren Status erkennen können, damit die Bedienung zugänglich ist. *(UI-40)*

### EPIC-MOB-7 · Mobiles Dashboard & Profil
- Als **Kind** möchte ich beim Öffnen sofort „Nächstes Abenteuer" und meinen
  EP-Stand sehen, damit ich motiviert bleibe statt nur ein zweites Menü zu sehen. *(UI-43)*
- Als **Elternteil** möchte ich auf der Startseite die häufigsten Aktionen
  (Anmelden, Mein Held) als Quick-Actions, damit ich mit wenigen Klicks ans Ziel
  komme. *(UI-43)*
- Als **Elternteil** möchte ich eine themengetreue Profilseite mit klarem
  Datenschutz-Link und Datenübersicht, damit ich der Plattform vertraue. *(UI-36)*

### EPIC-MOB-8 · PWA & Zukunft
- Als **Kind/Jugendlicher** möchte ich das Portal als Icon auf den
  Startbildschirm legen und im Vollbild öffnen, damit es sich wie eine echte App
  anfühlt. *(ARCH-006)*
- Als **Kind** möchte ich meinen Helden per QR-Code/Link teilen, damit Freunde
  ihn ohne Realnamen ansehen können. *(PUB-05, baut auf öffentlicher Heldenseite auf)*
- Als **Produktverantwortlicher** möchte ich eine getestete JSON-Datenkontrakt-
  Grundlage, damit eine spätere native App/Offline-Funktion realisierbar bleibt. *(ARCH-007)*

---

## 5. Phasen-Roadmap

> Aufwand = Schätzungen aus den Tickets (⏱). Abhängigkeiten verweisen auf
> Ticket-IDs. „Breaking" = verändert Routing/Layout; „additiv" = baut auf.

### Phase 1 — Fundament (0–3 Monate)
> Architektur-Basis + günstige, hochwirksame Kind/Eltern-Quick-Wins, die keine
> neue Architektur brauchen.

| Ticket | Titel | Aufwand | Abhängigkeiten | Typ |
|--------|-------|---------|----------------|-----|
| ARCH-001 | Modal-JS nach `heldenregister.js` auslagern | 6h | — | Architektur |
| ARCH-002 | Dual-Render-Vertrag Partial ↔ Vollseite | 6h | — (entkoppelt UI-38/39) | Architektur |
| ARCH-003 | Mobile-Primitive (Accordion/Karte/Footer/Sheet) | 8h | ARCH-001, ARCH-002 | Architektur |
| ARCH-004 | ADR Mobile-Entscheidung dokumentieren | 1h | — | Doku |
| UI-31 | Kosten/Beitrag vor dem Anmelden sichtbar | 2h | — | Eltern-Flow |
| UI-32 | Datenschutz-/Zweckhinweis Minderjährigen-Daten | 3h | — | Eltern-Flow |
| UI-34 | EP & Fachbegriffe im Helden-Detail erklären | 3h | — | Kind-Flow |
| UI-35 | Hilfetexte ins Bearbeiten-Formular übernehmen | 1h | UI-15 (✅) | Eltern-Flow |

**Phase-1-Summe: ~30h.** Ergebnis: tragfähige Architektur-Basis + spürbar
vertrauenswürdigere Anmeldung und verständlichere Helden-Übersicht.

### Phase 2 — Mobile-Core (3–6 Monate)
> Sichtbarer Mobile-Gewinn für die Zielgruppe. Setzt Phase-1-Primitive ein.

| Ticket | Titel | Aufwand | Abhängigkeiten | Typ |
|--------|-------|---------|----------------|-----|
| UI-42 | Bottom-Navigation (5 + „Mehr") | 6h | — (harmoniert mit UI-43) | Breaking |
| UI-38 | Helden- & Abenteuer-Detail als Seite | 8h | ARCH-002, ARCH-003 | Breaking |
| UI-39 | Abenteuer-Verwaltung als Seite | 5h | ARCH-002, UI-38 | Breaking |
| UI-40 | Detail-Tabs als Accordion (< `sm`) | 5h | ARCH-003 | additiv |
| UI-29 | Breite Modal-Tabellen mobil (Anmeldung/Check-in/EP) | 3h | ARCH-003 | additiv |
| UI-41 | Detail-/Admin-Tabellen als Karten | 5h | ARCH-003, UI-29 | additiv |
| UI-33 | Helden-Detail: Tab-Flut reduzieren/gruppieren | 4h | UI-40 | Kind-Flow |
| UI-44 | Kurzformulare als Bottom-Sheet | 4h | UI-38/39, ARCH-003 | additiv |
| UI-37 | Orientierung im Anmelde-Modal-Stack | 2h | teilw. durch UI-44 ersetzt | Eltern-Flow |
| UI-43 | Mobiles Dashboard (Quick-Actions/Status) | 5h | UI-42, UI-22 (✅) | additiv |

**Phase-2-Summe: ~47h.** Ergebnis: echtes Mobile-First-Erlebnis — Bottom-Nav,
verlinkbare Detailseiten, Accordions, Karten-Tabellen, Bottom-Sheet-Formulare.

### Phase 3 — Optimierung (6–12 Monate)
> Feinschliff, PWA-Sprung, Langzeit-Wartbarkeit, Zukunftsoptionen.

| Ticket | Titel | Aufwand | Abhängigkeiten | Typ |
|--------|-------|---------|----------------|-----|
| UI-30 | Footer-Aktionen einheitlich als `<button>` | 1h | UI-38/39 | Politur |
| UI-36 | Profilseite ins Theme + Datenschutz-Link | 3h | — | Vertrauen |
| ARCH-006 | PWA-Fundament (Manifest/SW/Installierbar) | 6h | UI-38, UI-39 | PWA |
| PUB-05 | QR-/Teilen-Funktion für Helden-Code | 3h | PUB-02 | Teilen |
| ARCH-007 | API-/Serialisierungs-Grundlage (optional) | 4h | bei Bedarf | Zukunft |

**Phase-3-Summe: ~17h (ohne optionale Vision-Tickets).** Ergebnis: installierbare
PWA, einheitliche Politur, offene Tür für App/Offline.

---

## 6. Einordnung der Vision-/Feature-Tickets (mobil mitdenken)

Diese Tickets sind **keine Mobile-Tickets**, profitieren aber stark von den
Phase-1-Primitiven und sollten diese verbindlich nutzen statt eigenes
Mobile-Markup zu bauen:

| Ticket | Mobile-Hinweis |
|--------|----------------|
| GRP-04 Gruppen-Detail | Mitglieder-/Helden-Listen als Karten (UI-41-Muster) statt Tabelle. |
| GRP-05 Gruppe in Heldenansicht | Erbt Detailseiten-/Accordion-Muster (UI-38/40). |
| GRP-06 Gruppen-Sammelanmeldung | Mehrfachauswahl als Bottom-Sheet (UI-44-Muster). |
| SKILL-07 Fertigkeitsbaum-Visualisierung | Auf Mobil ggf. Spalten-/Level-Stapelung statt breitem Baum; in Accordion-Sektion (UI-40) integrieren. |
| PUB-01–04 Öffentliche Heldenseite | Von Beginn an als responsive Vollseite (kein Modal); Feldsichtbarkeit mit ARCH-007 abstimmen. |

---

## 7. Risiken & Annahmen

**Annahmen (verifiziert am Code, 2026-06-20):**
- `heldenregister.js` existiert leer als ARCH-001-Zielort. ✅
- `HeroController@show` rendert bereits Dual-Render (Vorbild für ARCH-002). ✅
- `AdventureController@show` macht bei Nicht-AJAX einen Redirect-Hack; es gibt
  keine Abenteuer-Detail-Vollseite (ARCH-002/UI-38 korrekt). ✅
- `AdventureController@manage` liefert nacktes Partial ohne Layout (ARCH-002/UI-39
  korrekt). ✅
- `adventures.manage`, `heroes.show`, `adventures.show` sind echte Routen. ✅

**Risiken:**
- **Reihenfolge-Risiko:** UI-38/39 ohne ARCH-002 würden zu Einzelfall-Bastelei →
  Phase-1-Architektur ist Voraussetzung, nicht optional.
- **Doppelpflege-Risiko:** UI-40/41/42/44 ohne ARCH-003 erzeugen 4-fach
  dupliziertes Markup → Primitive zuerst.
- **Breaking-Changes (Phase 2):** UI-38/39/42 verändern Routing/Layout → manuelle
  Regression aller Detail-/Anmelde-Flows nötig (Modal öffnen, Stack, Skill,
  Foto-Crop, Signature, Datepicker, Toast — vgl. ARCH-001-AK).
- **iOS-Safari-Eigenheiten:** `100vh`/Adressleisten-Kollaps (→ `dvh`, vgl. UI-28),
  Bottom-Sheet-Gesten, „Zum Startbildschirm" — real auf iOS Safari + Android
  Chrome verifizieren.
- **PWA-Caching-Risiko (ARCH-006):** HTML muss Network-First bleiben, sonst
  veraltete Inhalte/CSRF-Token-Probleme.
- **Scope-Creep:** SPA-Wechsel (Inertia/Livewire), native App, Vollständige
  Offline-DB sind ausdrücklich out-of-scope (ARCH-004) — bei Bedarf erst ADR
  „Revisit-wenn" prüfen.

---

## 8. Erfolgskriterien (messbar)

| # | Kriterium | Messung |
|---|-----------|---------|
| E1 | Ein 10-jähriges Kind findet den Fertigkeitsbaum ohne Hilfe | Usability-Test: ≥ 8/10 Kinder finden ihn in < 30 s (nach UI-33/34/40) |
| E2 | Elternteil schließt eine Anmeldung am Handy in < 5 min ab | Moderierter Test auf 360×800; ≥ 90 % Erfolg (nach UI-31/32/44) |
| E3 | Kein horizontales Quer-Scrollen mehr in Hauptansichten | Manuelle Prüfung 320–414 px: 0 Stellen mit erzwungenem X-Scroll (UI-29/40/41) |
| E4 | Hauptbereiche in ≤ 3 Taps erreichbar | Klick-Pfad-Audit über Bottom-Nav (UI-42) |
| E5 | Detail-URLs sind teil-/verlinkbar; Browser-Zurück funktioniert | Direktaufruf `heroes.show`/`adventures.show` zeigt Vollseite (UI-38) |
| E6 | Alle Tap-Ziele ≥ 44×44 px in Mobile-Ansichten | Stichprobe Footer/Nav/Aktionen (UI-26/42) |
| E7 | Mobile-JS in einer gebündelten Pflegestelle | `app.blade.php` ohne fachliches Inline-JS (ARCH-001-AK) |
| E8 | Lighthouse „Installable" grün; PWA auf iOS/Android installierbar | Lighthouse-Report + manueller Geräte-Test (ARCH-006) |
| E9 | Mobile-Bausteine sind WCAG-AA-tastatur-/screenreaderbedienbar | Audit Accordion/Karten/Sheet (ARCH-003, vgl. UI-11/20) |

---

## 9. Top-10-Reihenfolge (auf einen Blick)

1. **ARCH-001** — JS auslagern (entsperrt alles Weitere)
2. **ARCH-002** — Dual-Render-Vertrag (entsperrt UI-38/39)
3. **ARCH-003** — Mobile-Primitive (entsperrt UI-40/41/42/44)
4. **UI-31** — Kosten beim Anmelden (höchster Eltern-Nutzen, 2h)
5. **UI-34 / UI-32** — EP erklären + Datenschutz (Kind-/Eltern-Vertrauen)
6. **UI-42** — Bottom-Navigation (sichtbarster Mobile-Sprung)
7. **UI-38 / UI-39** — Detailseiten statt Modals
8. **UI-40 / UI-33** — Accordion + Tab-Flut reduzieren
9. **UI-29 / UI-41 / UI-44** — Tabellen→Karten + Bottom-Sheet
10. **ARCH-006** — PWA-Fundament (gefühlter App-Sprung am Ende)
