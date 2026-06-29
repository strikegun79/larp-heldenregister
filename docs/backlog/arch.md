# Backlog · Architektur (ARCH)

> Architektur-Backlog des LARP Heldenregisters. Enthält die strukturellen
> Grundlagen-Tickets, die die fachlichen UI-/Feature-Tickets langfristig tragbar
> machen. **Diese Datei dupliziert NICHT** die Mobile-First-UI-Tickets UI-38–UI-44
> (`ui-ux.md`); ARCH-Tickets sind die *technische Basis*, auf der jene sauber
> umsetzbar werden. Lösungshinweise sind Vorschläge — in diesem Review wurde kein
> Anwendungscode geändert.

## Mobile-First-Architektur 2026-06 (🔲)

> Ergebnis des Architektur-Reviews zur Frage „Wie Mobile-First realisieren?".
> **Empfehlung: Ansatz E** (Responsive Blade + Tailwind/Fomantic + kleines
> Vanilla-JS) zusammen mit dem Roadmap-Muster „Modal → echte Seite". Begründung
> und Bewertungsmatrix siehe Executive Summary unten in diesem Dokument.

### ARCH-001 · [P1] Modal-JS aus app.blade.php nach `heldenregister.js` auslagern · ⏱ 6h · ✅
**Kategorie:** Architektur / Wartbarkeit
**Beschreibung:** Das gesamte Modal-/AJAX-/Skill-/Foto-Crop-/Signature-JS (~500 Zeilen)
liegt als drei Inline-`<script>`-Blöcke in `layouts/app.blade.php`. Es ist nicht
modularisiert, nicht über Vite gebündelt, nicht testbar und mischt globale
Funktionen (`loadModalContent`, `loadStackContent`, `openPhotoCropper`,
`initFomanticCalendars`, `showToast`) mit Event-Bindings. Die leere Datei
`resources/js/heldenregister.js` existiert bereits als vorgesehener Zielort.
Jeder Mobile-Umbau (UI-38/40/44) fasst dieses JS an — ohne Modularisierung
multiplizieren sich Risiko und Doppelpflege.
**Nutzen:** Wartbares, versionierbares, perspektivisch testbares Frontend-JS;
Voraussetzung für saubere Erweiterung um Accordion-/Bottom-Sheet-/Seiten-Logik.
**Lösungshinweis (nur Vorschlag):** Inline-JS schrittweise nach
`resources/js/heldenregister.js` verschieben, als Vite-Entrypoint einbinden
(neben `app.js`). Globale Funktionen, die Blade-Partials per `onclick`/Inline-
Skript aufrufen (`openPhotoCropper`, `loadStackContent`, `clearSignaturePad`),
bewusst als `window.*` exportieren, bis die Inline-Aufrufe in den Partials
abgelöst sind. Reihenfolge: erst verschieben (verhaltensgleich), dann aufteilen
in Module (modal, skill, photo, signature, calendar).
**Akzeptanzkriterien:**
- [x] `layouts/app.blade.php` enthält kein fachliches Inline-JS mehr (Flash via `#app-flash` data-Attribut).
- [x] JS liegt in `resources/js/heldenregister.js`, via Vite gebündelt (13,77 kB / 4,03 kB gzip).
- [x] Von Partials genutzte Funktionen auf `window.*` exportiert (kein Funktionsbruch).
- [x] 567 Tests grün (Regression bestätigt).
**Betroffene Bereiche:** `layouts/app.blade.php`, `resources/js/heldenregister.js`,
`vite.config.js`, alle Partials mit Inline-`<script>` (z. B. `heroes/_detail.blade.php`).
**Abhängigkeiten:** Grundlage für ARCH-002/003 und UI-38/40/44 (alle erweitern dieses JS).

### ARCH-002 · [P1] Dual-Render-Vertrag „Partial ↔ Vollseite" vereinheitlichen · ⏱ 6h · ✅
**Kategorie:** Architektur
**Beschreibung:** Die Show-/Manage-Controller behandeln AJAX vs. Direktaufruf
uneinheitlich — das ist der zentrale Architektur-Blocker für UI-38/39:
- `HeroController@show`: liefert bei AJAX `heroes._detail`, sonst `heroes.show`
  (Vollseite existiert bereits). ✅ Vorbild.
