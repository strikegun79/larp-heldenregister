# Waldritter Gießen Heldenregister – Designsystem (Konzept v1)

## Leitidee
„Die lebendige Waldritter-Chronik“: mittelalterlich, freundlich, warm und gut bedienbar. Zielgruppe: Kinder/Jugendliche, Eltern, Teamer und Administration. Fantasy-Dekor dient der Identität, nicht als Ersatz für Nutzbarkeit.

## Verifizierte Projektbasis
Repo: https://github.com/strikegun79/larp-heldenregister · Branch main · Stand 17.11.2026.
- Laravel 12, PHP/Blade, Tailwind 3, Fomantic UI, Vanilla-JS/Vite.
- `resources/css/app.css`: vorhandene Pergament-Basis `#e4cea5`, `paper.jpg`, lokale Fontsource-Schriften (EB Garamond, Cinzel Decorative, Uncial Antiqua, MedievalSharp), bestehende `--wr-*`-Variablen.
- `public/css/heldenregister.css`: spezifische Fertigkeitsbaum-Marker, modale Ansichten, mobile Bottom-Navigation.
- `public/images/`: originale Abenteuer-, Helden- und Verwaltungsmotive, Klassenbanner, Waldritter-Logo.
- `docs/adr/0001-frontend-architektur-mobile-first.md`: Responsive Blade + Fomantic/Tailwind, kein React/Vue-Neubau.
- `CLAUDE.md`: Entwicklungsbranch, Entwicklungsordner und Freigabeprozess sind zwingend zu respektieren.

## Tokens
Siehe `design-tokens.css`. Primär Waldgrün `#284D38` (Navigation/CTA), Pergament `#F4E8CC` (Grund), Papier `#FFF9EC` (Karten), Tinte `#35281E`, Antikgold `#B58A3A` (nur Deko/Highlights), Leder `#765039`, Moos `#587749`, Warnrot `#A4483B`.

Wichtig: Dies sind *neue Zielwerte*, nicht die derzeitigen Ist-Farben; CSS-Variablen in `app.css` sind teils anders belegt und müssen bewusst migriert werden. `--wr-ink` z. B. aktuell `#5a3a22` statt dem vorgeschlagenen `#35281E`.

## Typografie
- Fließtext/Formulare: EB Garamond für redaktionelle Inhalte; für datenlastige Formulare/Tabellen wahlweise solide System-Sans.
- Hauptüberschriften: Cinzel Decorative; nicht in langen Fließtexten.
- Uncial Antiqua/MedievalSharp sparsam in Plaketten und atmosphärischen Titeln.
- Körpertext mind. 16px, line-height 1.5; Labels gut lesbar, kein Text in dekorativen Bildern einbrennen.

## Komponenten
- Hauptnavigation: Waldgrün mit hellem Text, dezenter goldener aktiver Kennzeichnung.
- Hero-/Quest-Banner: echtes Motiv, dunkler Verlauf hinter Text, CTA mit hohem Kontrast.
- Karten: elfenbeinfarben, 1px warmer Rand, 12–14px Rundung, zurückhaltender Schatten; ganze Karte nur anklickbar, wenn eindeutig.
- Primärbutton: Waldgrün mit weißem Text; sekundär: Papierfläche, brauner Rand. Kein Gold als alleinige Info- oder Button-Basis.
- Formulare: klare Labels, Feldhöhe mindestens 44px; Fehler zusätzlich als Text, nicht nur rot.
- Heldenkarte: Charaktername, Klasse, Fortschritt, nächste Aktion; vorhandene Klassensymbole/Banner nach Möglichkeit erhalten.
- Fertigkeitsbaum: vorhandene Markerzustände unlearned/learned/locked beibehalten und durch Symbol sowie zugängliche Beschriftung ergänzen.
- Admin-Tabellen: weniger Dekoration, klare Spalten und Suche/Filter; auf Smartphones Card-Ansicht, wenn sinnvoll.

## Klassen
Krieger Rot `#B44343`; Magier Gelb `#D5A523`; Waldläufer Grün `#3F8156`; Heiler Blau `#386BA1`; Alchemist Orange `#C77330`. Diese Palette ist *vorgeschlagen*; Klassen-/Bannerzuordnungen sowie DB-Farbcodes vor Änderung prüfen. Nie ausschließlich Farbe als Klassenkennzeichnung verwenden.

## Responsive / Accessibility
Mobile zuerst, 320–390px prüfen; 44x44px Touch-Ziele; keine horizontalen Scrollbereiche außer bewusst scrollbaren Datentabellen; klare Tastatur-Fokusmarkierung (`#245F93`); AA-Kontrast nach WCAG 2.2 prüfen; `prefers-reduced-motion` berücksichtigen; Bilder `alt`-Text, nichttextliche Schmuckgrafiken dekorativ markieren. Keine personenbezogenen Echtdaten in Design-Demos.

## Umsetzungsreihenfolge
1. Inventory: Layouts/Views/Styles/Token-Vorkommen und vorhandene Bilder erfassen.
2. Gemeinsame Design-Tokens einführen; Legacy-Kompatibilität und Fomantic-Kaskade beachten.
3. Ein einziges Dashboard als Pilot, Desktop + Mobile; visuell abnehmen lassen.
4. Helden-/Abenteuerkarten, Listen, Formulare und Tabs angleichen.
5. Verwaltungsansichten mit Fokus auf Lesbarkeit und Effizienz anpassen.
6. Regression/Keyboard/Responsive/Contrast prüfen; keine Fachlogik ändern.

## Beispielgrafik
`beispielansichten.html` lokal im Browser öffnen, Layout passt sich an Mobile/Desktop an. Das ist eine Designstudie und kein Screenshot. Für Entwicklungsübergabe die ganze ZIP mitsamt `assets/` an Claude Code geben.
