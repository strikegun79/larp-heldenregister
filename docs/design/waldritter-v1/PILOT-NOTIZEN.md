# Pilot „Dashboard“ – Waldritter-Designsystem v1

Stand: 23.11.2026 · Branch `develop`

> Ab Abschnitt „Weitere umgestellte Seiten“ dokumentiert diese Datei auch die Seiten nach dem Pilot.

## Ergebnis

Das Dashboard (`/dashboard`) ist als erste Seite auf das Designsystem
„Die lebendige Waldritter-Chronik“ umgestellt und anschließend durch
`accessibility-reviewer` und `child-experience-reviewer` geprüft worden.
Alle Befunde, die das Dashboard selbst betreffen, sind umgesetzt.

Im Pilot blieben alle anderen Seiten optisch unverändert, weil die neuen Werte für kollidierende
Tokens nur innerhalb von `.wr-v1` galten. Das gilt seit den globalen Schritten G1/G2
(Navigation, Tokens, Buttons, Fokus) nicht mehr – siehe „Globale Umstellung“.

### Umbau

| Schritt | Commit | Inhalt |
|---|---|---|
| 1 | `94a9e39` | Tokens, `wr-*`-Komponentenklassen, Tailwind-Farben `wr.*` |
| 2 | `507223d` | Blade-Komponenten `x-wr.*` |
| 3 | `8f39780` | Umbau `dashboard.blade.php` |
| 4 | `8898dd5` | Pilot-Notizen |
| – | `eb6a7d5` | Designsystem-Textdateien versioniert |

### Review und Korrekturen

| Paket | Commit | Inhalt |
|---|---|---|
| A | `4b3a8a2` | Barrierefreiheit und Texte (siehe unten) |
| – | `60f55c3` | Backlog-Tickets für Befunde außerhalb des Dashboards |
| – | `ebcd1ac` | Gefüllte Symbole statt `outline`-Varianten |
| – | `11ea62f` | Heldenkarte auf schmalen Bildschirmen (400–510 px) |
| B1–B3 | `11079af` | Quest-Banner für gebuchtes Abenteuer, vollständiger Termin, Onboarding ausblendbar |
| B4 | `1fddf3f` | Eindeutigere Statusbegriffe |
| B5 | `03c08b5` | Fortschritts-Chips und Platzhalter für Spieler ohne Helden |
| Fix | `77bfc3f` | `Hero::skills_count` nutzt `withCount` (LazyLoadingViolation) |
| Bilder | `07290ce`, `1b335e9` | `abenteuer_v3.jpg`, `heroes_db_v2.jpg` |

## Getroffene Entscheidungen

1. **Navigation** (oben und Bottom-Nav) im Pilot unverändert. Sie gilt für alle Seiten
   und wird in einem eigenen Schritt umgestellt (siehe UI-46).
2. **Anmeldungen / nächstes Abenteuer** erscheinen einheitlich nur mit `adventure.access`.
   Vorher wurden sie auf dem Smartphone ohne diese Prüfung angezeigt.
3. **Admin-Kennzahlen** stehen am Seitenende im Abschnitt „Verwaltung auf einen Blick“.
4. **Begrüßung** steht im Seiteninhalt (Überschriftzeile und H1). Die Header-Leiste entfällt auf dem Dashboard.
5. **Status nie nur über Farbe:** Anmeldestatus, Beitrag und Teamer stehen als sichtbarer
   Text mit eigenem Symbol in der Pill. Tooltips allein reichen nicht (Smartphone).
6. **Symbole immer gefüllt**, keine Fomantic-`outline`-Varianten.
7. **Onboarding ausblenden** wird pro Konto im Browser gespeichert
   (`onboarding_dismissed_{user_id}`), weil Eltern und Kinder oft ein Gerät teilen.
8. **Newsletter-Banner** steht hinter den Kacheln: erst die eigenen Aufgaben, dann Werbung.
   Ganz ausblenden für neue Konten geht nicht, `NewsletterSubscriptionTest` erwartet es.