- `AdventureController@show`: antwortet nur auf AJAX; bei Direktaufruf
  **Redirect** auf `adventures.index?open=` — es gibt **keine** Abenteuer-Detail-
  Vollseite.
- `AdventureController@manage`: gibt direkt das Partial `adventures._manage`
  zurück — ohne Layout-Wrapper, ohne Vollseiten-Pfad.
Ohne einheitlichen Vertrag wird jeder UI-38/39-Umbau zur Einzelfall-Bastelei.
**Nutzen:** Ein wiederholbares, vorhersehbares Muster für „dieselbe Ansicht als
Modal-Partial UND als verlinkbare Vollseite"; macht UI-38/39 zu reiner
Anwendung statt Architekturarbeit.
**Lösungshinweis (nur Vorschlag):** Konvention festlegen: Show/Manage-Methoden
geben bei `$request->ajax()` das `_detail`/`_manage`-Partial zurück, sonst das
Partial gewrappt in `x-app-layout` (eigene `heroes/show`, `adventures/show`,
`adventures/manage`-Vollseiten-Blades, die nur `@include` des Partials + Header/
Footer-Mapping machen). `data-modal-title` → Seiten-Header, `data-modal-actions`
→ Sticky-Footer. Optional ein kleines Trait/Helper `respondWithPartialOrPage()`.
**Akzeptanzkriterien:**
- [x] Einheitliches Muster: AJAX → `*._detail`/`*._manage`, Direktaufruf → `*.show`/`*.manage`.
- [x] `AdventureController@show` liefert echte Vollseite (`adventures.show`) statt Redirect.
- [x] `AdventureController@manage` liefert Vollseite (`adventures.manage`) bei Direktaufruf.
- [x] `HeroController@show` bleibt kompatibel (Referenzimplementierung unverändert).
- [x] Keine Duplizierung: Vollseiten-Blades include-n nur das Partial.
- [x] 567 Tests grün.
**Betroffene Bereiche:** `HeroController@show`, `AdventureController@show`,
`AdventureController@manage`, `routes/web.php`, `heroes/show.blade.php`,
neue `adventures/show.blade.php` + `adventures/manage.blade.php`-Wrapper.
**Abhängigkeiten:** Direkte Voraussetzung für UI-38 und UI-39.

### ARCH-003 · [P2] Wiederverwendbare Layout-Primitive für Mobile (Accordion, Bottom-Sheet, Sticky-Footer, Tabelle→Karte) · ⏱ 8h · ✅
**Kategorie:** Architektur / Wartbarkeit / Mobile
**Beschreibung:** UI-40 (Accordion), UI-41 (Tabelle→Karte), UI-42 (Bottom-Nav),
UI-44 (Bottom-Sheet) lösen jeweils dasselbe Grundproblem an verschiedenen Stellen.
Werden sie einzeln pro View gebaut, entsteht 4-fach dupliziertes Markup/CSS/JS
(genau das Copy-Paste-Muster, das das Projekt langfristig bremst). Sinnvoller ist
ein kleines Set wiederverwendbarer Bausteine, das die UI-Tickets dann nur noch
anwenden.
**Nutzen:** UI-40/41/42/44 werden zu „Komponente einsetzen" statt „Mechanik neu
erfinden"; konsistentes Verhalten, eine Pflegestelle, testbar.
**Lösungshinweis (nur Vorschlag):**
- `<x-mobile.accordion>` / `<x-mobile.accordion-section>`: ab `sm` Tabs, darunter
  `<details>`/Fomantic-Accordion (UI-40). Muss mit Tab-Reinit im Modal-Lader
  koexistieren.
- `<x-mobile.cards-or-table>` bzw. `<x-data-table>`: Label/Wert-Karten unter `sm`,
  Tabelle ab `sm` (UI-19-Muster verallgemeinert → UI-41).
- Sticky-Aktions-Footer-Komponente (`data-modal-actions` → unten fixierte Leiste
  auf Vollseiten, UI-38/39).
- Bottom-Sheet-Variante des Modals rein per CSS (`@media (max-width: …)` +
  Transform) statt neuer JS-Lib (UI-44).
