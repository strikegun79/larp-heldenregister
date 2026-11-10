# Versionsverlauf

Alle wesentlichen Änderungen am Heldenregister werden hier festgehalten.
Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionsnummern folgen [Semantic Versioning](https://semver.org/lang/de/).

## [1.6.0] – 2026-11-10

### Neu
- **Versionsverlauf:** Diese Seite – alle Änderungen am Portal auf einen Blick, verlinkt im Seitenfuß.
- **Brans Tagebuch:** Neue Kachel auf der Startseite mit Erzählungen aus den Abenteuern.

## [1.5.0] – 2026-10-26

### Neu
- **Startseiten-Nachrichten:** Die Orga kann Nachrichten mit Banner-Bild, Link, Farbe und Zeitraum auf der Startseite anzeigen; Nachrichten lassen sich wegklicken. Verwaltung mit Verlauf, Duplizieren und anonymem Klickzähler.
- **Nächstes Abenteuer auf der Startseite:** Anzeige der freien Plätze (grün/gelb/rot) und des Anmeldestatus (offen, Warteliste, geschlossen).
- **Steckbrief:** Formatierter Text mit Editor (Schriftgröße, Farben, Listen), eigene Ansicht im Fenster, Anzeige auch im öffentlichen Profil und im PDF.
- **Heldenausweis zum Herunterladen:** Besitzer können den Ausweis ihres Helden als PDF laden – mit Name und angekreuzten Klassen.
- **Klassenspezifische Voraussetzungen:** Fertigkeiten können je Klasse unterschiedliche Voraussetzungen haben.
- **Öffentlicher Skilltree:** Fertigkeiten lassen sich per Klick im Info-Fenster ansehen.

### Geändert
- **Teilnehmer-PDF:** Querformat, mehr Platz für Unterschriften, Rolle mit Zahlungsstatus, stornierte Anmeldungen ausgeblendet, Seitenzahlen.
- **Check-in:** Kein Neuladen der Seite mehr, Sortierung nach Nachname, neue Spalte „Held“.
- **EP-Vergabe:** Bestätigungsdialog mit Übersicht; EP werden nur noch über die Heldenansicht gebucht.
- **Newsletter:** Versand wird gedrosselt, damit alle Mails zuverlässig ankommen.
- **Offline-Seite:** Weist auf eine mögliche Serverwartung hin.

### Behoben
- Telefonnummern im Belegungsreport (Excel) werden wieder korrekt als Text exportiert.
- Freie Plätze in der Abenteuer-Übersicht werden korrekt berechnet.

## [1.4.0] – 2026-09-30

### Neu
- **Newsletter:** Abonnieren im Profil, Newsletter-Erstellung mit Editor, Bildern und Tabellen, Versand an alle Abonnenten.
- **Feedback-Umfragen:** Umfragesystem für Teilnehmende, z. B. für die Jugendförderung.
- **Taskmanager:** Automatische Aufgaben je Abenteuer (Erinnerungsmails, Statuswechsel, EP-Vergabe, Wartelisten, Umfragen).
- **Teamer-Rollen:** Teilnahme-Rollen aus der Verwaltung, vollständiger Teamer-Ablauf und Verwaltungs-Dashboard für Projekt- und Spielleitung.
- **Stornierung:** Anmeldungen werden storniert statt gelöscht; Rücknahme einer Stornierung kann angefragt werden.
- **Teilnahmebedingungen:** Eigene Seite für die AGB.
- **Datensparmodus:** Weniger Bilder und Datenverbrauch auf Wunsch.
- **E-Mail an alle Teilnehmer** direkt aus der Abenteuer-Verwaltung.
- Anmeldebestätigungen können erneut versendet werden.

### Datenschutz
- Ausdrückliche Einwilligungen für Gesundheitsdaten und für Kinderprofile.
- Fotoerlaubnis je Anmeldung widerrufbar, mit Benachrichtigung der Orga.
- Verarbeitungsverzeichnis, Datenpannen-Protokoll und Audit-Log für Änderungen.
- Automatische Löschung abgelaufener Daten und von Teamer-Gesundheitsdaten.
- Datenexport nach Art. 20 DSGVO verbessert.

### Geändert
- Admin-Tabellen und Suchfelder sind auf dem Smartphone besser bedienbar.
- Anmeldungstabelle in der Verwaltung ist sortierbar.
- Wartelistenmodus je Abenteuer umschaltbar, Altersgrenzen für Abenteuer.

## [1.3.0] – 2026-08-27

### Neu
- **Warteliste:** Anmeldungen landen bei vollen Abenteuern auf der Warteliste, mit Benachrichtigung beim Nachrücken.
- **Bezahlung:** GiroCode-QR in der Anmeldebestätigung, Ermäßigungspreise und Verwendungszweck mit Projektkürzel.
- **Onboarding:** Fortschrittsbox für neue Nutzer auf der Startseite.
- **Gesundheitsdaten im Spielerprofil** mit Vorausfüllung bei der Anmeldung.
- **Willkommens-Mail** für neue Helden; Anmeldung durch die Orga möglich.
- Belegungsreport als Excel-Datei statt CSV.
- Google-Maps-Link im Abenteuer, wenn ein Ort hinterlegt ist.
- E-Mails im Waldritter-Design mit Zahlungsinformationen.

### Datenschutz
- Datenschutzerklärung, lokale Schriften, Einwilligungen und Kontolöschung.
- Datenexport und automatische Aufbewahrungsfristen.

### Geändert
- Abenteuer-Übersicht mit Gruppenansicht und Sortierung.
- Detailansichten von Helden aktualisieren sich nach EP- oder Fertigkeitsänderungen automatisch.

## [1.2.0] – 2026-07-07

### Neu
- **Benachrichtigungen:** Portal- und E-Mail-Benachrichtigungen bei Statusänderungen, einstellbar im Profil.
- **Helden-Galerie:** Bis zu vier Bilder je Held.
- **Heldensuche per Siegel** im Heldenregister.
- **Fertigkeiten:** Eigene Symbole und Anzeige, welche Helden eine Fertigkeit beherrschen.
- **Gruppen-Anmeldung** für Abenteuer.
- **Hilfe-Seite** mit Funktionsübersicht für Vorstand und Auftraggeber.
- Klassenbänder mit Farbe und Bild.

### Geändert
- Abenteuer können gelöscht werden, solange keine Abhängigkeiten bestehen.
- Abgesagte und abgeschlossene Abenteuer werden für Spieler ausgeblendet.

## [1.1.0] – 2026-06-30

### Neu
- **Öffentliches Heldenprofil:** Jeder Held erhält einen Code, ein öffentliches Profil mit QR-Code und kann gesucht werden – auf Wunsch unsichtbar.
- **Helden-Siegel / Ausweis:** Ausweise als PDF im Klappformat.
- **Gruppen:** Gruppen mit Mitgliedern, sichtbar im Helden-Detail.
- **App-Installation (PWA):** Das Portal lässt sich auf dem Smartphone wie eine App installieren.
- Fertigkeitsbaum mit Spaltenansicht nach Stufe; Voraussetzungen werden beim Lernen geprüft.

### Geändert
- **Mobile Ansicht:** Untere Navigationsleiste, Karten statt breiter Tabellen, Akkordeons statt Tabs, mobiles Dashboard.
- Helden- und Abenteuer-Details als eigene Seiten statt Fenster.
- Barrierefreiheit verbessert (Tastaturbedienung, Fokus, Screenreader).

### Technik
- Automatische Backups, Logging, Hintergrundverarbeitung für E-Mails, automatische Tests.

## [1.0.0] – 2026-06-18

### Neu
- **Umstieg auf Laravel:** Das bisherige Portal wurde vollständig auf eine neue technische Grundlage gestellt. Bestehende Daten und Funktionen wurden übernommen.
- **Benutzerverwaltung:** Registrierung mit Pflichtangaben, E-Mail-Bestätigung, Rollen und Rechte, Konto-Deaktivierung.
- **Helden und Spieler:** Verwaltung von Spielern und Helden, EP-Buchungen, Perlen- und Bändchenübersicht.
- **Fertigkeiten:** Fertigkeitenkatalog und -verwaltung je Klasse.
- **Abenteuer:** Anmeldung, Teamer-Anmeldung und -Einladung, Teilnehmerliste als PDF.
- **Administration:** Verwaltung von Gruppen, Status, Perlenfarben, Buchungsarten und Portal-Einstellungen; Audit-Log.