## Aufbau des Dashboards (Reihenfolge)

1. Begrüßung (Überschriftzeile, H1, Einleitung), Newsletter-Meldungen, Startseiten-Nachricht
2. „Deine ersten Schritte“ (solange nicht alles erledigt und nicht ausgeblendet)
3. „Meine Anmeldungen“: nächstes gebuchtes Abenteuer als Quest-Banner mit Countdown,
   weitere als Karten. Ohne Anmeldung: nächstes Abenteuer als Quest-Banner.
4. „Deine Helden“: Heldenkarten mit Fortschritts-Chips, Platzhalter für Spieler ohne Helden
5. „Wohin möchtest du?“: Kacheln (mobil zweispaltig und kompakt)
6. Newsletter-Banner
7. „Verwaltung auf einen Blick“ (nur Admins)

## Komponenten

| Komponente | Zweck |
|---|---|
| `x-wr.section-heading` | Abschnittstitel, optional per Skript fokussierbar (`focusable`) |
| `x-wr.card` | Karte/Kachel; `compact` (mobil kleines Bild, ohne Fließtext), `hint` (z. B. externe Website) |
| `x-wr.quest-banner` | Banner mit Motiv und Waldgrün-Verlauf, Standardmotiv `abenteuer_v3.jpg` |
| `x-wr.adventure-meta` | Termin mit Wochentag, Uhrzeit und Ende; Ort; Beitrag |
| `x-wr.booking-card` | Abenteuer mit eigenen Anmeldungen |
| `x-wr.booking-participants` | Teilnehmerliste mit Status-Pills (Karte und Banner) |
| `x-wr.hero-card` | Heldenkarte; optional `skills-count`, `adventures-count` |
| `x-wr.hero-placeholder` | Eigener Spieler ohne Helden („Wartet noch auf das erste Abenteuer“) |
| `x-wr.class-badge` | Klassenbanner + Name, Klassenfarbe als Akzent |
| `x-wr.step` | Onboarding-Schritt; `later-hint` statt Abblenden |
| `x-wr.stat-tile` | Kennzahl (Verwaltung) |
| `x-wr.adventure-card` | Abenteuerkarte für Listen (echter Link, ab 640 px Modal) inkl. „Deine Anmeldungen“ |
| `x-wr.adventure-status` | Anmeldestatus als Pill (Text + Symbol), optional freie Plätze |
| `x-wr.adventure-date` | Terminformat mit Wochentag, Uhrzeit und Ende (von `adventure-meta` und Faktenlisten genutzt) |
| `x-wr.own-booking` | Eigene Anmeldung im Abenteuer-Detail mit Aktionen als Text-Buttons |
| `x-wr.booking-blocked-notice` | Sichtbarer Grund, warum „Anmelden“ gesperrt ist (Profil/Spieler fehlt) |
| `x-wr.player-card` | Spielerkarte: Papyrus-Textur im Kopf, Alter, Abenteuer, aktiver Held |

## Vorher / Nachher

| Bereich | Vorher | Nachher |
|---|---|---|
| Markup | 570 Zeilen, Mobil- und Desktop-Blöcke doppelt (`sm:hidden` / `hidden sm:block`) | eine Ausgabe, responsiv über CSS |
| Begrüßung | Header-Leiste in Uncial Antiqua | Überschriftzeile + H1 in Cinzel Decorative + Einleitungstext |
| Nächstes Abenteuer | helle Karte, ganze Karte verlinkt | Quest-Banner mit Motiv, Waldgrün-Verlauf, Goldrahmen, eigener Button |
| Meine Anmeldungen | Status nur über Farbe/Häkchen | nächstes Abenteuer als Banner mit Countdown; Status als Text mit Symbol |
| Termin | nur Datum | Wochentag, Uhrzeit und Ende („Sa, 14.03., 10:00 Uhr – So, 15.03.2026, 15:00 Uhr“) |
| Helden | Textliste der Klassen, ganz unten | Heldenkarte mit Klassenbannern, Erfahrungspunkten, Fortschritts-Chips; Platzhalter |
| Kacheln | nur ab 640 px, Wackel-Hover | auf allen Größen, mobil zweispaltig kompakt; dezenter Hover |
| Primärbuttons | Fomantic, Gold-Verlauf | `wr-btn--primary` Waldgrün/weiß, mind. 44 px hoch |
| Fokus | uneinheitlich (amber-600) | einheitlich `#245F93`, 3 px; gezielte Fokusführung nach dem Schließen von Hinweisen |
| Onboarding | ausgegraute Schritte | helle Plakette + „nach Schritt 1“, ausblendbar, Eltern-Infos im Du |

