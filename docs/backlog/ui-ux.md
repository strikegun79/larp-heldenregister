# Backlog · Oberfläche (UI)

Fomantic UI, Mittelalter-Theme, Modals, Interaktion.

## Inventar (✅)

### UI-01 · Mittelalter-Theme (Pergament, Fonts, Logo, Footer) · ⏱ 4h · ✅
Aus Legacy übernommen.

### UI-02 · Dashboard-Kartenmenü (rollenbasiert) · ⏱ 3h · ✅
Profil/Spieler/Heldenregister/Abenteuer/Verwaltung.

### UI-03 · Fomantic UI + gemeinsames AJAX-Modal · ⏱ 4h · ✅
`[data-modal-url]` lädt Inhalte; vier Entitäten öffnen im Modal.

### UI-04 · AJAX-Submit mit Toast-Rückmeldung · ⏱ 3h · ✅
Modal-Formulare; Erfolg/Validierung/Fachfehler als Toast.

## Offen (🔲)

### UI-05 · Konsistente Fomantic-Formularkomponenten · ⏱ 4h · ✅
**Beschreibung:** Mischung aus Tailwind- und Fomantic-Formularen vereinheitlichen;
Fomantic-Dropdowns/Calendar (wie Legacy) für Auswahl/Datum.
**Akzeptanzkriterien:**
- [x] Wiederverwendbare Blade-Komponente `<x-date-picker>` (Props: name, value,
      type date|datetime, required).
- [x] Datepicker (Fomantic Calendar) mit DE-Format (TT.MM.JJJJ), ISO-Hidden-Input
      ans Backend, `initFomanticCalendars()` in beiden Modal-Ladern + DOMContentLoaded.
- [x] Helden-/_form (born, died), Spieler-/_form (dayofbirth),
      Abenteuer-/_form (start_at, end_at) umgestellt.

### UI-06 · Such-/Filter-/Sortier-Baustein für Listen · ⏱ 4h · ✅
**Beschreibung:** Gemeinsames Muster für Suche/Filter/Sortierung mit
Paginierungs-Erhalt (Query-String).
**Akzeptanzkriterien:**
- [x] Wiederverwendbare Suchleiste + serverseitige Filterung.
- [x] In mind. einer Liste produktiv (Helden oder Spieler). (umgesetzt in PLAY-09)
- [x] Tests.

### UI-07 · Modal-Submit ohne Reload (Teil-Refresh) · ⏱ 4h · ✅
**Beschreibung:** Aktuell `reload` nach Erfolg. Stattdessen Liste/Modal gezielt
per AJAX aktualisieren.
**Akzeptanzkriterien:**
- [x] Nach Erfolg wird der betroffene Listeneintrag/Modal-Inhalt neu geladen.
- [x] Kein voller Seiten-Reload mehr; Toast bleibt.

### UI-08 · Responsives Verhalten & Mobile-Feinschliff · ⏱ 3h · ✅
**Beschreibung:** Tabellen/Modals/Karten auf Mobil prüfen und anpassen.
**Akzeptanzkriterien:**
- [x] Tabellen scrollbar/stacked auf kleinen Screens.
- [x] Modals nutzbar auf Mobil (Scroll/Fullscreen).

### UI-09 · Flash-Messages global als Toast · ⏱ 2h · ✅
**Beschreibung:** Session-`status`/`error` (Vollseiten) ebenfalls als Toast
darstellen (einheitliches Feedback).
**Akzeptanzkriterien:**
- [x] Beim Laden vorhandene Flash-Messages als Toast ausgeben.
- [x] Keine doppelte Anzeige (Box + Toast).

### UI-10 · Fomantic-Assets lokal bündeln (statt CDN) · ⏱ 3h · ✅
**Beschreibung:** Fomantic/jQuery aktuell per CDN; für Offline/Prod lokal via Vite.
**Akzeptanzkriterien:**
- [x] Fomantic + jQuery über npm/Vite gebaut und eingebunden.
- [x] Keine externen CDN-Abhängigkeiten zur Laufzeit.
- [x] Build dokumentiert.

> Umgesetzt: `jquery@3.7.1`, `fomantic-ui@2.9.3`, `cropperjs@1.6.2` per npm;
> `resources/js/vendor.js` + `resources/css/vendor.css` als eigene Vite-Entrypoints.
> `@rollup/plugin-inject` injiziert jQuery als Global in alle JS-Dateien (Fomantic-IIFE-Kompatibilität).
> CDN-Tags in `app.blade.php` durch `@vite()` ersetzt.

