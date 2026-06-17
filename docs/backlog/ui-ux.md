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

### UI-05 · Konsistente Fomantic-Formularkomponenten · ⏱ 4h · 🔲
**Beschreibung:** Mischung aus Tailwind- und Fomantic-Formularen vereinheitlichen;
Fomantic-Dropdowns/Calendar (wie Legacy) für Auswahl/Datum.
**Akzeptanzkriterien:**
- [ ] Wiederverwendbare Blade-Komponenten für Feld/Select/Checkbox (Fomantic).
- [ ] Datepicker (Fomantic Calendar) für Datumsfelder.
- [ ] Helden-/Spieler-/Event-Formulare umgestellt.

### UI-06 · Such-/Filter-/Sortier-Baustein für Listen · ⏱ 4h · ✅
**Beschreibung:** Gemeinsames Muster für Suche/Filter/Sortierung mit
Paginierungs-Erhalt (Query-String).
**Akzeptanzkriterien:**
- [x] Wiederverwendbare Suchleiste + serverseitige Filterung.
- [x] In mind. einer Liste produktiv (Helden oder Spieler). (umgesetzt in PLAY-09)
- [x] Tests.

### UI-07 · Modal-Submit ohne Reload (Teil-Refresh) · ⏱ 4h · 🔲
**Beschreibung:** Aktuell `reload` nach Erfolg. Stattdessen Liste/Modal gezielt
per AJAX aktualisieren.
**Akzeptanzkriterien:**
- [ ] Nach Erfolg wird der betroffene Listeneintrag/Modal-Inhalt neu geladen.
- [ ] Kein voller Seiten-Reload mehr; Toast bleibt.

### UI-08 · Responsives Verhalten & Mobile-Feinschliff · ⏱ 3h · 🔲
**Beschreibung:** Tabellen/Modals/Karten auf Mobil prüfen und anpassen.
**Akzeptanzkriterien:**
- [ ] Tabellen scrollbar/stacked auf kleinen Screens.
- [ ] Modals nutzbar auf Mobil (Scroll/Fullscreen).

### UI-09 · Flash-Messages global als Toast · ⏱ 2h · ✅
**Beschreibung:** Session-`status`/`error` (Vollseiten) ebenfalls als Toast
darstellen (einheitliches Feedback).
**Akzeptanzkriterien:**
- [x] Beim Laden vorhandene Flash-Messages als Toast ausgeben.
- [x] Keine doppelte Anzeige (Box + Toast).

### UI-10 · Fomantic-Assets lokal bündeln (statt CDN) · ⏱ 3h · 🔲
**Beschreibung:** Fomantic/jQuery aktuell per CDN; für Offline/Prod lokal via Vite.
**Akzeptanzkriterien:**
- [ ] Fomantic + jQuery über npm/Vite gebaut und eingebunden.
- [ ] Keine externen CDN-Abhängigkeiten zur Laufzeit.
- [ ] Build dokumentiert.

### UI-11 · Accessibility-Grundlagen · ⏱ 3h · 🔲
**Beschreibung:** Fokus-Management in Modals, Labels, Kontraste, ARIA.
**Akzeptanzkriterien:**
- [ ] Modale fangen Fokus, schließen mit ESC.
- [ ] Formularfelder mit Labels; ausreichende Kontraste.

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

### UI-14 · Accessibility-Rollen anzeigen · ⏱ 3h · 🔲
**Beschreibung:** Unter Profil soll sichtbar sein, welche Rollen der aktuelle Nutzer hat.
**Akzeptanzkriterien:**
- [ ] Anzeigen der aktivierten Rollen unter dem User-Profil
- [ ] alternativ auch in der Headleiste?
