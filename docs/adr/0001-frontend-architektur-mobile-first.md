# ADR-0001 · Frontend-Architektur: Responsive Blade statt Inertia/Livewire

**Status:** Angenommen  
**Datum:** 2026-06-29  
**Kontext:** Mobile-First-Umbau (ARCH-001–ARCH-003, roadmap-mobile.md)

---

## Kontext

Das Heldenregister ist ein Laravel-Portal für Kinder- und Jugend-LARP
(Waldritter Gießen e. V.). Die Nutzer sind Kinder (7–17 J.), Eltern und Teamer,
die primär mit dem Smartphone auf Abenteuer, Heldenprofile und Buchungen zugreifen.

Das bestehende System rendert serverseitig (Blade-Templates). Modals laden Partials
per AJAX (HTML). Die Grundsatzfrage „Wie Mobile-First realisieren?" wurde im
Architektur-Review 2026-06 beantwortet.

---

## Bewertete Optionen

| Option | Beschreibung | Bewertung |
|---|---|---|
| **A — Native App** | iOS + Android (React Native / Flutter) | Stack-Wechsel, Store-Pflege, Kosten; kein Mehrwert für Vereinsbetrieb |
| **B — Inertia.js + Vue/React** | SPA-Schicht über Laravel | Ersetzt Blade vollständig; hohe Lernkurve; Serverside-Rendering verloren; kein Vorteil gegenüber E |
| **C — Livewire** | Reaktive Komponenten, PHP-First | Benötigt Alpine.js oder Pusher für Echtzeit; Debugging-Komplexität steigt; Mehrwert erst bei reaktiven Formularen oder Live-Updates |
| **D — Mobile-Subdomain** | Separates mobiles Frontend | Dupliziert Codebase und Logik; sofort verworfen |
| **E — Responsive Blade + Vanilla-JS** | Tailwind + Fomantic-UI + schlankes Vanilla-JS; Modal-Partials → verlinkbare Vollseiten | Erhält bestehende Architektur; kein Stack-Wechsel; graduelle Verbesserung möglich |

---

## Entscheidung

**Ansatz E** wird umgesetzt: Responsive Blade-Templates, Tailwind/Fomantic-UI,
schlankes Vanilla-JS (gebündelt via Vite). Dazu das Muster **„Modal → echte Seite"**:
Jedes Modal-Partial ist gleichzeitig als verlinkbare Vollseite verfügbar
(AJAX → Partial, Direktaufruf → Partial in App-Layout gewrappt).

### Begründung

1. **Kein Stack-Wechsel nötig.** Die bestehende Blade/AJAX-Architektur funktioniert
   — sie braucht Mobile-Optimierung, keinen Neubau.

2. **Coding-Standards konform.** `docs/coding-standards.md` §16 schreibt vor:
   „JavaScript nur einsetzen, wenn es echten Mehrwert bringt. Keine unnötigen
   neuen Frontend-Frameworks."

3. **Zielgruppe.** Kinder und Eltern brauchen eine schnelle, einfache Web-App —
   keine native App. Eine gut gemachte PWA (ARCH-006) erfüllt denselben Zweck
   günstiger.

4. **Wartbarkeit.** Ein einziges PHP/Blade-Team kann das Portal ohne
   Frontend-Spezialisten (React/Vue) pflegen.

5. **Graduelle Verbesserung.** Einzelne Seiten können schrittweise verbessert werden
   (Accordion, Sticky-Footer, Karten statt Tabellen), ohne andere Bereiche zu
   brechen.

---

## Konsequenzen

### Positiv

- Kein Dependency-Upgrade-Risiko durch ein SPA-Framework
- Serverseitiges Rendering bleibt erhalten (SEO, initiale Ladezeit, DSGVO)
- Bestehende Tests (661+) bleiben valide — kein Teststack-Wechsel
- Mittelalterliches Design bleibt ohne UI-Framework-Konflikt erhaltbar

### Negativ / Akzeptierte Einschränkungen

- Komplexe Echtzeit-Funktionen (Live-Checkin, kollaborative Bearbeitung)
  sind mit Vanilla-JS aufwändig
- Reaktive Formularvalidierung (sofort, ohne Submit) erfordert manuelles JS

### Bereits umgesetzt (ARCH-001–003)

- `resources/js/heldenregister.js` (Vite-gebündelt) statt Inline-JS in `app.blade.php`
- `AdventureController@show/manage` liefern Vollseite bei Direktaufruf
- Blade-Komponenten `<x-mobile.accordion-section>`, `<x-mobile.cards-or-table>`,
  `<x-mobile.sticky-footer>`

---

## Revisit-Kriterien

Diese Entscheidung überdenken, wenn:

| Kriterium | Schwelle | Mögliche Alternative |
|---|---|---|
| Echtzeit-Checkin | Veranstaltungs-Scanstation im LARP-Betrieb | Livewire + Laravel Echo + Pusher |
| Reaktive Formulare | ≥ 3 Formulare mit Live-Validierung / abhängigen Feldern | Livewire (schrittweise, ohne SPA) |
| Native App gefordert | Vereinsbeschluss oder App-Store-Pflicht | React Native + ARCH-007 als API-Basis |
| Offline-Daten | Teilnehmerliste / Charakterbogen ohne Internet | Service-Worker + ARCH-006/007 |
| Team wächst auf ≥ 3 Frontend-Entwickler | Dann lohnt sich ein dedizierten JS-Framework | Inertia + Vue (bewusstes Projekt) |

---

## Verwandte Entscheidungen

- `docs/roadmap-mobile.md` — Mobile-Roadmap und Epic-Planung
- ARCH-006 — PWA-Fundament (Manifest, Service-Worker) als nächster Schritt
- ARCH-007 — API-Grundlage für spätere App/Offline (nur bei konkretem Bedarf)
- `docs/coding-standards.md` §16 — Frontend-Regeln