## Ergebnis der Prüfung durch die Reviewer (Paket A)

- Status-Pills „bestätigt“/„angemeldet“ waren nur über die Farbe unterscheidbar (1,00:1) → Text + Symbol.
- Quest-Banner mobil: Text über hellem Himmel (bis 1,2:1) → Verlauf mit mind. 0,86 Deckkraft.
- `opacity-60` im Onboarding (2,8:1) → entfernt.
- Fokus verschwand hinter der Bottom-Nav → `scroll-padding-bottom` unter 640 px.
- Abgeschnittene Heldennamen und Orte → Umbruch an Wortgrenzen/Silbentrennung.
- Mobile Seite rund 3.000 px lang → kompakte Kacheln.
- Überschriftzeile/Einleitung knapp unter 4,5:1 → `#5f4823` / `#5a4636`.
- Texte: „Waldrittern“, „Nein danke“, Bürokrat erklärt, falscher Begriff „NSC“ entfernt,
  „Erfahrungspunkte (EP)“, Eltern-Infos im Du, doppelter Titel „Deine Abenteuerwelt“.
- MOTD-Schließen als echter Button (44 px, Enter/Leertaste).

## Änderungen außerhalb des Dashboards

- `html { scroll-padding-bottom: 5rem }` unter 640 px (in `waldritter-v1.css`, wirkt global, harmlos).
- `Hero::getSkillsCountAttribute()` nutzt einen per `withCount('skills')` geladenen Wert.
- Ersatzbild für Helden ohne Foto ist jetzt `heroes_db_v2.jpg` (Heldenliste, Details, öffentliche Ansicht).
- Kein Deployment: In Produktion liegen die neuen Bilder bereits, der Code verweist dort noch auf die alten.

## Bewusst unverändert

- Routen, Berechtigungen, Datenbank. Der `DashboardController` lädt nur zusätzliche
  Zählwerte (`withCount`) und die eigenen Spieler ohne Helden.
- `dashboard/_motd.blade.php`: Inline-Style der neutralen Variante bleibt (`MotdTest`).
- `dashboard/_adventure_status.blade.php`: Tailwind-Klassen bleiben (Tests); der Kontrast von
  amber-600 wird innerhalb von `.wr-status-chip` angehoben.
- Newsletter-Banner behält den Inline-Hintergrund, weil der Datensparmodus
  `[style*="background-image"]` ausblendet.
- Der Datenbank-Status „Anmeldung offen“ heißt nur auf dem Dashboard „Anmeldung möglich“.
  Abenteuerliste und Verwaltung zeigen weiter „Anmeldung offen“.

## Entfallen

- Der mobile Leerzustand („Herzlich willkommen!“ + zwei Buttons). Die Kacheln sind jetzt
  auch auf dem Smartphone sichtbar und übernehmen diese Navigation.

## Prüfung

- `npm run build` ohne Fehler, PHPStan und Pint für geänderte PHP-Dateien sauber.
- Komplette Testsuite grün (1024 Tests), nur gegen die `_test`-Datenbank.
  Neu: `DashboardBookedAdventureTest`, `DashboardHeroProgressTest`.
