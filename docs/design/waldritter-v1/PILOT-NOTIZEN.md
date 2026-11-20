# Pilot „Dashboard“ – Waldritter-Designsystem v1

Stand: 20.11.2026 · Branch `develop`

## Ergebnis

Das Dashboard (`/dashboard`) ist als erste Seite auf das Designsystem
„Die lebendige Waldritter-Chronik“ umgestellt und anschließend durch
`accessibility-reviewer` und `child-experience-reviewer` geprüft worden.
Alle Befunde, die das Dashboard selbst betreffen, sind umgesetzt.

Alle anderen Seiten sind optisch unverändert, weil die neuen Werte für kollidierende
Tokens nur innerhalb von `.wr-v1` gelten und keine globalen Fomantic-Regeln (`.ui.*`)
verändert wurden. Ausnahmen mit Wirkung über das Dashboard hinaus: siehe
„Änderungen außerhalb des Dashboards“.

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
   - UI-46 Navigation und Bottom-Nav barrierefrei (inkl. verdecktem Footer auf dem Smartphone)
   - UI-47 Seitentitel pro Seite
   - UI-48 Basis-Schriftgröße relativ statt 17,5 px
   - ADM-11 Alt-Text für MOTD-Bilder
   - PUB-13 Heldenbilder nicht ohne Login abrufbar
   - AUTH-15 Newsletter-Abo nur durch Volljährige/Erziehungsberechtigte
   - AUTH-16 Gespeicherten Einwilligungstext anzeigen
2. **Navigation** auf Waldgrün umstellen (globaler Schritt, alle Seiten).
3. **Globale Token-Migration** von `--wr-ink` (`#5a3a22` → `#35281E`) und `--wr-gold`
   (`#c8962a` → `#B58A3A`) sowie `.ui.primary.button` von Gold auf Waldgrün. Bis dahin
   haben das Dashboard grüne und alle anderen Seiten goldene Primärbuttons.
4. **Tests unabhängiger von CSS-Klassen machen** (`DashboardNextAdventureTest`, `MotdTest`), damit
   `_adventure_status` und `_motd` vollständig auf Tokens umgestellt werden können.
5. **Bildgrößen:** `abenteuer_v3.jpg` (350 KB) und `heroes_db_v2.jpg` (315 KB) haben
   1254 × 1254 px; mit ca. 600 px Breite wären sie unter 100 KB.
6. **Klassenfarben in der Datenbank:** `hero_classes.ribbon_color` ist überall leer. Die
   Designfarben gelten bisher nur per CSS. Ob sie in die Datenbank übernommen werden sollen,
   ist noch nicht entschieden.
7. **Heldenarchiv-Kachel** aus der Designvorlage: Dafür gibt es noch keine Funktion bzw. Route.
8. **Begriff „Anmeldung offen“** in Abenteuerliste und Verwaltung beim Umbau dieser Seiten
   an „Anmeldung möglich“ angleichen.
9. **Onboarding wieder einblenden:** Einmal ausgeblendet, erscheint es erst nach dem Löschen
   der Browserdaten wieder. Bei Bedarf einen Link im Profil ergänzen.

## Nächste Seiten (Reihenfolge laut Designsystem)

Helden- und Abenteuerkarten, Listen, Formulare, Tabs → Verwaltungsansichten → Regression.
Die Komponenten aus der Tabelle oben sind dafür vorbereitet.
