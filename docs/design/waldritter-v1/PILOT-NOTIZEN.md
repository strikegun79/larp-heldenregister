# Pilot „Dashboard“ – Waldritter-Designsystem v1

Stand: 19.11.2026 · Branch `develop`

## Ergebnis

Das Dashboard (`/dashboard`) ist als erste Seite auf das Designsystem
„Die lebendige Waldritter-Chronik“ umgestellt. Alle anderen Seiten sind
unverändert, weil die neuen Werte für kollidierende Tokens nur innerhalb von `.wr-v1`
gelten und keine globalen Fomantic-Regeln (`.ui.*`) verändert wurden.

| Schritt | Commit | Inhalt |
|---|---|---|
| 1 | `94a9e39` | Tokens, `wr-*`-Komponentenklassen, Tailwind-Farben `wr.*` |
| 2 | `507223d` | Blade-Komponenten `x-wr.*` |
| 3 | `8f39780` | Umbau `dashboard.blade.php` |
| 4 | (siehe Git-Log) | Diese Notizen |

## Getroffene Entscheidungen

1. **Navigation** (oben und Bottom-Nav) im Pilot unverändert. Sie gilt für alle Seiten
   und wird in einem eigenen Schritt umgestellt.
2. **Anmeldungen / nächstes Abenteuer** erscheinen einheitlich nur mit `adventure.access`.
   Vorher wurden sie auf dem Smartphone ohne diese Prüfung angezeigt.
3. **Admin-Kennzahlen** stehen am Seitenende im Abschnitt „Verwaltung auf einen Blick“.
4. **Begrüßung** steht im Seiteninhalt (Überschriftzeile und H1). Die Header-Leiste entfällt auf dem Dashboard.

## Vorher / Nachher

| Bereich | Vorher | Nachher |
|---|---|---|
| Markup | 570 Zeilen, Mobil- und Desktop-Blöcke doppelt (`sm:hidden` / `hidden sm:block`) | ca. 220 Zeilen, eine Ausgabe, responsiv über CSS |
| Begrüßung | Header-Leiste in Uncial Antiqua | Überschriftzeile + H1 in Cinzel Decorative + Einleitungstext |
| Nächstes Abenteuer | helle Karte, ganze Karte verlinkt | Quest-Banner mit Motiv, Waldgrün-Verlauf, Goldrahmen, eigener Button |
| Meine Anmeldungen | Status nur über Farbe/Häkchen, Inline-Farben | Status-Pills mit Symbol, Titel und Screenreader-Text; Farben als CSS-Klassen |
| Helden | Textliste der Klassen, ganz unten | Heldenkarte mit Klassenbannern, EP und „Zum Heldenbuch“, vor den Kacheln |
| Kacheln | nur ab 640 px, Wackel-Hover | auf allen Größen; dezenter Hover, abschaltbar per `prefers-reduced-motion` |
| Primärbuttons | Fomantic, Gold-Verlauf | `wr-btn--primary` Waldgrün/weiß, mind. 44 px hoch |
| Fokus | uneinheitlich (amber-600) | einheitlich `#245F93`, 3 px |
| Onboarding | gleiche Logik | Schritt-Plaketten (Leder = offen, Moos = erledigt), Eltern-Aufklapper mit 44 px Klickfläche |

## Bewusst unverändert

- `DashboardController`, Routen, Berechtigungen, Datenbank.
- `dashboard/_motd.blade.php`: `MotdTest` prüft den Inline-Style der neutralen Variante.
- `dashboard/_adventure_status.blade.php`: Tests prüfen auf `text-green-700` / `text-amber-600` /
  `text-red-700`. Der zu schwache Kontrast von amber-600 wird nur innerhalb von `.wr-status-chip`
  per CSS angehoben.
- Newsletter-Banner behält den Inline-Hintergrund, weil der Datensparmodus
  `[style*="background-image"]` ausblendet.
- Brans Tagebuch öffnet weiterhin im selben Tab (wie vorher).

## Entfallen

- Der mobile Leerzustand („Herzlich willkommen!“ + zwei Buttons). Die Kacheln sind jetzt
  auch auf dem Smartphone sichtbar und übernehmen diese Navigation.

## Prüfung

- `npm run build` ohne Fehler.
- Dashboard-bezogene Tests (63) und komplette Testsuite grün, nur gegen die `_test`-Datenbank.
- Alle `x-wr.*`-Komponenten probehalber mit Daten der Entwicklungsdatenbank gerendert
  (nur lesend). Alle 5 Klassen finden Banner und CSS-Klasse (`wr-class--waldlaeufer` usw.).
- Visuelle Abnahme durch den Auftraggeber im Browser (19.11.2026).
- Keine automatisierten Screenshots: auf dem Entwicklungsserver ist kein Chrome/Chromium installiert.

## Offene Punkte

1. **Gezielte Prüfung auf 320/390/768/1280 px** mit Tastatur, Kontrastmessung und
   `prefers-reduced-motion` ist noch nicht dokumentiert. Empfehlung: `accessibility-reviewer`
   und `child-experience-reviewer` auf das Dashboard ansetzen.
2. **Navigation** auf Waldgrün umstellen (globaler Schritt, alle Seiten).
3. **Globale Token-Migration** von `--wr-ink` (`#5a3a22` → `#35281E`) und `--wr-gold`
   (`#c8962a` → `#B58A3A`) sowie `.ui.primary.button` von Gold auf Waldgrün. Bis dahin
   haben das Dashboard grüne und alle anderen Seiten goldene Primärbuttons.
4. **Tests unabhängiger von CSS-Klassen machen** (`DashboardNextAdventureTest`, `MotdTest`), damit
   `_adventure_status` und `_motd` vollständig auf Tokens umgestellt werden können.
5. **`heroes_db.jpg` (768 KB)** für die Kachel verkleinern (z. B. 800 px breit, WebP/JPEG ~80 KB).
6. **Klassenfarben in der Datenbank:** `hero_classes.ribbon_color` ist überall leer. Die
   Designfarben gelten bisher nur per CSS. Ob sie in die Datenbank übernommen werden sollen,
   ist noch nicht entschieden.
7. **Heldenarchiv-Kachel** aus der Designvorlage: Dafür gibt es noch keine Funktion bzw. Route.

## Nächste Seiten (Reihenfolge laut Designsystem)

Helden- und Abenteuerkarten, Listen, Formulare, Tabs → Verwaltungsansichten → Regression.
Die Komponenten `x-wr.card`, `x-wr.hero-card`, `x-wr.booking-card`, `x-wr.class-badge`,
`x-wr.adventure-meta`, `x-wr.stat-tile` und `x-wr.quest-banner` sind dafür vorbereitet.