- Komponenten probehalber mit Daten der Entwicklungsdatenbank gerendert (nur lesend).
- Visuelle Abnahme durch den Auftraggeber im Browser (19./20.11.2026), u. a. 400–510 px.
- Keine automatisierten Screenshots: auf dem Entwicklungsserver ist kein Chrome/Chromium installiert.

## Offene Punkte

1. **Backlog-Tickets aus dem Review:**
   - ~~UI-46 Navigation und Bottom-Nav barrierefrei~~ – erledigt mit G1 (`2022982`)
   - UI-47 Seitentitel pro Seite
   - UI-48 Basis-Schriftgröße relativ statt 17,5 px
   - ADM-11 Alt-Text für MOTD-Bilder
   - PUB-13 Heldenbilder nicht ohne Login abrufbar
   - AUTH-15 Newsletter-Abo nur durch Volljährige/Erziehungsberechtigte
   - AUTH-16 Gespeicherten Einwilligungstext anzeigen
2. ~~**Navigation** auf Waldgrün umstellen~~ – erledigt mit G1 (`2022982`).
3. ~~**Globale Token-Migration** und Primärbuttons in Waldgrün~~ – erledigt mit G2 (`a968069`).
4. **Tests unabhängiger von CSS-Klassen machen** (`DashboardNextAdventureTest`, `MotdTest`), damit
   `_adventure_status` und `_motd` vollständig auf Tokens umgestellt werden können.
5. **Bildgröße:** `abenteuer_v3.jpg` (350 KB, 1254 × 1254 px) ließe sich auf ca. 600 px verkleinern
   (`heroes_db_v2.jpg` ist erledigt: 600 px, 91 KB).
6. **Klassenfarben in der Datenbank:** `hero_classes.ribbon_color` ist überall leer. Die
   Designfarben gelten bisher nur per CSS. Ob sie in die Datenbank übernommen werden sollen,
   ist noch nicht entschieden.
7. **Heldenarchiv-Kachel** aus der Designvorlage: Dafür gibt es noch keine Funktion bzw. Route.
8. **Begriff „Anmeldung offen“** (Datenbank-Status) erscheint noch in den Gruppenüberschriften der
   Abenteuerliste, im Block „Organisation“ des Abenteuer-Details und in der Verwaltung; die Pills
   sagen bereits „Anmeldung möglich“. Ggf. Status-Bezeichnung in der Datenbank angleichen.
9. **Onboarding wieder einblenden:** Einmal ausgeblendet, erscheint es erst nach dem Löschen
   der Browserdaten wieder. Bei Bedarf einen Link im Profil ergänzen.
10. ~~**Reiter „Verwalten“ im Heldenbuch** und die Verwaltung des Abenteuers~~ – erledigt mit V2–V4.
11. **Modal-Breite:** Falls die Reiterleiste im Heldenbuch-Modal umbricht, Modal-Breite anpassen.
12. ~~**Ungenutzte CSS-Regeln** `wr-verwalten-*`~~ – entfernt (`.siegel-input` bleibt).
13. ~~**Fertigkeiten-Vollseite fehlt**~~ – `admin/skills/edit` und `create` ergänzt; Symbol-Upload lädt
    dort die Seite neu.
14. **Unzuverlässiger Test:** `PublicHeroProfileTest` schlägt gelegentlich fehl, weil zufällige
    Faker-Namen den geprüften Text („Max“) enthalten können.
15. **Deployment** (nur nach Freigabe): In Produktion fehlen u. a. die Migration
    `adventures.asks_vegetarian` und neue Bilder wie `wewantyou_v2.jpg`.

## Weitere umgestellte Seiten

### Abenteuerliste (`/adventures`)