Reines Tailwind + Fomantic + minimal Vanilla-JS — kein neues Framework.
**Akzeptanzkriterien:**
- [x] `<x-mobile.accordion-section>` (`<details>`/`<summary>`, native ARIA).
- [x] `<x-mobile.cards-or-table>` (CSS `data-label`-Karten unter sm, Tabelle ab sm).
- [x] `<x-mobile.sticky-footer>` (CSS `position:fixed` auf Mobile, inline auf Desktop).
- [x] Pilots: `adventures/_detail.blade.php` (Accordion), `adventures/manage_index.blade.php` (Tabelle→Karten), `adventures/manage.blade.php` (Sticky-Footer).
- [x] Keine neuen JS-Frameworks, keine neuen Abhängigkeiten. CSS in `heldenregister.css`.
- [x] 567 Tests grün.
**Betroffene Bereiche:** `resources/views/components/mobile/*`,
`public/css/heldenregister.css`, `resources/js/heldenregister.js`.
**Abhängigkeiten:** Liefert die Bausteine für UI-40/41/42/44; baut auf ARCH-001/002.

### ARCH-004 · [P3] Bewusste Entscheidung gegen Inertia/Livewire dokumentieren (ADR) · ⏱ 1h · ✅
**Kategorie:** Architektur / Dokumentation
**Beschreibung:** Damit die Mobile-First-Entscheidung (Ansatz E statt Stack-Wechsel)
nicht in 12 Monaten erneut diskutiert wird, sollte sie als kurzer Architecture
Decision Record festgehalten werden — inkl. der Bedingungen, unter denen Livewire
später doch sinnvoll würde (z. B. wenn Echtzeit-Check-in oder komplexe reaktive
Formulare gefragt sind).
**Nutzen:** Verhindert wiederholte Grundsatzdiskussionen; macht die Entscheidung
und ihre Auslöser für künftige Entwickler nachvollziehbar.
**Akzeptanzkriterien:**
- [x] Kurzer ADR mit Kontext, Optionen A–E, Entscheidung (E + Modal→Seite) und
      „Revisit-wenn"-Kriterien.
- [x] Verweis aus diesem Backlog auf den ADR.
**Betroffene Bereiche:** `docs/adr/` (neuer ADR).
**Abhängigkeiten:** Keine.

> Umgesetzt: [`docs/adr/0001-frontend-architektur-mobile-first.md`](../adr/0001-frontend-architektur-mobile-first.md) —
> Kontext, Optionen A–E mit Bewertungsmatrix, Begründung für Ansatz E,
> bereits umgesetzte ARCH-001–003, Revisit-Kriterien (Echtzeit-Checkin,
> reaktive Formulare, native App, Offline, Teamwachstum).

### ARCH-005 · [P3] Stack-Diskrepanz Laravel-Version klären (Doku vs. composer.json) · ⏱ 1h · ✅
**Kategorie:** Architektur / Technische Schuld
**Beschreibung:** `CLAUDE.md`/Projektkontext nennen Laravel 12 / PHP 8.3+;
`composer.json` deklariert jedoch `"php": "^8.1"` und
`"laravel/framework": "^10.10"`. Diese Diskrepanz führt zu falschen Annahmen bei
versionsabhängigen Entscheidungen (z. B. verfügbare Framework-Features, Livewire-/
Inertia-Versionswahl).
**Nutzen:** Eindeutige, vertrauenswürdige Stack-Information als Grundlage aller
weiteren Architekturentscheidungen.
**Akzeptanzkriterien:**
- [x] Tatsächliche Version verifiziert: Laravel 12.62.0 / PHP 8.3.31.
- [x] `composer.json` (`^12.0`, `^8.2`) und `CLAUDE.md` (`Laravel 12`, `PHP 8.3+`) stimmen überein.
**Betroffene Bereiche:** `composer.json`, `CLAUDE.md`.
**Abhängigkeiten:** Keine.

