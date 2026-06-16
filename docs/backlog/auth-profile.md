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

### AUTH-05 · Passwort-Migration Legacy-Klartext-Konto · ⏱ 2h · 🔲
**Beschreibung:** Mind. ein migriertes Konto (`richy@strikegun.de`) hat ein
Klartext-Passwort; Login schlägt fehl. Erzwungener Reset nötig.
**Akzeptanzkriterien:**
- [ ] Artisan-Command markiert Non-bcrypt-Passwörter (z. B. `password=null` + Flag) und triggert „Passwort vergessen".
- [ ] Betroffene Nutzer werden beim Login auf den Reset-Flow geleitet.
- [ ] Dokumentiert in `MIGRATION_PLAN.md` Go-Live-Checkliste.
**Abhängig von:** ETL-Bereich.

### AUTH-06 · Lokalisierung der Auth-Texte (DE) · ⏱ 3h · 🔲
**Beschreibung:** Breeze-Views/Mails sind teils Englisch.
**Akzeptanzkriterien:**
- [ ] `lang/de` für Auth, Validation, Pagination angelegt; `APP_LOCALE=de`.
- [ ] Login/Registrierung/Reset/Verifizierungsmail auf Deutsch.
- [ ] Keine englischen Reststrings in Auth-Flows.

### AUTH-07 · Konto-Deaktivierung (`activated`) im Login durchsetzen · ⏱ 3h · 🔲
**Beschreibung:** `users.activated` existiert, wird beim Login aber nicht geprüft.
**Akzeptanzkriterien:**
- [ ] Deaktivierte Nutzer können sich nicht anmelden (klare Meldung).
- [ ] Bereits eingeloggte deaktivierte Nutzer werden ausgeloggt (Middleware).
- [ ] Feature-Tests für aktiv/inaktiv.

### AUTH-08 · „Profil löschen" auf Soft-Delete + Re-Aktivierung prüfen · ⏱ 2h · 🔲
**Beschreibung:** User nutzt SoftDeletes; Selbstlöschung und Admin-Sicht klären.
**Akzeptanzkriterien:**
- [ ] Selbstlöschung soft-deleted das Konto und loggt aus (bereits Verhalten – Test absichern).
- [ ] Soft-gelöschte Nutzer erscheinen nicht in Auswahl-/Login-Listen.
- [ ] Admin kann ein soft-gelöschtes Konto wiederherstellen (optionale Teilaufgabe).

### AUTH-09 · Registrieren als Nutzer  · ⏱ 2h · 🔲
**Beschreibung:** Neuer User kann sich registrieren als für Teilnehmer und Buchen von Events
**Akzeptanzkriterien:**
- [ ] Email verifizieren, beachte alle gängingen sinnvollen Sicherheitsmechanismen
- [ ] Neuer Nutzer wird nur als Teilnehmer und Event-Buchen Rolle erstellt.
- [ ] Ein Eltern-Account für mehrere Kinder, aber Kinder sollen auch ihren Account sehen können oder anmelden?
- [ ] Wenn Kinder rauswachsen, wie kann man ein Kinder vom Eltern-Account abkoppeln ohne die Daten zum Spieler und Helden zu verlieren?