| Commit | Inhalt |
|---|---|
| `41e4faf` | Komponenten `adventure-card`, `adventure-status`; CSS Werkzeugleiste, Umschalter, Kartenraster |
| `a3a19e3` | Umbau Liste und Leerzustand, `AdventureListTest` |
| `c5c0840` | Eigene Anmeldungen an der Karte (Controller lädt nur Buchungen eigener Spieler) |

- Eine Kartenausgabe statt Mobil-Karten plus Desktop-Tabelle (vorher vier fast gleiche Blöcke).
- Ansicht („Nach Status“, „Nächstes zuerst“, „Letztes zuerst“) als Links mit `aria-current` statt Fomantic-Dropdown.
- Platzangabe/Warteliste nur bei offener Anmeldung (vorher auch bei abgeschlossenen/abgesagten Abenteuern).
- Vereinfachung: Wartende bei freien Plätzen (Wartelistenmodus) werden in der Liste nicht berücksichtigt; die Detailseite ist maßgeblich.
- `max_player = 0` gilt in der Buchungslogik als „voll“; dann keine Platzangabe (nicht „unbegrenzt“).

### Abenteuer-Detail (Seite und Modal)

| Commit | Inhalt |
|---|---|
| `0c22cf5` | Komponenten `own-booking`, `booking-blocked-notice`; CSS Reiter, Akkordeon, Faktenliste, Hinweis, Button-Zustände |
| `eb08b94` | Umbau `show`, `_detail`, `_event_info`, neue Teilansicht `_own_bookings`, `AdventureDetailTest` |

- `_detail` hat einen eigenen `.wr-v1`-Wrapper, damit auch das globale Modal im neuen Design erscheint.
- „Das Wichtigste“ (Termin, Ort mit Karte, Beitrag/kostenlos, Alter, Plätze, Anmeldung) vor „Organisation“.
- Eigene Anmeldungen als Karten mit Text-Buttons; **`_bookings` (Verwaltung) unverändert**.
- Sperrgrund für „Anmelden“ als sichtbarer Hinweis statt Tooltip.

### Heldenbuch (`/heroes/{id}` und Modal)

| Commit | Inhalt |
|---|---|
| `c9d52e2` | H1: Seitenkopf, Übersicht (`heroes/partials/_overview`), Schatzkiste, öffentliches Profil |
| `9ecbbff` | H2: Fertigkeiten (`_skills`, `_skill_columns`) mit zugänglichen Zuständen |
| `42ef96e` | Breitere Seite, kompaktere Reiter (eine Zeile auch mit Zählern) |
| `2fea8ac` | H3: Abenteuer, EP-Verlauf, Galerie (`_adventures`, `_ep`, `_gallery`) |

- `_detail.blade.php` von 1046 auf 277 Zeilen: Jeder Bereich ist nur noch einmal geschrieben und wird
  für Akkordeon (Handy) und Reiter (Desktop) eingebunden. `$ctx` (`m`/`d`) hält IDs eindeutig.
- „Foto ändern“ jetzt auch auf dem Handy; Galerie-Löschen fragt überall nach.
- Fertigkeiten: Marker mit zugänglichem Namen, Schloss-Symbol für „gesperrt“, Legende; alle Lern-Auslöser
  sind echte Buttons. Farben, Zustände und `data-skill-*`-Attribute unverändert.
- Reiter „Verwalten“ nur im neuen Rahmen, Inhalt folgt mit den Verwaltungsansichten.

### Spielerseiten (`/players` und Spieler-Detail)

| Commit | Inhalt |
|---|---|
| `7e3b8dd` | P1: Spielerliste, Komponente `player-card` |
| `c36bd5b` | P2: Spieler-Detail mit Teilansichten `players/partials/*` |
| `87156d1` | Bild `wewantyou_v2.jpg` für „Neuer Spieler“ |