### ARCH-006 · [P3] PWA-Fundament: Manifest, Service-Worker, Installierbarkeit · ⏱ 6h · 🔲
**Kategorie:** Architektur / Mobile / PWA
**Beschreibung:** Nach Abschluss der Mobile-First-Umstellung (UI-38–UI-44) ist das
Portal eine echte „eine-Sache-pro-Seite"-Web-App mit verlinkbaren URLs. Damit ist
die technische Voraussetzung für eine installierbare Progressive Web App gegeben —
heute fehlen jedoch Web-App-Manifest, Service-Worker und ein App-Icon-Satz
vollständig. Eine PWA bringt für die mobile Zielgruppe den größten gefühlten Sprung
(„Icon auf dem Startbildschirm", App-typisches Vollbild, schnelleres Wiederöffnen)
**ohne** den Aufwand und die Store-Pflege einer nativen App. Offline-Fähigkeit
wird hier bewusst auf einen schlanken App-Shell-Cache begrenzt; echte
Offline-Datenszenarien (Charakterbogen, Teilnehmerliste vor Ort) sind ein eigenes,
späteres Ticket.
**Nutzen:** Installierbares, app-typisches Erlebnis für Kinder/Jugendliche/Eltern;
schnelleres Wiederöffnen; Grundlage für spätere Push-Benachrichtigungen.
**Lösungshinweis (nur Vorschlag):** `manifest.webmanifest` (Name, Theme-/
Background-Farbe Waldritter/Pergament, `display: standalone`, Icon-Satz
192–512 px), via Vite/Blade einbinden. Schlanker Service-Worker (z. B. Workbox
oder handgeschrieben) für App-Shell-/Asset-Caching (`vendor.css`, `vendor.js`,
Logo, Fonts) mit Network-First für HTML. Kein neues Framework. Erst nach UI-38/39
sinnvoll, weil dann stabile, verlinkbare Seiten-URLs existieren.
**Akzeptanzkriterien:**
- [ ] Gültiges Web-App-Manifest mit Icon-Satz; Lighthouse „Installable" grün.
- [ ] Service-Worker cached App-Shell/Assets; HTML bleibt Network-First (keine
      veralteten Inhalte/Token-Probleme).
- [ ] Auf Android Chrome und iOS Safari als „Zum Startbildschirm hinzufügen" prüfbar.
- [ ] Kein zusätzliches JS-Framework; Build dokumentiert.
**Betroffene Bereiche:** `public/` (Manifest, Icons), `resources/js/`, `vite.config.js`,
`layouts/app.blade.php` (Manifest-/Theme-Color-Tags).
**Abhängigkeiten:** Sinnvoll nach UI-38/UI-39 (stabile Seiten-URLs); Voraussetzung
für eventuelle spätere Push-Benachrichtigungen und Offline-Datenszenarien.

### ARCH-007 · [P3] API-/Serialisierungs-Grundlage für spätere App-/Offline-Optionen · ⏱ 4h · 🔲
**Kategorie:** Architektur / Skalierbarkeit
**Beschreibung:** Die Anwendung rendert ausschließlich serverseitig (Blade-Partials
+ AJAX-HTML). Das ist für den Mobile-First-Web-Ansatz (Ansatz E, vgl. ARCH-004)
genau richtig und soll **nicht** durch eine SPA ersetzt werden. Sollte später eine
native App, ein echtes Offline-Datenszenario (Charakterbogen/Teilnehmerliste) oder
eine Drittintegration entstehen, fehlt jedoch eine saubere JSON-Repräsentation der
Kern-Entitäten (Held, Abenteuer, Buchung). Eine kleine, bewusst begrenzte
API-Resource-Schicht hält diese Tür offen, ohne den aktuellen Render-Ansatz zu
verändern. **Dieses Ticket ist explizit optional/vorbereitend** — nur umsetzen,
wenn ein konkreter App-/Offline-Bedarf entsteht (siehe „Revisit-wenn" in ARCH-004).
**Nutzen:** Klare, getestete Datenkontrakte als Grundlage für native App, Offline
oder Integrationen — ohne Vorabinvestition in ein Frontend-Framework.
**Lösungshinweis (nur Vorschlag):** `App\Http\Resources\*` (Hero/Adventure/Booking)
mit bewusster Feldauswahl (öffentliche vs. interne Felder, vgl. PUB-02).
Auth via Sanctum-Token nur bei Bedarf. Versionierter Prefix (`/api/v1`).
**Akzeptanzkriterien:**
- [ ] API-Resources für Held/Abenteuer/Buchung mit dokumentierter Feldauswahl.
- [ ] Read-only-Endpunkte hinter Auth; öffentliche Felder = PUB-02-Kontrakt.
- [ ] Tests (sichtbare vs. verborgene Felder, Auth).
**Betroffene Bereiche:** `app/Http/Resources/*`, `routes/api.php`, `app/Models/*`.
**Abhängigkeiten:** Konzeptuell mit PUB-02 (Feldsichtbarkeit) abzustimmen; sonst
unabhängig. Nur bei konkretem App-/Offline-Bedarf umsetzen.
