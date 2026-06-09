# Backlog · Benachrichtigungen (NOTI)

E-Mail-Benachrichtigungen (Mailables/Notifications, via Queue).

## Inventar (✅)

### NOTI-01 · Admin-Benachrichtigung bei Neuregistrierung · ⏱ 2h · ✅
`NewUserRegistered` an alle Admins (ersetzt `email_admin_new-user.tpl`).

## Offen (🔲)

### NOTI-02 · Buchungsbestätigung an Spieler/Betreuer · ⏱ 3h · 🔲
**Beschreibung:** Nach erfolgreicher Buchung Bestätigungsmail.
**Akzeptanzkriterien:**
- [ ] Mailable mit Event-/Spieler-/Rollendaten; ausgelöst in `BookingController@store`.
- [ ] Queued; Test mit `Mail::fake()`.
**Abhängig von:** INFRA-04 (Queue).

### NOTI-03 · Wartelisten-Benachrichtigung beim Nachrücken · ⏱ 2h · 🔲
**Beschreibung:** Rückt eine Buchung von der Warteliste nach, wird informiert.
**Akzeptanzkriterien:**
- [ ] Mail beim Statuswechsel Warteliste→regulär.
- [ ] Test.
**Abhängig von:** BOOK-07.

### NOTI-04 · Event-Absage-Benachrichtigung · ⏱ 2h · 🔲
**Beschreibung:** Bei Event-Absage werden alle Gebuchten informiert.
**Akzeptanzkriterien:**
- [ ] Massenversand an gebuchte Spieler/Betreuer.
- [ ] Test.
**Abhängig von:** ADV-07.

### NOTI-05 · Event-Erinnerung (X Tage vorher) · ⏱ 4h · 🔲
**Beschreibung:** Geplante Erinnerung vor Eventbeginn.
**Akzeptanzkriterien:**
- [ ] Scheduled Command (`schedule:run`) selektiert anstehende Events.
- [ ] Erinnerung an bestätigte Buchungen; idempotent (kein Doppelversand).
- [ ] Test.
**Abhängig von:** INFRA-05 (Scheduler).

### NOTI-06 · Passwort-Reset-/Aktivierungsmails auf Deutsch & gebrandet · ⏱ 3h · 🔲
**Beschreibung:** Mail-Templates im Vereins-Layout (statt Default-Breeze).
**Akzeptanzkriterien:**
- [ ] Gemeinsames Mail-Layout (Logo, Footer) wie Legacy `email_footer_system`.
- [ ] Verifizierungs-/Reset-/Admin-Mails nutzen es; Deutsch.
- [ ] Visuelle Prüfung (Mailpit) dokumentiert.

### NOTI-07 · In-App-Benachrichtigungen (optional) · ⏱ 4h · 🔲
**Beschreibung:** Datenbank-Notifications + Glocke in der Navigation.
**Akzeptanzkriterien:**
- [ ] `notifications`-Tabelle; Anzeige ungelesener im Header.
- [ ] Markieren als gelesen; Test.
