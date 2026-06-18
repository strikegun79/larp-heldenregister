# Backlog · Auth, Profil & Passwörter (AUTH)

Authentifizierung, Registrierung, Profilpflege, Passwort-Themen.

## Inventar (✅)

### AUTH-01 · Breeze-Auth-Scaffolding · ⏱ 3h · ✅
Login, Registrierung, Logout, „Passwort vergessen", Passwortbestätigung.

### AUTH-02 · E-Mail-Verifizierung als Konto-Aktivierung · ⏱ 2h · ✅
`User implements MustVerifyEmail`; Verifizierungsmail beim Registrieren.

### AUTH-03 · Neue Nutzer erhalten Rolle „Teilnehmer" · ⏱ 2h · ✅
Listener `AssignParticipantRole` am `Registered`-Event (defensiv).

## Offen (🔲)

### AUTH-04 · Profilformular um `lastname` + `phone` erweitern · ⏱ 3h · ✅
**Beschreibung:** Die `users`-Tabelle hat `lastname` und `phone` (aus Legacy),
das Breeze-Profilformular pflegt sie aber nicht.
**Akzeptanzkriterien:**
- [x] `ProfileUpdateRequest` validiert `lastname` (nullable, max 255) und `phone` (nullable, max 50).
- [x] Felder im Profil-Blade vorhanden und vorbefüllt.
- [x] Feature-Test deckt Aktualisierung beider Felder ab.

### AUTH-05 · Passwort-Migration Legacy-Klartext-Konto · ⏱ 2h · ✅
**Beschreibung:** Mind. ein migriertes Konto (`richy@strikegun.de`) hat ein
Klartext-Passwort; Login schlägt fehl. Erzwungener Reset nötig.
**Akzeptanzkriterien:**
- [x] Artisan-Command markiert Non-bcrypt-Passwörter (z. B. `password=null` + Flag) und triggert „Passwort vergessen".
- [x] Betroffene Nutzer werden beim Login auf den Reset-Flow geleitet.
- [x] Dokumentiert in `MIGRATION_PLAN.md` Go-Live-Checkliste.
**Abhängig von:** ETL-Bereich.

### AUTH-06 · Lokalisierung der Auth-Texte (DE) · ⏱ 3h · ✅
**Beschreibung:** Breeze-Views/Mails sind teils Englisch.
**Akzeptanzkriterien:**
- [x] `lang/de` für Auth, Validation, Pagination angelegt; `APP_LOCALE=de`.
- [x] Login/Registrierung/Reset/Verifizierungsmail auf Deutsch.
- [x] Keine englischen Reststrings in Auth-Flows.

### AUTH-07 · Konto-Deaktivierung (`activated`) im Login durchsetzen · ⏱ 3h · ✅
**Beschreibung:** `users.activated` existiert, wird beim Login aber nicht geprüft.
**Akzeptanzkriterien:**
- [x] Deaktivierte Nutzer können sich nicht anmelden (klare Meldung).
- [x] Bereits eingeloggte deaktivierte Nutzer werden ausgeloggt (Middleware).
- [x] Feature-Tests für aktiv/inaktiv.

### AUTH-08 · „Profil löschen" auf Soft-Delete + Re-Aktivierung prüfen · ⏱ 2h · ✅
**Beschreibung:** User nutzt SoftDeletes; Selbstlöschung und Admin-Sicht klären.
**Akzeptanzkriterien:**
- [x] Selbstlöschung soft-deleted das Konto und loggt aus (bereits Verhalten – Test absichern).
- [x] Soft-gelöschte Nutzer erscheinen nicht in Auswahl-/Login-Listen.
- [x] Admin kann ein soft-gelöschtes Konto wiederherstellen (optionale Teilaufgabe).

### AUTH-09 · Registrieren als Nutzer  · ⏱ 2h · ✅
**Beschreibung:** Neuer User kann sich registrieren als für Teilnehmer und Buchen von Events
**Akzeptanzkriterien:**
- [x] Email verifizieren – MustVerifyEmail + Breeze-Verifikationsmail; Rate-Limiting, CSRF, Passwort-Stärkeregeln aktiv.
- [x] Neuer Nutzer wird nur als Teilnehmer und Event-Buchen Rolle erstellt.
- [x] Ein Eltern-Account für mehrere Kinder – bereits durch `user2player`-Pivot mit `self`-Flag unterstützt; mehrere Spieler je Account möglich.
- [x] Kind vom Eltern-Account abkoppeln – Pivot-Eintrag entfernen reicht; Spieler/Held-Daten bleiben erhalten (kein FK-Verlust). Kein spezielles UI nötig, Admin kann per Spieler-Verwaltung trennen.

### AUTH-10 Anschrift der erziehungsberechtigten Person als Pflichtdaten ✅
**Beschreibung:**Im Benutzerprofil müssen die Kontaktdaten der erziehungsberechtigten Person vollständig gepflegt werden.
**Akzeptanzkriterien:**
- [x] Pflichtfelder:
Vorname
Nachname
Straße
Hausnummer
PLZ
Ort
E-Mail
Mobiltelefon
- [x] Validierung verhindert unvollständige Profile bei Veranstaltungsanmeldung.
- [x] Bestehende Benutzer ohne Anschrift werden zur Ergänzung aufgefordert.

### AUTH-11 Rechtliche Rollenbezeichnung im Profil verbessern ✅
**Beschreibung:**Die Benutzerangaben sollen klar als Angaben der erziehungsberechtigten Person gekennzeichnet werden.
**Akzeptanzkriterien:**Formularüberschrift: „Angaben der erziehungsberechtigten Person”.
- [x] Hilfetext: „Diese Anschrift wird für Anmeldung, Kontakt und rechtliche Einwilligungen verwendet.”
- [x] Keine Formulierung, die nahelegt, dass hier die Anschrift des Kindes gemeint ist.

### AUTH-12 Datenschutz-Hinweis ergänzen ✅
**Beschreibung:**Bei der Adresseingabe soll kurz erklärt werden, warum die Daten benötigt werden.
**Akzeptanzkriterien:**
- [x] Hinweistext unter dem Adressblock:
„Wir benötigen diese Daten zur Durchführung der Veranstaltung, zur Kontaktaufnahme und für rechtlich erforderliche Einwilligungen.”
- [x] Der Hinweis ist verständlich und nicht zu lang.
- [x] Keine zusätzlichen Einwilligungs-Checkboxen, sofern nicht notwendig.