- Papyrus-Hintergrund (`player_background.png`) nur noch als Textur im Kartenkopf.
- Karte zeigt Alter, „X Abenteuer erlebt“ und den aktiven Helden; „Erstellt“/„Geschlecht“ nur im Detail.
- Avatar-Upload unter dem Avatar in „Allgemeines“ (auch auf dem Handy); Reiter „Avatar“ entfallen.
- Aktiver Held wählbar über eigenen Button, getrennt vom Link ins Heldenbuch.
- Reiter „Buchungen“ heißt „Fotoerlaubnis“ (`data-tab="p-buchungen"` unverändert).

### Formulare

| Commit | Inhalt |
|---|---|
| `6d7fbc4` | F1: Formularregeln in `.wr-v1`, Abenteuer-Anmeldung in Abschnitten |
| `1103ed3`, `9cbcf1c` | F2: Spielerformular mit „Wen legst du an?“ oben; Vorauswahl beim Bearbeiten |
| `c40068b` | F3: Gast, Gruppe, Anmeldung bearbeiten; gemeinsame Kurzinfo `bookings/_summary` |
| `38f71d0` | Event-Einstellung „Nach vegetarischem Essen fragen“; Gast mit Leih-Tunika, Leih-Waffe, NSC |

- Formularregeln greifen für Fomantic-`ui form` und Breeze-Felder innerhalb von `.wr-v1`
  (Felder 44 px, Fokus `#245F93`, `wr-fieldset`, `wr-check` mit großer Klickfläche).
- Feldnamen, IDs und `.field`-Struktur bleiben, weil `heldenregister.js` darauf zugreift.
- NSC heißt korrekt „Nichtspielercharakter“; Fotoerlaubnis mit Hinweis auf den Widerruf im Spielerprofil.
- Spielerformular: Auswahl „Mich selbst / Mein Kind“ (Feld `self` = 1/0); Einwilligung Art. 8 nur bei „Mein Kind“.
- Neue Spalte `adventures.asks_vegetarian` (Standard aus, bestehende Events per Migration auf an).
  Snapshot vorher: `storage/backups/larp_heldenregister_dev_vor-asks-vegetarian.sql.gz`.
- **Bitte inhaltlich prüfen:** Formulierungen zu Leih-Tunika/Leih-Waffe und zur Fotoerlaubnis sind neutral gehalten.

### Heldenliste und Heldenformular (Team)

| Commit | Inhalt |
|---|---|
| `1397f39` | Heldenliste: Seitenkopf, Filter mit Labels, Trefferzahl als Live-Region, `wr-table`, Handy-Karten |
| `f886076` | Heldenformular in Abschnitten; Bearbeiten-Modal mit Klassenbannern; Löschen im Gefahrenbereich |

- Charaktername ist ein echter Link; Zeilen ohne `role="button"`.
- Status als Pill (aktiv / inaktiv / verschollen) statt abgeblendeter Zeile.
- Korrektur-Hinweis im Formular sichtbar statt als Tooltip.

## Globale Umstellung

| Schritt | Commit | Inhalt |
|---|---|---|
| G1 | `2022982` | Navigation oben und Bottom-Nav in Waldgrün, barrierefrei (UI-46) |
| G2 | `a968069` | Tokens, Primärbuttons und Fokus global |

- **G1:** Aktive Punkte mit goldener Unterstreichung bzw. goldenem Balken und `aria-current`;
  Klickflächen 44 px; Glocke mit zugänglichem Namen und Anzahl; Dropdowns schließen mit Escape,
  Fokus zurück zum Auslöser. „Mehr“ ist ein echter Dialog (Titel, Fokus hinein/zurück, Tab-Falle).
  Der Footer liegt auf dem Handy nicht mehr hinter der unteren Leiste.
- **G2:** `--wr-ink` (`#35281E`) und `--wr-gold` (`#B58A3A`) gelten global, die Sonderregel in `.wr-v1`
  ist entfernt. Fomantic- und Breeze-Primärbuttons in Waldgrün mit weißer Schrift, Rundung 9 px,
  kein Vergrößern beim Hover. Fokus überall `#245F93`. Link-Hover in Waldgrün.

