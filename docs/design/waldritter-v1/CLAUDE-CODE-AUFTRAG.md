# Auftrag für Claude Code: Waldritter Designsystem implementieren

Lies zuerst `CLAUDE.md`, `docs/coding-standards.md`, `docs/adr/0001-frontend-architektur-mobile-first.md` und die beigefügten `DESIGN-SYSTEM.md`, `design-tokens.css` sowie `beispielansichten.html`.

**Ziel:** Bestehendes Heldenregister konsistent zum Design „Die lebendige Waldritter-Chronik“ weiterentwickeln. Verwende die Muster als Inspirationsvorlage; kein 1:1-Austausch bestehender Inhalte. Laravel 12, Blade, Tailwind, Fomantic-UI und Vite bleiben erhalten. Die vorhandenen lokalen Fonts und unter `public/images/` vorhandenen Bilder verwenden; keine externen Fonts, keine neue SPA und keine neuen unnötigen Abhängigkeiten.

**Vorgehen (verbindlich):**
1. Analysiere aktuelle Styles/Layouts und finde Fomantic-/Tailwind-/Legacy-Kollisionen. Ermittle alle Stellen mit `--wr-*` Tokens. Prüfe die in der DB/Klassenkonfiguration verwendeten Farben und die existierenden Klassenbanner.
2. Beschreibe zuerst den Plan inklusive konkret betroffener Dateien und Risiken. **Vor Änderungen Freigabe abwarten** (bestehende `CLAUDE.md`-Regel).
3. Führe anschließend Design-Tokens und wiederverwendbare Blade/CSS-Komponenten ein. Vorhandene Funktionalität, Rollenrechte, Formulare, AJAX-Modals und mobile Navigation erhalten. CSS nicht blind an globale `.ui.*`-Selektoren anflanschen.
4. Entwickle zuerst nur einen repräsentativen Dashboard-Piloten und zeige Desktop-/Mobil-Screenshots bzw. dokumentierte Sichtprüfung, erst dann die weiteren Seiten.
5. Prüfe Barrierefreiheit, Lesbarkeit, Tastaturbedienung, 320/390/768/1280px, Kontrast, reduziert animierte UI, Button-Loading, Modals/Bottom-Sheets. Tests/Build nur in isolierter Entwicklungsumgebung.
6. Liefere eine Dateiliste, Vorher/Nachher-Notizen, offene Punkte und einen Git-Commit-Vorschlag.

**Schutz:** Keine Arbeit in `/var/www/heldenregister`, keine Produktions-Datenbank, keine destruktiven Migrationen, kein Deployment oder Push auf `main` ohne Freigabe. Nur `/home/laraveldev/apps/heldenregister` und Entwicklungsbranch. Keine echten Kinder-/Elterndaten in Screenshots.

**Abnahme:** Bestehende Buchungs-, Helden-, Fertigkeits- und Verwaltungsabläufe funktionieren unverändert; einheitliche Tokens, kontrastreiche Buttons, mobile Nutzbarkeit, keine Layout-Regressionen, keine unnötigen neuen Packages.