### UI-11 · Accessibility-Grundlagen · ⏱ 3h · ✅
**Beschreibung:** Fokus-Management in Modals, Labels, Kontraste, ARIA.
**Akzeptanzkriterien:**
- [x] Modale fangen Fokus, schließen mit ESC.
- [x] Formularfelder mit Labels; ausreichende Kontraste.

### UI-12 · Accessibility-UI Fomantic · ⏱ 3h · ✅
**Beschreibung:** Verwende ui Modale für das öffnen der Details
**Akzeptanzkriterien:**
- [x] Modale Fenster einsetzen für Helden-Detail, Spieler-Detail, Abenteuer-buchen, Abenteuer-Editieren, Nutzer editieren 
- [x] Modale Fenster mit Header und Footer und internes Scrollen, falls der inhalt länger ist.

> Umgesetzt: ein persistentes `ui modal` (#app-modal) mit `header` /
> `scrolling content` / `actions`-Footer. AJAX-Partials liefern per Konvention
> `[data-modal-title]` (Header) und `[data-modal-actions]` (Footer); ein
> „Schließen"-Button wird immer ergänzt. Abenteuer-Editieren öffnet jetzt
> ebenfalls als Modal (`AdventureController@edit` AJAX -> `_edit_modal`,
> `@update` liefert JSON). Booking/EP nutzen `refresh_modal` (Modal-Teil-Refresh).

### UI-13 · Accessibility-Modale Fenster für Editieren · ⏱ 3h · ✅
**Beschreibung:** Verwende ui Modale auch beim editieren von Spieler und Helden
**Akzeptanzkriterien:**
- [x] Modale Fenster einsetzen für Helden-Edit und Spieler-Edit

> Umgesetzt analog UI-12: `HeroController@edit` / `PlayerController@edit`
> liefern bei AJAX ein `_edit_modal`-Partial (Titel + Formular), `@update`
> antwortet mit JSON (`reload`). „Bearbeiten"-Links in Detail-Modal und
> Listen tragen `data-modal-url` → Bearbeiten öffnet im Modal. Vollseiten
> bleiben als Fallback. Tests: Hero-/Player-Edit-Modal + AJAX-Update.

### UI-14 · Accessibility-Rollen anzeigen · ⏱ 3h · ✅
**Beschreibung:** Unter Profil soll sichtbar sein, welche Rollen der aktuelle Nutzer hat.
**Akzeptanzkriterien:**
- [x] Anzeigen der aktivierten Rollen unter dem User-Profil
- [x] alternativ auch in der Headleiste?

## Neu identifiziert (UX-Review 2026-06, 🔲)

> Ergebnisse eines vollständigen UX/UI-Reviews aller Views aus Sicht der
> Zielgruppe (Kinder 8–16 Jahre + Eltern/Betreuer). Reihenfolge grob nach
> Wichtigkeit für die Zielgruppe. Bereits offene Tickets UI-05/07/08/10/11
> werden bewusst NICHT dupliziert; die folgenden Tickets sind eigenständig.

### UI-15 · Anmelde-Formular für Eltern verständlich & sicher machen · ⏱ 5h · ✅
**Beschreibung:** Das Abenteuer-Anmeldeformular (`bookings/_create.blade.php`,
`_create_guest.blade.php`) ist der wichtigste Eltern-Flow, enthält aber
sensible Gesundheitsdaten ohne jede Erläuterung und kritische Stolperfallen.
**Akzeptanzkriterien:**
- [x] AGB-Checkbox zeigt Teilnahmebedingungen als aufklappbaren `<details>`-Block.
- [x] Jedes sensible Feld (Allergien, Medikamente, Erreichbarkeit) hat einen
      kurzen Hilfetext (Zweck + optional, Notfall-Bezug erklärt).
- [x] Pflichtfelder klar gekennzeichnet (Legende „* Pflichtfelder" + `required`-Klasse).
- [x] Checkbox-Gruppe erhält `<fieldset>`-Überschrift; NSC mit `data-tooltip`-Erklärung.
- [ ] Fehlende Pflichtangaben feldnah hervorgehoben (erfordert JS-Umbau, offen in UI-07).

> Umgesetzt: `_create.blade.php` + `_create_guest.blade.php` überarbeitet.
> Pflichtfeld-Legende am Formularbeginn. `<fieldset>` mit `<legend>` für Checkboxen.
> NSC-Checkbox mit `data-tooltip`. `<small>`-Hilfetexte bei Allergien, Medikamente,
> Erreichbarkeit, Kontaktrufnummer. AGB ersetzt durch `<details>`-Block mit den fünf
> Kernpunkten der Teilnahmebedingungen. Wartelisten-Hinweis als `ui warning message`.
**Betroffene Seiten/Routen:** `bookings/_create.blade.php`, `bookings/_create_guest.blade.php`

### UI-16 · Kindgerechte, einheitliche Begriffe & Sprache · ⏱ 4h · ✅
**Beschreibung:** Über alle Module verteilt standen für Kinder schwer
verständliche oder uneinheitliche Begriffe.
**Akzeptanzkriterien:**
- [x] Einheitlicher Begriff „Abenteuer" projektweit durchgezogen (Tab, Dashboard,
      Index-Button; Admin-interne Lookups behalten „Event" als Kürzel).
- [x] Datumsfelder „Erste Erblickung"/„Verschollen" mit erklärendem Hilfetext versehen.
- [x] NSC an jeder Verwendung mit `data-tooltip` erklärt (`_create`, `_edit`).
- [x] „GAST-Anmeldung" → „Gast anmelden"; „Eventleiter" → „Veranstaltungsleiter".
- [x] `docs/begriffe.md` als Begriffs- und Stil-Referenz angelegt.

> Umgesetzt: `_detail.blade.php` (Tab, Button, Feldlabel), `dashboard.blade.php`,
> `adventures/index.blade.php`, `adventures/_form.blade.php`, `heroes/_form.blade.php`,
> `bookings/_edit.blade.php`, `profile/edit.blade.php`. Neue Datei `docs/begriffe.md`.
> Tests angepasst (`CharacterSheetAndDashboardTest`, `EventManageListTest`).

### UI-17 · Bestätigungen & Eingabe-Dialoge statt nativer confirm()/alert() · ⏱ 4h · ✅
**Beschreibung:** Sicherheitskritische Aktionen (Löschen von Ort/Klasse/Foto,
Event absagen, EP-Klassen-Abzug, Teamer einladen) nutzen native
`confirm()`-Dialoge. Diese sind nicht zum Mittelalter-Theme passend, auf Mobil
unscheinbar, nicht stylebar, oft englisch beschriftet (OK/Cancel je Browser)
und für Kinder leicht „weggeklickt". Das System hat bereits ein gemeinsames
Fomantic-Modal – Bestätigungen sollten konsistent darüber laufen.
**Akzeptanzkriterien:**
- [x] Wiederverwendbares Bestätigungs-Modal (Titel, Text, Bestätigen/Abbrechen)
      löst native `confirm()` ab.
- [x] Destruktive Aktionen kennzeichnen den Bestätigen-Button rot/„negative".
- [x] Alle bisherigen `onsubmit="return confirm(...)"`-Stellen umgestellt (31 Vorkommen).
- [x] Buttons deutschsprachig: „Abbrechen" / „Bestätigen".

> Umgesetzt: `#confirm-modal` in `app.blade.php` ergänzt. Capture-Phase-Submit-Handler
> interceptiert Formulare mit `data-confirm="..."` vor dem AJAX-Bubble-Handler.
> Sonderfall `heroes/_detail.blade.php` (EP-Kosten-Bestätigung nur wenn kostenpflichtig):
> `data-confirm-unless-id` / `data-confirm-unless-val` Attributmuster.
> Alle 31 `onsubmit="return confirm(...)"` in 26 Views auf `data-confirm` umgestellt.
> Auch UI-16-Terminologie: „Event absagen" → „Abenteuer absagen" in `_manage.blade.php`.
**Betroffene Seiten/Routen:** `admin/locations/index.blade.php`, `heroes/_detail.blade.php`,
`players/_detail.blade.php`, `adventures/_manage.blade.php`, weitere Admin-Listen

### UI-18 · Einheitliches Theme statt Tailwind/Indigo-Fragmente & toter Dark-Mode · ⏱ 4h · ✅
**Beschreibung:** Viele Views mischen das Mittelalter-/Pergament-Theme mit
generischen Tailwind-Resten: Indigo-Fokusringe und blaue Links
(`focus:ring-indigo-500`, `text-indigo-700`) statt der Waldritter-/Stein-Farben;
zahlreiche `dark:`-Klassen (z. B. `heroes/index`, `adventures/index`), obwohl es
keinen Dark-Mode gibt – das ist toter Code und kann auf Geräten mit
Systemdunkelmodus zu unleserlichen Kontrasten führen. Das wirkt uneinheitlich
und „technisch" statt einladend für die junge Zielgruppe.
**Akzeptanzkriterien:**
- [x] Indigo-/Blau-Akzente durch Theme-Farben (Waldritter/Stein/Amber) ersetzt.
- [x] Ungenutzte `dark:`-Klassen aus 9 App-Views entfernt (kein Dark-Mode).
- [x] Fokus-/Hover-Farben projektweit einheitlich (`amber-600`, `waldritter`).

> Umgesetzt: 39 `dark:`-Klassen aus 9 Views entfernt (`heroes/`, `adventures/`).
> 16 `text-indigo-700`-Links → `text-waldritter` in allen Admin-Tabellen,
> `adventures/manage_index`, `players/_detail`, `heroes/_detail` (Skill-Trigger).
> `focus:ring-indigo-500` → `focus:ring-amber-600` und `text-indigo-600` →
> `text-amber-600` (Checkboxen) in `_form.blade.php`, `admin/matrix/edit`,
> `auth/login`, Blade-Komponenten (`text-input`, `primary-button`, `secondary-button`).
**Betroffene Seiten/Routen:** `heroes/index.blade.php`, `heroes/_form.blade.php`,
`adventures/index.blade.php`, `players/_form.blade.php`, `components/text-input.blade.php`

### UI-19 · Listen-/Tabellen auf Mobil als Karten (Helden, Abenteuer, Admin) · ⏱ 4h · ✅
**Beschreibung:** Ergänzend zu UI-08: Die Haupt-Tabellen (Heldenregister,
Abenteuer, Admin-Lookups) sind 5–6-spaltige `min-w-full`-Tabellen ohne
horizontalen Scroll-Container. Auf Smartphones laufen sie aus dem Viewport
oder erzwingen seitliches Scrollen; Spaltenüberschriften gehen verloren.
Für die mobile-first Zielgruppe (Kinder/Eltern am Handy) sollten Zeilen auf
kleinen Screens als gestapelte Karten dargestellt werden.
**Akzeptanzkriterien:**
- [x] Helden- und Abenteuerliste auf < sm als Kartenliste (Label + Wert) statt Tabelle.
- [x] Mindestens horizontaler Scroll-Wrapper für Admin-Tabellen (15 Dateien).
- [x] Tippziel pro Zeile bleibt erhalten (Modal öffnen via `data-modal-url` auf `<div>`).

> Umgesetzt: `heroes/index.blade.php` und `adventures/index.blade.php` erhalten
> doppeltes Layout: `sm:hidden` Kartenliste (Name, Spieler/Datum, Tags) und
> `hidden sm:block overflow-x-auto` Tabelle. Admin- und Verwaltungslisten
> (15 Dateien) bekommen `overflow-x-auto`-Wrapper innerhalb des `overflow-hidden`-
> Rahmens – horizontales Scrollen ohne Designbruch.

### UI-20 · Tabellen-Zeilen tastatur- & screenreader-bedienbar · ⏱ 3h · ✅
**Beschreibung:** Zeilen mit `data-modal-url` öffnen Details nur per Maus-/Touch-
Klick auf `<tr>` (`cursor:pointer`). Sie sind nicht fokussierbar, nicht per
Tastatur (Enter) auslösbar und für Screenreader nicht als interaktiv erkennbar.
Das betrifft Helden, Abenteuer und die Spieler-/Helden-Tabellen in Detail-Modals.
Ergänzt UI-11 um den konkreten Listen-Fall.
**Akzeptanzkriterien:**
- [x] Interaktive Zeilen sind per Tab erreichbar und mit Enter/Space auslösbar.
- [x] `role="button"` + `aria-label` auf allen interaktiven `<tr>`/`<div>`-Elementen.
- [x] Sichtbarer Fokuszustand: `focus-visible:outline-2 outline-amber-600`.

> Umgesetzt: Globaler `keydown`-Handler in `app.blade.php` (Enter/Space → `trigger.click()`).
> `tabindex="0"`, `role="button"`, `aria-label` und `focus-visible`-Outline auf 5 Elementen
> in `heroes/index.blade.php` (Karte + `<tr>`), `adventures/index.blade.php` (Karte + `<tr>`)
> und `players/_detail.blade.php` (`<tr data-modal-stack>`).
**Betroffene Seiten/Routen:** `heroes/index.blade.php`, `adventures/index.blade.php`,
`players/_detail.blade.php`

### UI-21 · Bilder optimieren (Dashboard-/Admin-Kacheln, Avatare) · ⏱ 3h · ✅
**Beschreibung:** Dashboard- und Admin-Übersicht laden je 5–16 großformatige
JPGs (h-44 Kacheln) ohne `loading="lazy"`, ohne `width/height` und mit leerem
`alt=""`. Auf langsamen Mobilverbindungen verzögert das den ersten Eindruck
spürbar und verursacht Layout-Verschiebungen. Avatare/Helden-Fotos werden
ebenfalls in voller Größe in Listen eingebunden.
**Akzeptanzkriterien:**
- [x] `loading="lazy"` + feste Maße für Kachel-/Listenbilder.
- [x] Sinnvolle `alt`-Texte (oder bewusst dekorativ + `aria-hidden`).
- [ ] Bilder in passender Auflösung ausgeliefert (keine 4-MB-Originale in Kacheln).
  → **out-of-scope:** erfordert Server-Side Image Resize (z. B. Intervention Image /
  Spatie Media Library); als eigenes Ticket vertagt.
**Betroffene Seiten/Routen:** `dashboard.blade.php`, `admin/index.blade.php`,
`players/index.blade.php`
**Implementierung:** Alle Kachel-`<img>` in `dashboard.blade.php` und
`admin/index.blade.php` erhalten `loading="lazy" width="400" height="176"
aria-hidden="true"`. Spieler-Avatare und „Neuer Spieler"-Bild in
`players/index.blade.php` erhalten `loading="lazy" width="150" height="150"`.

### UI-22 · Hilfreiche Leerzustände & Erst-Nutzer-Führung · ⏱ 3h · ✅
**Beschreibung:** Leere Listen zeigen nur knappe Sätze („Noch keine Helden
erfasst.", „Noch keine Orte."). Für neue Eltern fehlt der nächste Schritt:
Wie lege ich einen Spieler an, warum brauche ich erst einen Spieler vor einem
Helden (dieser Zusammenhang steht nur im Spieler-Intro), wie melde ich zu einem
Abenteuer an? Ein klarer Call-to-Action im Leerzustand senkt die Einstiegshürde.
**Akzeptanzkriterien:**
- [x] Leere Helden-/Abenteuer-/Spielerlisten enthalten einen erklärenden
      Hinweis + primären Button zum nächsten sinnvollen Schritt.
- [x] Abhängigkeit „erst Spieler, dann Held/Anmeldung" wird dort erklärt, wo sie auftritt.
**Betroffene Seiten/Routen:** `heroes/index.blade.php`, `adventures/index.blade.php`,
`players/index.blade.php`
**Implementierung:** Leere Heldenliste erklärt Spieler-Abhängigkeit mit Link +
„Neuen Helden anlegen"-Button (nur mit Berechtigung). Leere Abenteuerliste
zeigt Platzhaltertext + „Zur Verwaltung"-Button (nur Admins/SL). Spielerliste
zeigt bei leerem Ergebnis einen „Erste Schritte"-Hinweis unterhalb des Grids.

### UI-23 · Fertigkeits-Modal verständlicher gestalten · ⏱ 2h · ✅
**Beschreibung:** Das Skill-Bestätigungs-Modal (`layouts/app.blade.php`) ist für
Kinder das zentrale „Belohnungs"-Element, aber: Button-Texte „Fertigkeit
errungen" (Lernen) und „Fertigkeit aberkennen" sind missverständlich; der
Warnhinweis „Nicht genug EP" erscheint als roter Text mit potenziell zu geringem
Kontrast; bei zu wenig EP wird der Button nur „disabled" gesetzt, ohne klare
Erklärung, wie man EP bekommt. Eine positivere, klarere Gestaltung erhöht
Motivation und Verständnis.
**Akzeptanzkriterien:**
- [x] Eindeutige Button-Beschriftungen: „Fertigkeit erlernen" / „Zurücknehmen".
- [x] EP-Kosten vs. verfügbare EP visuell klar: Verfügbare EP farbig (grün/rot),
      Kontrast WCAG-AA (`text-green-700` / `text-red-700`).
- [x] Bei zu wenig EP: „Nicht genug EP. EP werden durch Abenteuer-Teilnahme gutgeschrieben."
**Implementierung:** `layouts/app.blade.php` — Button-Labels, Warntext und
EP-Metazeile überarbeitet. Metazeile nutzt jetzt `.html()` mit `<span>`-Farbkodierung
statt `.text()`.
**Betroffene Seiten/Routen:** `layouts/app.blade.php` (#skill-modal), `heroes/_detail.blade.php`

### UI-24 · Filter-/Suchbausteine vereinheitlichen · ⏱ 3h · ✅
**Beschreibung:** Suche/Filter sind je Modul unterschiedlich umgesetzt: Helden
nutzen ein Tailwind-Grid mit Indigo-Selects und „Filtern"-Button; Spieler eine
Fomantic `action input` mit Lupe; Fertigkeiten ein `onchange=submit`-Select ohne
Button. Uneinheitliche Bedienung erhöht die Lernlast. Ergänzt UI-06 (Baustein
existiert) um die konsequente Anwendung & einheitliche Optik.
**Akzeptanzkriterien:**
- [x] Ein gemeinsames Such-/Filter-Pattern (Optik + Verhalten) in Helden, Spieler,
      Fertigkeiten, Abenteuer. Referenz: Heroes-Form (Tailwind, amber-600-Focus,
      `bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4`).
- [x] Konsistentes Verhalten: expliziter „Filtern"-Button überall; kein Auto-Submit.
- [x] „Zurücksetzen" überall gleich platziert (neben Button) und beschriftet.
**Betroffene Seiten/Routen:** `heroes/index.blade.php`, `players/index.blade.php`,
`skills/index.blade.php`, `adventures/index.blade.php`
**Implementierung:** Spieler-Suche von Fomantic `action input` auf Tailwind
umgestellt. Fertigkeiten-Select: `onchange` entfernt, Button + einheitliches
Styling hinzugefügt. Abenteuer: Name-Suche (`q`) neu in View + Controller.

### UI-25 · Mobile Bedienbarkeit gestapelter Modals & Tabs · ⏱ 3h · ✅
**Beschreibung:** Detailansichten nutzen mehrere Tabs plus gestapelte Modals
(z. B. Spieler-Detail → Held im Stack → Foto-Crop im Stack). Auf Smartphones ist
das schwer beherrschbar: viele horizontale Tabs (`tabular menu`) brechen schlecht
um, gestapelte Modals verdecken den Schließen-Button im langen Scroll, und das
Modal lässt sich bewusst nur per Footer-Button schließen (kein X, kein
Klick-außerhalb). Touch-Nutzer verlieren leicht die Orientierung.
**Akzeptanzkriterien:**
- [x] Tab-Leisten scrollen horizontal: `overflow-x: auto; flex-wrap: nowrap` +
      `white-space: nowrap` auf allen Tab-Items in allen drei Detail-Partials.
- [x] Footer erreichbar: `<i class="close icon">` in `#app-modal` und `#app-modal-2`
      ist oben rechts fix positioniert (Fomantic), immer sichtbar unabhängig vom Scroll.
- [x] Gestapeltes Modal hat klar erkennbare Schließoption: Close-Icon +
      `closable: false` → `true` für `#app-modal-2` (Außenklick schließt nun auch).
**Betroffene Seiten/Routen:** `layouts/app.blade.php`, `players/_detail.blade.php`,
`heroes/_detail.blade.php`, `adventures/_manage.blade.php`

## Mobile-Modal-Review 2026-06 (🔲)

> Gezielte Prüfung des Modal-Systems auf Mobil (Auslöser: „Abenteuer-Modal auf
> Mobile nicht vollständig sichtbar"). Ergänzt — dupliziert NICHT — die bereits
> erledigten UI-08 (Mobile-Feinschliff) und UI-25 (gestapelte Modals/Tabs). Die
> folgenden Tickets adressieren konkrete, dort noch nicht behandelte Teilaspekte.
> Priorität: P1 = kritisch, P2 = wichtig, P3 = nice-to-have. **Lösungshinweise
> sind nur Vorschläge — kein Code in diesem Review geändert.**

### UI-26 · [P1] Modal-Footer mit vielen Buttons umbruchfähig machen · ⏱ 2h · ✅
**Beschreibung:** Die projekteigene Regel `#app-modal-actions { display: flex;
align-items: center; gap: .5rem }` (`public/css/heldenregister.css`) besitzt **kein
`flex-wrap`**. Im Abenteuer-Detail-Modal (`adventures/_detail.blade.php`) stehen je
nach Rolle bis zu vier Aktions-Buttons plus den automatisch ergänzten
„Schließen"-Button im Footer (Anmelden · Gast anmelden · Teamer-Anmeldung ·
Verwalten · Schließen). Auf Screens < ~400 px werden diese Buttons in eine einzige,
nicht umbrechende Zeile gequetscht und laufen seitlich aus dem sichtbaren Bereich —
das ist die direkte Ursache des gemeldeten „nicht vollständig sichtbar". Fomantics
eigener mobiler Button-Umbruch (`.actions > .button { margin-bottom: 1rem }`) greift
nicht, weil die `display:flex`-Regel ihn aushebelt. `.deny { margin-left: auto }`
verschärft das Quetschen zusätzlich.
**Nutzen:** Alle Touch-Nutzer (Eltern/Kinder am Handy) erreichen jede Aktion des
zentralen Anmelde-Flows zuverlässig; kein horizontales Überlaufen mehr.
**Lösungshinweis (nicht umsetzen, nur Vorschlag):** `flex-wrap: wrap` ergänzen;
auf < 768 px ggf. `width: 100%` + ausreichende Tap-Höhe (≥ 44 px) je Button und
„Schließen" nach unten umbrechen lassen. `margin-left:auto` der `.deny` auf Mobil
zurücknehmen, damit nicht künstlich Platz erzwungen wird.
**Akzeptanzkriterien:**
- [x] Footer-Buttons brechen auf schmalen Screens (320–400 px) um, statt zu überlaufen.
- [x] Jeder Button bleibt vollständig sichtbar und antippbar (Tap-Ziel ≥ 44 px Höhe).
- [x] „Schließen" bleibt eindeutig auffindbar (z. B. als letztes Element / volle Breite).
- [x] Verhalten in `#app-modal` UND `#app-modal-2` gleich (siehe UI-27).
**Betroffene Seiten/Routen:** `public/css/heldenregister.css` (`#app-modal-actions`),
`adventures/_detail.blade.php`, `adventures/_manage.blade.php`, `heroes/_detail.blade.php`

### UI-27 · [P2] Footer-Layout für gestapeltes Modal (#app-modal-2) angleichen · ⏱ 1h · ✅
**Beschreibung:** Die Flex-/Abstands-Regel existiert nur für `#app-modal-actions`.
Der Footer des gestapelten Modals (`#app-modal-2-actions`) hat **keine eigene
Regel** und erbt weder das Flex-Layout noch `.deny { margin-left: auto }`. Dadurch
sind Footer von Haupt- und gestapeltem Modal optisch und im Umbruchverhalten
unterschiedlich. Gestapelte Modals tragen u. a. den Anmelde-/Gast-Anmelde- und
Teamer-Anmelde-Flow sowie „Buchung bearbeiten" — also genau die Eltern-Flows.
**Nutzen:** Konsistente, vorhersehbare Bedienung über beide Modal-Ebenen; der
Umbruch-Fix aus UI-26 wirkt auch im gestapelten Modal.
**Lösungshinweis:** `#app-modal-2-actions` in dieselbe (umbruchfähige) Regel
aufnehmen, z. B. Selektor `#app-modal-actions, #app-modal-2-actions { … }`.
**Akzeptanzkriterien:**
- [x] `#app-modal-2-actions` nutzt dasselbe Flex-/Wrap-/Abstands-Layout wie `#app-modal-actions`.
- [x] „Schließen"-Position und Tap-Größen sind in beiden Modal-Ebenen identisch.
**Betroffene Seiten/Routen:** `public/css/heldenregister.css`, `layouts/app.blade.php`

### UI-28 · [P2] Vertikale Sichtbarkeit langer Modals auf Mobil sichern · ⏱ 3h · 🔲
**Beschreibung:** Fomantic positioniert Modals mit `position: absolute` + `top`
(mobil `top: .5rem; margin: 0 auto`), nicht `fixed`/vertikal zentriert. Bei langem
Inhalt — z. B. Verwaltungs-Modal mit `min-height: min(820px, 60vh)` plus mehreren
Tabs (`adventures/_manage.blade.php`) oder Helden-Detail (`modal-hero`) — kann der
Modal-Kopf bei gescrolltem Hintergrund über den oberen Viewport-Rand wandern; der
Titel/obere Bereich wirkt dann „abgeschnitten". Der scrollende Inhaltsbereich
(`.scrolling.content`) ist zwar auf `max-height: calc(80vh - 10rem)` gedeckelt,
aber die erzwungene `min-height` der Detail-Modals kann dem entgegenwirken.
**Nutzen:** Modal-Titel und obere Inhalte bleiben auf Smartphones immer sichtbar;
kein „Verlust" des oberen Modal-Bereichs beim Öffnen.
**Lösungshinweis (zu prüfen):** Auf < 768 px die Detail-Modal-`min-height`
(`modal-hero`/`modal-event` `.scrolling.content`) reduzieren oder entfernen, damit
Fomantics 80vh-Deckel greift; alternativ Voll-Höhen-Modal (`top:.5rem; bottom:.5rem`)
mit innerem Scroll. Real auf iOS Safari + Android Chrome verifizieren (Adressleisten-
Kollaps, `100vh`-Problematik → ggf. `dvh`).
**Akzeptanzkriterien:**
- [ ] Beim Öffnen jedes Modals ist der Titel/Header sofort vollständig sichtbar (320×568 bis 414×896).
- [ ] Langer Inhalt ist vollständig im Modal-Body scrollbar erreichbar; nichts wird abgeschnitten.
- [ ] Verhalten auf iOS Safari und Android Chrome geprüft.
**Betroffene Seiten/Routen:** `public/css/heldenregister.css` (`modal-hero`/`modal-event`),
`adventures/_manage.blade.php`, `heroes/_detail.blade.php`, `layouts/app.blade.php`

### UI-29 · [P2] Breite Tabellen in Modalen mobil bedienbar (Anmeldungen, Check-in, EP) · ⏱ 3h · 🔲
**Beschreibung:** Modal-Inhalte enthalten breite Tabellen: `adventures/_bookings.blade.php`
(7 Spalten + Aktionsleiste mit bis zu 5 Icon-Buttons), `adventures/_checkin.blade.php`
(4 Spalten + Check-in/Abmelden/auschecken), `heroes/_detail.blade.php` EP-Verlauf
(`inline fields` Formular). Sie liegen in `overflow-x-auto`-Wrappern (gut), aber im
ohnehin schmalen Modal-Body auf dem Handy entsteht doppeltes/verschachteltes
Horizontal-Scrollen, und Icon-Only-Aktionen (nur `data-tooltip`, kein sichtbares
Label) sind auf Touch kaum erschließbar (Tooltips erscheinen erst bei Hover).
**Nutzen:** Spielleitungen/Admins können Anmeldungen und Check-in auch am Handy/Tablet
am Veranstaltungsort bedienen; Aktionen sind ohne Hover verständlich.
**Lösungshinweis:** Auf < sm Karten-/Stack-Darstellung je Zeile (wie UI-19 in den
Index-Listen), Aktions-Icons mit sichtbarem Kurzlabel oder größeren Tap-Zielen.
**Akzeptanzkriterien:**
- [ ] Anmeldungs- und Check-in-Tabelle auf < sm ohne erzwungenes Quer-Scrollen nutzbar.
- [ ] Aktions-Buttons auf Touch ohne Hover-Tooltip verständlich (Label oder größere Ziele).
- [ ] EP-Verlauf-Eingabe (`inline fields`) bricht auf Mobil sauber um.
**Betroffene Seiten/Routen:** `adventures/_bookings.blade.php`, `adventures/_checkin.blade.php`,
`heroes/_detail.blade.php`, `adventures/_teamer_nsc_tab.blade.php`

### UI-30 · [P3] Footer-Buttons als einheitliche `<button>`-Elemente · ⏱ 1h · 🔲
**Beschreibung:** Footer-Aktionen sind teils `<a class="ui button" data-modal-stack>`
(Anmelden/Gast/Teamer/Verwalten in `adventures/_detail.blade.php`,
`heroes/_detail.blade.php`), teils `<button type="submit">`. Links und Buttons
verhalten sich bei Tastatur/Touch leicht unterschiedlich (z. B. Space-Taste,
Default-Fokusring) und das Mischmasch erschwert ein einheitliches Umbruch-/
Tap-Größen-Styling (UI-26).
**Nutzen:** Einheitliche Bedienbarkeit und konsistentes Styling aller Footer-Aktionen.
**Lösungshinweis:** Wo keine echte Navigation nötig ist, `<button type="button">`
mit dem `data-modal-stack`/`data-modal-url`-Attribut verwenden (Handler reagiert
bereits auf beliebige Elemente). Reine Downloads/Neue-Tab-Links (`target="_blank"`,
PDF/CSV) bleiben `<a>`.
**Akzeptanzkriterien:**
- [ ] Footer-Aktionen, die Modals öffnen, sind `<button>` mit gleichem Styling.
- [ ] Tastaturauslösung (Enter/Space) und Fokusring für alle Footer-Aktionen gleich.
- [ ] Download-/Neuer-Tab-Aktionen bleiben semantisch `<a>`.
**Betroffene Seiten/Routen:** `adventures/_detail.blade.php`, `heroes/_detail.blade.php`,
`adventures/_manage.blade.php`