## Verwaltungsansichten

| Schritt | Commit | Inhalt |
|---|---|---|
| V1 | `333a660` | Gemeinsame Grundlage für Admin-Seiten, Verwaltungsübersicht |
| V2a | `6ffb281` | Abenteuer verwalten: Liste |
| V2b | `a88ece0` | Abenteuer verwalten: Modal und Vollseite (`_manage`) |
| V3 | `ae732d9` | Inspektor für Anmeldungen und Teamer |
| V4 | `e661269` | Heldenbuch „Verwalten“, Handy- und Desktop-Version zusammengeführt |
| I1 | `8485ee0` | Inspektor für Admin-Modalansichten; Admin-Spielerliste |
| I2 | `7094aee` | Neun Stammdaten-Listen im Inspektor |
| I3 | `5291f50` | Portal-Nutzer, Fertigkeiten und Gruppen im Inspektor |

- **V1:** Noch nicht einzeln umgestellte Seiten bekommen einen ruhigen Seitenkopf: Die erste `<h2>`
  im Header-Slot wird zur `<h1>` (`wr-page-header`), Cinzel statt Uncial. Nur innerhalb von
  `wr-admin-area` erscheinen Admin-Tabellen (`min-w-full divide-y`) in `wr-table`-Optik, Panels auf
  Papier und Amber-Kästen als Hinweisbox. Die rote Signalkennzeichnung des Admin-Bereichs bleibt.
- **V2:** Belegung als Text-Pills (Plätze, Warteliste, Teamer) statt Farben mit Tooltip. Reiter
  „Anmeldungen (1 aktiv · 0 Warteliste · 0 storniert)“ als Text. Status und Beitrag in der
  Anmeldetabelle als Pills statt Emojis. Löschen mit Rückfrage bzw. sichtbarem Sperrgrund.
- **V3:** Statuskopf und Beitrag als Pills; beschriftete Aktions-Buttons; Formulare unverändert.
- **V4:** „Verwalten“ nur noch einmal geschrieben (`heroes/partials/_manage`); Helden-Siegel mit
  Label, Hinweis und Fehlermeldung als `role="alert"`; Status im Spiel als Pill.
  `heroes/_detail.blade.php` hat jetzt 131 Zeilen (anfangs 1046).

### Inspektor in Admin-Listen

Admin-Listen öffnen eine Zeile im seitlichen Inspektor statt in einem eigenen Modal.

| Liste | Im Inspektor | Bleibt in der Zeile |
|---|---|---|
| Spieler | Kurzinfo, Anschrift, Betreuer, Matrix, Löschen (auch „Trotzdem löschen“) | Profil (Modal), Wiederherstellen |
| Helden-Klassen, Perlenfarben, EP-Buchungsarten, Orte, Kategorien, Auftraggeber, Teilnahme-Rollen, Event-Status, Taskmanager | Bearbeiten, Löschen im Gefahrenbereich (bei Helden-Klassen und Taskmanager kein Löschen) | Anforderungen der Auftraggeber |
| Portal-Nutzer | Rollen, Aktivierung, Löschen im Aktionsbereich | Profil (Seite), Wiederherstellen |
| Fertigkeiten | Bearbeiten, Symbol, Löschen im Gefahrenbereich | Helden (Modal) |
| Gruppen | Bearbeiten, Löschen mit Hinweis auf ausgetragene Helden | Details (Seite), Mitglieder (Modal) |

- **Bedienung:** Klick auf die Zeile oder Enter/Leertaste öffnet; Esc oder Klick daneben schließt,
  der Fokus kehrt zur Zeile zurück. Die geöffnete Zeile ist markiert (`.is-inspected`).
