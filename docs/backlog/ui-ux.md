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

### UI-28 · [P2] Vertikale Sichtbarkeit langer Modals auf Mobil sichern · ⏱ 3h · ✅
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
- [x] Beim Öffnen jedes Modals ist der Titel/Header sofort vollständig sichtbar (320×568 bis 414×896).
- [x] Langer Inhalt ist vollständig im Modal-Body scrollbar erreichbar; nichts wird abgeschnitten.
- [x] Verhalten auf iOS Safari und Android Chrome geprüft (dvh-Einheit verwendet).
**Betroffene Seiten/Routen:** `public/css/heldenregister.css` (`modal-hero`/`modal-event`),
`adventures/_manage.blade.php`, `heroes/_detail.blade.php`, `layouts/app.blade.php`

### UI-29 · [P2] Breite Tabellen in Modalen mobil bedienbar (Anmeldungen, Check-in, EP) · ⏱ 3h · ✅
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
- [x] Anmeldungs- und Check-in-Tabelle auf < sm ohne erzwungenes Quer-Scrollen nutzbar.
- [x] Aktions-Buttons auf Touch ohne Hover-Tooltip verständlich (Label oder größere Ziele).
- [x] EP-Verlauf-Eingabe (`inline fields`) bricht auf Mobil sauber um.
**Betroffene Seiten/Routen:** `adventures/_bookings.blade.php`, `adventures/_checkin.blade.php`,
`heroes/_detail.blade.php`, `adventures/_teamer_nsc_tab.blade.php`

### UI-30 · [P3] Footer-Buttons als einheitliche `<button>`-Elemente · ⏱ 1h · ✅
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
- [x] Footer-Aktionen, die Modals öffnen, sind `<button>` mit gleichem Styling.
- [x] Tastaturauslösung (Enter/Space) und Fokusring für alle Footer-Aktionen gleich.
- [x] Download-/Neuer-Tab-Aktionen bleiben semantisch `<a>`.
**Betroffene Seiten/Routen:** `adventures/_detail.blade.php`, `heroes/_detail.blade.php`,
`adventures/_manage.blade.php`

## Child-Experience-Review 2026-06 (🔲)

> Lese-Review aller Haupt-Flows aus Sicht von Kindern (8–12 am Elternhandy),
> Jugendlichen (13–17, eigenes Handy) und Eltern/Betreuern. Fokus: problematische
> Modale, Tabs, lange/komplexe Formulare, mehrstufige Abläufe. Reihenfolge:
> zuerst Eltern-Flows (Anmeldung), dann Jugend-Flows (Heldenpflege), dann
> Admin/Profil. Bereits erledigte Tickets (UI-15/16/19/22/23/24/25/26/27/28)
> und die offenen UI-29/30 werden NICHT dupliziert. **Lösungshinweise sind nur
> Vorschläge — in diesem Review wurde kein Anwendungscode geändert.**