- **Technik:** `tr[data-inspector-url]`; Stammdaten laden die vorhandene Bearbeiten-Ansicht mit
  `?inspector=1`. `adaptModalViewForInspector()` übernimmt den Titel aus `data-modal-title`, zeigt
  `data-modal-actions` als Leiste unten und sendet alle Formulare per AJAX (`data-inspector-form`).
  Nach dem Speichern werden Inspektor und Liste neu geladen; `data-inspector-close-on-success`
  schließt den Inspektor nach dem Löschen.
- **Gefahrenbereich:** `x-admin.inspector-delete` erscheint nur mit `?inspector=1`, nicht im
  bisherigen Bearbeiten-Modal. Gesperrtes Löschen zeigt den Grund sichtbar statt des Buttons.
- **„Neu …“** öffnet weiterhin das Modal.
- Deaktivierte bzw. gelöschte Einträge werden nicht mehr abgeblendet; der Status steht als Text oder Pill.
- Neue Route `admin.players.inspector`. Spieler-Löschen und Matrix-Speichern/-Entziehen antworten bei
  AJAX mit JSON, sonst unverändert.

### Gemeinsame Regeln aus diesen Seiten

- Status, Zustände und Beträge immer als Text mit Symbol, nie nur Farbe oder Tooltip.
- Symbole immer gefüllt (keine Fomantic-`outline`-Varianten).
- Ansichten, die auch im Modal laden, bekommen einen eigenen `.wr-v1`-Wrapper.
- Fomantic-Reiter/Akkordeon werden nur innerhalb von `.wr-v1` umgestaltet; Mechanik und `data-tab` bleiben.
- Fomantic-Checkboxen: Label ohne `for`-Attribut (sonst doppeltes Umschalten).
- Versteckte Datei-Felder: `sr-only wr-file-input` vor dem Label, damit der Tastaturfokus sichtbar ist.
- Nach Uploads per Zuschnitt-Dialog: Callback lädt offenes Modal neu, sonst die Seite (`window.location.reload()`).
- Dateien, die auch im Modal laden: Inline-Skripte sofort ausführen, nicht auf `DOMContentLoaded` warten.
- Klickbare Tabellenzeilen ohne `role="button"`: entweder echter Link in der Zeile oder Inspektor-Zeile
  mit `tabindex="0"` und zugänglichem Namen (`aria-label`).
- Löschen gehört in den Inspektor bzw. die Detailansicht, nicht in die Listenzeile.

### Entwicklungsumgebung

- `public/storage` fehlte (404 für hochgeladene Bilder); mit `php artisan storage:link` angelegt (nicht versioniert).
- Bilder: `abenteuer_v3.jpg`, `heroes_db_v2.jpg` (600 px) ersetzen die alten Motive bzw. das Helden-Ersatzbild.

## Stand der Prüfung

- Komplette Testsuite grün (1114 Tests, 23.11.2026), PHPStan ohne Fehler; nur gegen die `_test`-Datenbank.
- Neue Tests seit dem Pilot u. a.: `AdventureListTest`, `AdventureDetailTest`, `HeroDetailTest`,
  `PlayerListTest`, `PlayerDetailWrTest`, `PlayerFormWrTest`, `BookingFormWrTest`,
  `AdventureVegetarianSettingTest`, `HeroListWrTest`, `HeroFormWrTest`, `NavigationWrTest`,
  `AdminOverviewWrTest`, `AdventureManageListWrTest`, `AdventureManageModalWrTest`, `InspectorWrTest`,
  `HeroManageTabWrTest`, `AdminPlayerInspectorTest`, `AdminInspectorListsTest`.

## Nächste Schritte (Reihenfolge laut Designsystem)

Die Seiten- und Verwaltungsansichten sind umgestellt. Bewusst nur mit der Grundlage aus V1:
Startseiten-Nachricht, Newsletter, Umfragen, Datenpannen, Verarbeitungstätigkeiten (große Editoren),
Rollen und Rechte (Matrix), Einstellungen, Audit-Log, Ausweise.

Als Nächstes: Regression über alle Seiten (Desktop, Tablet, Handy) und die offenen Punkte oben.