### UI-31 · [P1] Kosten/Beitrag vor dem Anmelden klar sichtbar machen · ⏱ 2h · ✅
**Beschreibung:** Der Teilnahmebeitrag (`$adventure->fee`) erscheint nur als Zeile
„Beitrag" im ersten Tab des Detail-Modals (`adventures/_detail.blade.php`). Wer im
Footer direkt auf „Anmelden" tippt und das gestapelte Anmeldeformular
(`bookings/_create.blade.php`) öffnet, sieht dort **keinen Preis** mehr. Für
Eltern ist „Was kostet das?" eine der ersten Fragen; sie schließen die Anmeldung
ab, ohne die Kosten noch einmal vor Augen zu haben. Auch die Belegung
(„X / Y, Z frei") und ein evtl. Wartelisten-Status sind im Formular nur teilweise
präsent.
**Nutzen:** Eltern verstehen Kosten und Platzsituation innerhalb von 30 Sekunden
und schließen die Anmeldung mit Sicherheit statt Unsicherheit ab; weniger
Rückfragen ans Orga-Team.
**Lösungshinweis (nur Vorschlag):** Im Anmeldeformular oben eine kompakte
Info-Zeile/`ui message` mit Abenteuername, Datum, Ort und **Beitrag** anzeigen
(Daten liegen über `$adventure` bereits vor). Bei `fee == 0` „kostenlos"
ausweisen. Wartelisten-Warnung (bereits vorhanden) beibehalten.
**Akzeptanzkriterien:**
- [x] Beitrag ist im Anmelde- und Gast-Anmelde-Formular sichtbar (auch „kostenlos").
- [x] Datum + Ort des Abenteuers stehen im Formularkopf.
- [x] Bei vollem Abenteuer bleibt der Wartelisten-Hinweis sichtbar.
**Betroffene Seiten/Routen:** `bookings/_create.blade.php`,
`bookings/_create_guest.blade.php`, `adventures/_detail.blade.php`
**Implementierung:** Kompakter Pergament-Info-Block (`bg-[#fdf6e3]`) direkt nach dem
`data-modal-title`, immer sichtbar (vor dem `@if registrationOpen()`-Gate). Zeigt
Abenteuername, Datum (falls gesetzt), Ort (falls gesetzt), Beitrag (grün „kostenlos"
wenn 0), Platz-Belegung (grün/orange je Verfügbarkeit). `freeSlots()` nur einmal
via `@php` aufgerufen. Wartelisten-Meldung bleibt unverändert darunter.

### UI-32 · [P1] Datenschutz-/Zweckhinweis bei Erfassung von Minderjährigen-Daten · ⏱ 3h · ✅
**Beschreibung:** An mehreren Stellen werden personenbezogene Daten von Kindern
erhoben, **ohne Zweck- oder Datenschutzhinweis**: `players/_form.blade.php`
(Geburtsdatum, Geschlecht, Anschrift des Kindes, E-Mail) und
`bookings/_create_guest.blade.php` (Alter und Ort eines minderjährigen Gastes).
Im Spieler-Anlegeformular fehlt jeder Hinweis, wofür Geburtsdatum/Adresse
gebraucht werden und wer sie sieht. Das Buchungsformular für eigene Kinder
(`_create`) macht das über UI-15 bereits vorbildlich (jedes Feld erklärt „nur fürs
Orga-Team, Notfall"), die übrigen Stellen ziehen nicht nach. Für ein Angebot mit
überwiegend minderjährigen Nutzern ist transparente Datenerhebung
vertrauensbildend und datenschutzrechtlich relevant.
**Nutzen:** Eltern vertrauen der Plattform, weil bei jeder Dateneingabe klar ist,
warum und für wen sie erhoben wird; Transparenz im Sinne der Zielgruppe.
**Lösungshinweis (nur Vorschlag):** Kurze `<small>`-Zweckhinweise je Feldgruppe
ergänzen (analog UI-15), z. B. unter Geburtsdatum „Wird für altersgerechte
Gruppen und die Notfallvorsorge benötigt." Einen Satz + Link zur
Datenschutzerklärung am Formularanfang. Bei Gast-Alter/-Ort kurzer Zweckhinweis.
**Akzeptanzkriterien:**
- [x] Geburtsdatum, Anschrift und E-Mail in `players/_form` haben einen
      verständlichen Zweckhinweis (1 Satz) und Hinweis auf die Sichtbarkeit.
- [x] Gast-Alter und -Ort in `_create_guest` haben einen kurzen Zweckhinweis.
- [x] Verweis auf die Datenschutzerklärung am Anfang beider Formulare.
**Betroffene Seiten/Routen:** `players/_form.blade.php`,
`bookings/_create_guest.blade.php`
**Implementierung:** Datenschutzhinweis-Block (blau, `bg-blue-50`) am Anfang von
`players/_form` mit Hinweis auf Orga-Team-Sichtbarkeit und kein Drittfluss.
`<small>`-Zweckhinweise bei Geburtsdatum, E-Mail und Kinder-Anschrift.
Gast-Formular: `<small>` unter Alter und Ort. Da kein Datenschutzerklärung-Route
existiert, wird statt Link auf das Organisationsteam als Anlaufstelle verwiesen.

### UI-33 · [P2] Helden-Detail: Tab-Flut für Kinder reduzieren/gruppieren · ⏱ 4h · ✅
**Beschreibung:** Das Helden-Detail-Modal (`heroes/_detail.blade.php`) zeigt
dynamisch sehr viele Tabs: „Übersicht", „Abenteuer", **je Klasse einen eigenen
Tab** (Fertigkeitsbaum) plus „EP-Verlauf". Bei einem Helden mit 3 Klassen sind das
bereits 6 Tabs, bei 4 Klassen 7. Auf dem Handy ist die Tab-Leiste zwar horizontal
scrollbar (UI-25), aber für Kinder (8–12) ist die Menge an gleichwertig
aussehenden Reitern unübersichtlich; das spielerisch wichtigste Element (der
Fertigkeitsbaum) konkurriert optisch mit Verwaltungs-Tabs („EP-Verlauf"). Es gibt
keine visuelle Hierarchie zwischen „Spielen/Entdecken" und „Verwaltung/Historie".
**Nutzen:** Kinder finden den Fertigkeitsbaum (das Belohnungsherz der App) sofort;
weniger Orientierungsverlust, mehr Spielfreude.
**Lösungshinweis (nur Vorschlag):** Fertigkeitsbäume der Klassen unter einem
gemeinsamen Tab „Fertigkeiten" mit Unterauswahl (Klassen-Pills) bündeln, statt je
Klasse einen Top-Level-Tab. Verwaltungs-/Historien-Tabs („EP-Verlauf") optisch
oder per Reihenfolge nachordnen. Aktiven Tab beim Mobil-Öffnen sichtbar
hervorheben.
**Akzeptanzkriterien:**
- [x] Anzahl der Top-Level-Tabs steigt nicht mehr linear mit der Klassenzahl.
- [x] Fertigkeitsbäume bleiben pro Klasse erreichbar (Unterauswahl).
- [x] Spiel-relevante Tabs stehen vor Verwaltungs-/Historien-Tabs.
- [x] Verhalten auf 320–414 px geprüft (Tab-Leiste ohne Überforderung).
**Betroffene Seiten/Routen:** `heroes/_detail.blade.php`

### UI-34 · [P2] Erfahrungspunkte (EP) und Fachbegriffe im Helden-Detail erklären · ⏱ 3h · ✅
**Beschreibung:** Die Übersicht im Helden-Detail (`heroes/_detail.blade.php`)
nennt „EP-Saldo", „EP gesamt / ausgegeben", „Fertigkeiten / Klassen" ohne jede
Erklärung. Für ein Kind ist nicht ersichtlich, was EP sind, wie man sie bekommt
und wofür man sie ausgibt – obwohl genau das die zentrale Spielmechanik und
Motivationsschleife ist. UI-23 erklärt EP nur im Lern-Modal („EP werden durch
Abenteuer-Teilnahme gutgeschrieben"), nicht aber in der Helden-Übersicht selbst.
Auch im Navigationspunkt „EP buchen" und der Abenteuer-Detailzeile „Belegung"
bleiben Begriffe unerklärt.
**Nutzen:** Kinder verstehen den Kern-Spielkreislauf (Abenteuer → EP →
Fertigkeiten) und werden zum Weitermachen motiviert; weniger Erklärbedarf durch
Betreuer/Eltern.
**Lösungshinweis (nur Vorschlag):** Kurzer, freundlicher Hilfetext/Tooltip bei
„EP-Saldo" („Erfahrungspunkte – sammelst du durch Abenteuer, gibst du für
Fertigkeiten aus."). Begriffe gemäß `docs/begriffe.md` vereinheitlichen; ggf.
„verfügbar / insgesamt / ausgegeben" statt „Saldo".
**Akzeptanzkriterien:**
- [x] „EP-Saldo" o. Ä. hat einen verständlichen Ein-Satz-Hilfetext/Tooltip.
- [x] Begriffe konsistent mit `docs/begriffe.md`.
- [x] Hilfetext ist auch auf dem Handy ohne Hover erreichbar (Touch).
**Betroffene Seiten/Routen:** `heroes/_detail.blade.php`, `docs/begriffe.md`

### UI-35 · [P2] Hilfetexte/Tooltips aus dem Anmelde- ins Bearbeiten-Formular übernehmen · ⏱ 1h · ✅
**Beschreibung:** `bookings/_create.blade.php` erklärt durch UI-15 jedes sensible
Feld (Allergien, Medikamente, Erreichbarkeit, Notfallnummer) und enthält die
Pflichtfeld-Legende sowie die Teilnahmebedingungen. Das Bearbeiten-Formular
`bookings/_edit.blade.php` zeigt dieselben Felder **ohne diese Hilfetexte** (nur
der NSC-Tooltip ist vorhanden) und ohne Pflichtfeld-Legende. Wer eine bestehende
Anmeldung später korrigiert, verliert den Kontext, der beim ersten Mal noch da
war – uneinheitlich und für Eltern verwirrend.
**Nutzen:** Konsistente, vorhersehbare Bedienung; Eltern müssen die Bedeutung der
Felder beim Bearbeiten nicht neu erschließen.
**Lösungshinweis (nur Vorschlag):** Die `<small>`-Hilfetexte und die
Pflichtfeld-Legende aus `_create` in `_edit` übernehmen (ggf. gemeinsames Partial
für die Feldgruppe, um Doppelpflege zu vermeiden).
**Akzeptanzkriterien:**
- [x] Allergien/Medikamente/Erreichbarkeit/Notfallnummer haben in `_edit` dieselben
      Hilfetexte wie in `_create`.
- [x] Pflichtfeld-Legende auch in `_edit` vorhanden.
- [x] Keine inhaltliche Doppelpflege (gemeinsames Partial geprüft).
**Betroffene Seiten/Routen:** `bookings/_edit.blade.php`, `bookings/_create.blade.php`
**Implementierung:** Pflichtfeld-Legende, Placeholder-Texte und `<small>`-Hilfetexte
aus `_create` in `_edit` übernommen. Gemeinsames Partial abgelehnt: `_edit` hat
andere Defaults (Bestandswerte statt `old()`), keine Spieler-Auswahl, keine AGB —
zu unterschiedlich für sinnvolle Abstraktion.

### UI-36 · [P3] Profilseite ins Theme bringen + Datenübersicht/Datenschutz-Link · ⏱ 3h · ✅
**Beschreibung:** Die Profilseite (`profile/edit.blade.php`) bricht aus dem
Mittelalter-/Pergament-Theme aus: generischer grauer Header
(`text-gray-800`/`font-semibold` statt `font-uncial text-waldritter`) und weiße
Breeze-Standardkarten. Für Eltern, die hier ihre Stammdaten und das Passwort
pflegen, wirkt die Seite wie ein fremder, „technischer" Bereich – das schwächt das
Vertrauen, das die übrigen (themengetreuen) Seiten aufbauen. Zudem fehlt ein
zentraler, transparenter Überblick „Welche Daten sind über mich/mein Kind
gespeichert?" und ein gut auffindbarer Link zur Datenschutzerklärung – gerade bei
minderjährigen Nutzern ein wichtiger Vertrauensanker.
**Nutzen:** Einheitliches, vertrauenswürdiges Erscheinungsbild; Eltern finden
Datenschutz- und Dateninformationen an erwartbarer Stelle.
**Lösungshinweis (nur Vorschlag):** Header auf `font-uncial text-waldritter`
umstellen, Karten an das Theme angleichen (`bg-white/60 border-2`…). Abschnitt
„Deine gespeicherten Daten" mit Verweis auf die Datenschutzerklärung und
Kontaktmöglichkeit ergänzen.
**Akzeptanzkriterien:**
- [x] Profil-Header und Karten folgen dem Pergament-/Waldritter-Theme.
- [x] Sichtbarer Link zur Datenschutzerklärung auf der Profilseite.
- [x] Kurzer Hinweis, welche Datenkategorien gespeichert sind / an wen man sich wendet.
**Betroffene Seiten/Routen:** `profile/edit.blade.php`,
`profile/partials/*` (Header-Stil)

### UI-37 · [P3] Orientierung im zweistufigen Anmelde-Modal-Stack verbessern · ⏱ 2h · ✅
**Beschreibung:** Der Eltern-Anmeldeflow läuft über zwei gestapelte Modals:
Abenteuer-Detail (`#app-modal`) → „Anmelden" öffnet das Anmeldeformular im
gestapelten Modal (`#app-modal-2`). Auf dem Handy ist nicht erkennbar, dass man
sich auf einer zweiten Ebene befindet und dass „Schließen" zurück zum Detail führt
(nicht abbricht). Es gibt keine Schritt-/Orientierungsanzeige („Schritt: Anmeldung
für <Abenteuer>"). Kinder/Eltern verlieren bei langem Formular und Scroll leicht
den Kontext, in welchem Abenteuer sie gerade anmelden.
**Nutzen:** Klare Orientierung im mehrstufigen Flow; geringeres Risiko,
versehentlich abzubrechen oder das falsche Abenteuer zu buchen.
**Lösungshinweis (nur Vorschlag):** Im gestapelten Anmelde-Modal einen klaren,
kontextgebenden Titel/Breadcrumb anzeigen („Anmeldung · <Abenteuer>") – Titel ist
bereits gesetzt, aber visuell als Ebene-2 kennzeichnen. „Schließen" ggf. in
„Zurück" umbenennen, wo es nur die obere Ebene schließt.
**Akzeptanzkriterien:**
- [x] Gestapeltes Anmelde-Modal zeigt sichtbar Abenteuername als Kontext.
- [x] Schließen/Zurück-Aktion ist als „zurück zum Abenteuer" erkennbar.
- [x] Auf 320–414 px geprüft.
**Betroffene Seiten/Routen:** `layouts/app.blade.php`,
`bookings/_create.blade.php`, `bookings/_create_guest.blade.php`

## Mobile-First-Konzept 2026-06 (🔲)

> Zielgruppe: Kinder 8–17 + Eltern, primär am **Smartphone**. Das System ist
> heute „Desktop-mit-Mobil-Pflaster": fast alle Details laufen als AJAX-Modal
> (`#app-modal`), Detailansichten nutzen horizontale Fomantic-Tabs, Tabellen in
> Modals sind nur quer-scrollbar, und die gesamte Sekundär-Navigation versteckt
> sich auf Mobil im Hamburger. Dieses Konzept dreht die Logik auf Mobile-First.
> **Kein Anwendungscode in diesem Review geändert — Lösungshinweise sind Vorschläge.**
>
> **Modal → Seite:** Die „großen", verlinkbaren Entitäten werden auf Mobil zu
> echten Seiten mit eigener URL — Helden-Detail, Abenteuer-Detail und das
> Verwaltungs-Modal. Sie haben bereits reale Routen (`heroes.show`,
> `adventures.show`, `adventures.manage`), liefern bei AJAX nur das Partial → der
> Umbau zur Vollseite ist günstig (Browser-Back, Teilen/Verlinken, weniger
> DOM-/Scroll-Konflikte, kein Stack-Chaos). **Modal bleiben** die kurzen,
> kontextgebundenen Aktionen: Bestätigung, Skill-Lernen, Foto-Crop, Unterschrift/
> Check-in, Abmelde-Grund — und die kurzen Formulare (Anmeldung, EP buchen,
> Bearbeiten) als Bottom-Sheet. **Spieler-Detail** bleibt vorerst Modal (kürzer,
> meist Sprungbrett zum Helden), kann später nachziehen.
>
> **Tabs → Accordion:** Auf < `sm` werden die Detail-Tabs (Helden, Abenteuer,
> Verwaltung, Spieler) zu vertikalen Accordions (eine Sektion offen). Das löst
> das horizontale Tab-Scrollen (UI-25) und die Tab-Flut bei vielen Helden-Klassen
> (UI-33). Ab `sm` bleiben Tabs.
>
> **Bottom-Navigation (5 Punkte, nur < `sm`):** Übersicht · Helden · Abenteuer ·
> Spieler · Mehr. „Mehr" öffnet ein Sheet mit den rollenabhängigen Resten
> (Fertigkeiten, EP buchen, Verwaltung) + Profil, Benachrichtigungen, Abmelden.
> Punkte respektieren `@can`; fehlt eine Berechtigung, rückt der nächste erlaubte
> Punkt nach. Top-Navbar bleibt ab `sm`.
>
> **Mobiles Dashboard:** Statt reiner Bild-Kachel-Navigation (die die Bottom-Nav
> ohnehin dupliziert) oben Quick-Actions + „Nächstes Abenteuer" + Hero-/EP-Status.
>
> **Umsetzungsreihenfolge:**
> - **Phase 1 (Breaking, Architektur):** UI-38 (Detail-Seiten Helden/Abenteuer),
>   UI-39 (Verwaltung als Seite), UI-42 (Bottom-Nav). Verändert Routing/Layout.
> - **Phase 2 (Enhancement, additiv):** UI-40 (Tabs→Accordion), UI-41
>   (Modal-Tabellen→Karten, erweitert UI-29), UI-43 (mobiles Dashboard),
>   UI-44 (Anmelde-/Kurzformulare als Bottom-Sheet). Bauen auf Phase 1 auf.

### UI-38 · [P1] Helden- & Abenteuer-Detail als echte Seite (mit Modal-Fallback) · ⏱ 8h · ✅
**Beschreibung:** Helden- und Abenteuer-Detail öffnen heute ausschließlich im
AJAX-Modal (`#app-modal`, Partials `heroes/_detail.blade.php`,
`adventures/_detail.blade.php`). Auf dem Smartphone bedeutet das: kein nutzbarer
Browser-Zurück (Schließen nur per Footer-Button), keine teil-/verlinkbare URL,
verschachteltes Scrollen (Seite + Modal-Body), und beim Anmelden ein zweistufiger
Modal-Stack (UI-37). Da `heroes.show`/`adventures.show` bereits **echte Routen**
sind, die bei `X-Requested-With` nur das Partial liefern, kann derselbe Inhalt mit
geringem Aufwand auch als Vollseite ausgeliefert werden.
**Nutzen:** Kinder/Eltern bekommen echtes Zurück, teilbare Links (z. B. Abenteuer
per Link weitergeben), flüssiges Scrollen ohne Modal-im-Modal und einen klaren
„eine Sache pro Bildschirm"-Fokus — die Kernerwartung am Handy.
**Lösungshinweis (nur Vorschlag):** Bei Nicht-AJAX-Request das vorhandene Partial
in `x-app-layout` wrappen: `data-modal-title` → Seiten-Header (`font-uncial
text-waldritter`), `data-modal-actions` → Sticky-Footer-Leiste am unteren Rand
(Tap-Ziel ≥ 44 px). Modal-Variante als Verhalten ab `sm` (Desktop) optional
beibehalten oder ganz auf Seiten umstellen. Anmelden/Verwalten verlinken dann auf
echte Seiten/Sheet statt Modal-Stack.
**Akzeptanzkriterien:**
- [x] `heroes.show` und `adventures.show` rendern vollwertige Seite mit Theme-Header und `<x-mobile.sticky-footer>`.
- [x] Browser-Zurück führt von der Detailseite zur Liste zurück (echte Route, kein Redirect).
- [x] Detail-URL ist teil-/verlinkbar.
- [x] Aktionen (Anmelden/Gast/Teamer → Modal, Verwalten → direkte Seitennavigation, Bearbeiten → Modal).
- [x] Mobile Cards in adventures/index + heroes/index navigieren direkt (kein Modal mehr auf < sm).
- [x] Desktop-Tabellenzeilen öffnen weiterhin per Modal (unverändert).
**Betroffene Seiten/Routen:** `heroes/_detail.blade.php`, `adventures/_detail.blade.php`,
`HeroController@show`, `AdventureController@show`, `routes/web.php`, `layouts/app.blade.php`
**Abhängigkeiten:** Entschärft UI-28 (vertikale Modal-Sichtbarkeit) und UI-37
(Stack-Orientierung) für diese Ansichten; Grundlage für UI-40/UI-41.

### UI-39 · [P1] Abenteuer-Verwaltung als eigene Seite statt Modal · ⏱ 5h · ✅
**Beschreibung:** Das Verwaltungs-Modal (`adventures/_manage.blade.php`, Route
`adventures.manage`) ist der inhaltlich schwerste Dialog: 4 Tabs (Event-Daten-
Editor, Anmeldungen, Teamer/NSC, Check-in) mit breiten Tabellen und Formularen,
erzwungen auf `min-height: min(820px,60vh)` (`modal-event`). Es wird heute aus dem
Abenteuer-Detail-Modal **als weiteres Modal** geöffnet — auf dem Handy/Tablet am
Veranstaltungsort (Check-in!) kaum beherrschbar. `adventures.manage` ist bereits
eine echte Route.
**Nutzen:** Spielleitungen/Teamer können Anmeldungen und Check-in am Handy/Tablet
vor Ort bedienen; voller Bildschirm, echtes Zurück, kein Modal-Höhen-Konflikt.
**Lösungshinweis (nur Vorschlag):** `adventures.manage` als Vollseite rendern
(Partial in `x-app-layout`), „Verwalten" im Detail wird ein Link auf diese Seite.
Speichern-Footer als Sticky-Bar. Tabs auf Mobil als Accordion (UI-40).
**Akzeptanzkriterien:**
- [x] `adventures.manage` ist eigene Seite (manage.blade.php) mit Theme-Header, Sticky-Footer (Speichern/Zurück).
- [x] „Verwalten" in _detail und manage_index navigiert direkt (kein data-modal-url mehr).
- [x] Check-in-Tabelle via x-mobile.cards-or-table: Karten auf Mobile, kein Modal-Höhen-Limit.
- [x] sendModalAction lädt auf Vollseite via window.location.reload() statt appModalUrl.
- [x] „Zurück"-Link im Footer zeigt auf adventures.show (Detail-Seite).
**Betroffene Seiten/Routen:** `adventures/_manage.blade.php`,
`AdventureController@manage`, `routes/web.php`
**Abhängigkeiten:** Baut auf UI-38 (gleiches Wrapper-Muster); kombiniert mit
UI-40 (Accordion) und UI-41 (Tabellen→Karten für `_bookings`/`_checkin`, vgl. UI-29).

### UI-40 · [P2] Detail-Tabs auf Mobil als Accordion statt horizontaler Tab-Leiste · ⏱ 5h · ✅
**Beschreibung:** Alle Detailansichten nutzen Fomantic `tabular menu`: Helden bis
zu 7 Tabs (Übersicht, Abenteuer, je Klasse einer, EP-Verlauf — vgl. UI-33),
Verwaltung 4, Abenteuer-Detail 3, Spieler 4. Auf Mobil scrollt die Leiste
horizontal (UI-25 Workaround), aber nebeneinander liegende, gleich aussehende
Reiter sind für Kinder unübersichtlich, und nicht-aktive Tabs sind unsichtbar
(kein Überblick, was es überhaupt gibt). Ein vertikales Accordion zeigt alle
Sektionen untereinander; eine ist offen.
**Nutzen:** Kinder sehen sofort, welche Bereiche es gibt; kein horizontales
Suchen; der spielwichtige Fertigkeitsbaum ist klar auffindbar (ergänzt UI-33).
**Lösungshinweis (nur Vorschlag):** Auf < `sm` die Tab-Struktur als Accordion
rendern (z. B. Fomantic `ui accordion` oder native `<details>`), ab `sm` weiter
Tabs. Reihenfolge spielrelevant zuerst (Fertigkeiten vor Verwaltung/Historie,
vgl. UI-33). Offen-Zustand bei Teil-Refresh erhalten.
**Akzeptanzkriterien:**
- [x] Auf < `sm` werden Detail-Tabs als Accordion (eine Sektion offen) dargestellt.
- [x] Alle Sektionen sind ohne horizontales Scrollen erreichbar.
- [x] Tastatur-/Screenreader-bedienbar (aufklappbar mit Enter/Space, Status erkennbar).
- [x] Ab `sm` bleibt das bisherige Tab-Verhalten erhalten.
- [x] Geprüft mit einem Helden mit ≥ 3 Klassen (≥ 6 Sektionen) auf 320–414 px.
**Betroffene Seiten/Routen:** `heroes/_detail.blade.php`, `adventures/_detail.blade.php`,
`adventures/_manage.blade.php`, `players/_detail.blade.php`, `layouts/app.blade.php` (Tab-Init)
**Abhängigkeiten:** Ergänzt UI-25 (Tab-Scrollen) und UI-33 (Tab-Flut); wirkt in
UI-38/UI-39-Seiten wie im verbleibenden Modal.

### UI-41 · [P2] Tabellen in Detailansichten & Admin auf Mobil als Karten · ⏱ 5h · ✅
**Beschreibung:** UI-19 hat die Index-Listen (Helden, Abenteuer) auf Karten
umgestellt, UI-29 adressiert die Anmeldungs-/Check-in-Tabellen. Ungelöst bleiben
weitere mehrspaltige Tabellen **in Detailansichten** — Spieler-Detail (Helden,
besuchte Abenteuer), Helden-Detail (bestrittene Abenteuer, Anmeldungen,
EP-Verlauf, Perlen) — sowie die **Admin-Lookups**, die nur `overflow-x-auto`
haben (UI-19) und damit auf dem Handy weiterhin quer gescrollt werden müssen.
**Nutzen:** Eltern/Kinder lesen Helden-Historie und EP-Verlauf am Handy ohne
Quer-Scrollen; Admins bedienen Lookups mobil.
**Lösungshinweis (nur Vorschlag):** Wiederverwendbares „Tabelle → Karten unter
< `sm`"-Muster (Label/Wert-Paare) wie in UI-19, idealerweise als Blade-Komponente,
um Doppelpflege zu vermeiden. Zuerst Detail-Tabellen, dann Admin-Lookups.
**Akzeptanzkriterien:**
- [x] Tabellen in `players/_detail` und `heroes/_detail` werden auf < `sm` als
      Karten (Label + Wert) dargestellt, ohne erzwungenes Quer-Scrollen (via UI-40 Accordion).
- [x] Alle 14 Admin-Lookup-Tabellen erhalten Kartenfallback auf < `sm`
      (`x-mobile.cards-or-table` + `data-label` auf allen Daten-`<td>`).
- [x] Interaktive Zeilen behalten Tap-Ziel + Tastaturbedienung (vgl. UI-20).
- [x] Geprüft auf 320–414 px.
**Betroffene Seiten/Routen:** `players/_detail.blade.php`, `heroes/_detail.blade.php`,
`adventures/_bookings.blade.php`, `admin/*/index.blade.php`
**Abhängigkeiten:** Erweitert UI-19 und UI-29 auf Detail-/Admin-Tabellen; nutzt
das gleiche Karten-Muster.

### UI-42 · [P1] Bottom-Navigation für Mobil (5 Hauptpunkte + „Mehr") · ⏱ 6h · ✅
**Beschreibung:** Die Navigation (`layouts/navigation.blade.php`) ist eine
Top-Navbar mit Alpine-Hamburger; auf Mobil verschwinden **alle** Navigationsziele
(inkl. Benachrichtigungen, Profil, Abmelden) hinter dem Hamburger-Icon. Für die
junge, mobile Zielgruppe ist eine permanent sichtbare Bottom-Navigation mit großen
Tap-Zielen die erwartete und schnellste Bedienung (Daumenreichweite).
**Nutzen:** Die wichtigsten Bereiche sind mit einem Daumen-Tap erreichbar; weniger
Klicks, klare Orientierung, app-typisches Gefühl für Kinder/Jugendliche.
**Lösungshinweis (nur Vorschlag):** Fixierte Bottom-Bar nur < `sm`
(`fixed bottom-0`), 5 Punkte: **Übersicht · Helden · Abenteuer · Spieler · Mehr**.
Jeder Punkt Icon + Kurzlabel, aktiver Zustand farbig (Waldritter/Amber),
Tap-Ziel ≥ 44 px. `@can` respektieren (fehlt z. B. Heldenregister, rückt der
nächste erlaubte Punkt nach oder „Mehr" trägt ihn). „Mehr" öffnet ein Sheet mit
Fertigkeiten, EP buchen, Verwaltung (rollenabhängig) + Profil, Benachrichtigungen
(mit Badge), Abmelden. `<main>` unten Padding geben, damit die Bar nichts verdeckt.
Top-Navbar bleibt ab `sm`; Hamburger entfällt < `sm`.
**Akzeptanzkriterien:**
- [x] Auf < `sm` ist eine fixierte Bottom-Nav mit bis zu 5 Punkten sichtbar.
- [x] „Mehr" öffnet Profil, Abmelden, Benachrichtigungen + rollenabhängige Reste.
- [x] Aktiver Bereich ist hervorgehoben; Tap-Ziele ≥ 44 px.
- [x] Punkte/Sheet-Einträge respektieren die `@can`-Berechtigungen.
- [x] Inhalt wird nicht von der Bar verdeckt (ausreichendes `padding-bottom`).
- [x] Benachrichtigungs-Badge bleibt mobil sichtbar erreichbar.
**Betroffene Seiten/Routen:** `layouts/navigation.blade.php`, `layouts/app.blade.php`
**Abhängigkeiten:** Unabhängig umsetzbar; harmoniert mit UI-43 (Dashboard muss die
Kachel-Navigation dann nicht mehr doppeln).

### UI-43 · [P2] Mobiles Dashboard: Quick-Actions, nächstes Abenteuer, Hero-Status · ⏱ 5h · ✅
**Beschreibung:** Das Dashboard (`dashboard.blade.php`) ist auf Mobil eine reine
Kachel-Navigation (5–6 große Bild-Kacheln `h-44`), die exakt die Nav-Ziele
dupliziert und viel vertikalen Platz frisst, ohne inhaltlichen Mehrwert. Für die
Zielgruppe wäre die Startseite wertvoller mit konkretem Kontext: Was steht als
Nächstes an? Wie viele EP hat mein aktiver Held? Was kann ich jetzt tun?
**Nutzen:** Kinder/Eltern sehen beim Öffnen sofort Relevantes (nächstes Abenteuer,
EP-Stand) und die häufigsten Aktionen — statt nur ein zweites Menü. Mehr
Motivation, weniger Klicks.
**Lösungshinweis (nur Vorschlag):** Auf < `sm` oben kompakte Quick-Action-Buttons
(z. B. „Zu Abenteuern", „Mein Held", „Anmelden"), darunter Karte „Nächstes
Abenteuer" (Name, Datum, Ort, Anmelden) und „Mein aktiver Held" (Name, EP-Saldo,
Link). Bild-Kacheln auf Mobil reduzieren oder weglassen (Bottom-Nav UI-42 deckt
Navigation ab). Admin-Kennzahlen (`$metrics`) bleiben.
**Akzeptanzkriterien:**
- [x] Auf < `sm` zeigt das Dashboard Quick-Actions statt nur Bild-Kacheln.
- [x] „Nächstes Abenteuer" (falls vorhanden) mit Datum/Ort + Aktion sichtbar.
- [x] Hero-/EP-Status des aktiven Helden sichtbar (falls vorhanden).
- [x] Sinnvoller Leerzustand, wenn nichts ansteht (vgl. UI-22).
- [x] Geprüft auf 320–414 px.
**Betroffene Seiten/Routen:** `dashboard.blade.php`, `DashboardController`
**Abhängigkeiten:** Sinnvoll nach UI-42 (Navigation aus dem Dashboard ausgelagert);
nutzt Leerzustands-Muster aus UI-22.

### UI-44 · [P2] Kurzformulare (Anmeldung, EP, Bearbeiten) als Bottom-Sheet statt Stack-Modal · ⏱ 4h · ✅
**Beschreibung:** Nach dem Umbau der Detailansichten zu Seiten (UI-38/39) sollen
die kurzen, kontextgebundenen Formulare nicht wieder als (gestapelte) zentrierte
Desktop-Modals erscheinen — auf Mobil ist dafür ein vom unteren Rand
einfahrendes Bottom-Sheet die natürlichere, daumenfreundlichere Form (Schließen
durch Wegwischen/Button unten). Betrifft Anmeldung/Gast-Anmeldung
(`bookings/_create*`), EP buchen und die Bearbeiten-Formulare.
**Nutzen:** Eltern füllen das Anmeldeformular in einer ruhigen, vollbreiten
Vom-Rand-Ansicht aus; klarer Schließen-/Abschicken-Bereich unten in
Daumenreichweite; Orientierungsverlust aus UI-37 entfällt.
**Lösungshinweis (nur Vorschlag):** Auf < `sm` Modal als Bottom-Sheet stylen
(volle Breite, vom unteren Rand, Drag-/Schließleiste oben, Footer fix unten).
Ab `sm` weiter zentriertes Modal. Kontext-Kopf „Anmeldung · <Abenteuer>" (UI-37)
beibehalten.
**Akzeptanzkriterien:**
- [x] Anmelde-/Gast-/Bearbeiten-Formulare erscheinen auf < `sm` als Bottom-Sheet
      (volle Breite, vom unteren Rand, abgerundete obere Ecken).
- [x] Schließen-/Abschicken-Bereich ist unten in Daumenreichweite (Fomantic-Actions-Footer).
- [x] Kontext (Abenteuername) im Sheet-Kopf via UI-37-Breadcrumb-Strips.
- [x] Ab `sm` unverändertes Modalverhalten (Regeln nur bei max-width: 639px).
- [x] Slide-up-Animation via `@keyframes sheet-slide-up`.
- [x] Geprüft auf 320–414 px.
**Betroffene Seiten/Routen:** `layouts/app.blade.php`, `public/css/heldenregister.css`,
`bookings/_create.blade.php`, `bookings/_create_guest.blade.php`, `bookings/_edit.blade.php`
**Abhängigkeiten:** Setzt UI-38/39 voraus (Stack entsteht dann nicht mehr aus
Detail-Modals); ersetzt teilweise UI-37; nutzt Footer-Wrap aus UI-26/27.
