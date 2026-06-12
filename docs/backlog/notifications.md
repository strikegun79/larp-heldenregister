# Backlog · Benachrichtigungen (NOTI)

E-Mail-Benachrichtigungen (Mailables/Notifications, via Queue).

## Inventar (✅)

### NOTI-01 · Admin-Benachrichtigung bei Neuregistrierung · ⏱ 2h · ✅
`NewUserRegistered` an alle Admins (ersetzt `email_admin_new-user.tpl`).

## Offen (🔲)

### NOTI-02 · Buchungsbestätigung an Spieler/Betreuer · ⏱ 3h · ✅
**Beschreibung:** Nach erfolgreicher Buchung Bestätigungsmail.
**Akzeptanzkriterien:**
- [x] Mailable mit Event-/Spieler-/Rollendaten; ausgelöst in `BookingController@store`.
- [x] Queued; Test mit `Mail::fake()`.
**Abhängig von:** INFRA-04 (Queue).

> Umgesetzt: `BookingReceived` (Notification, `ShouldQueue`, Mail-Kanal) mit
> Event-/Rollen-/Wartelisten-Infos; in `store` als On-Demand-Mail an
> `player->email` (sofern vorhanden). Test mit `Notification::fake`.

### NOTI-03 · Wartelisten-Benachrichtigung beim Nachrücken · ⏱ 2h · ✅
**Beschreibung:** Rückt eine Buchung von der Warteliste nach, wird informiert.
**Akzeptanzkriterien:**
- [x] Mail beim Statuswechsel Warteliste→regulär.
- [x] Test.
**Abhängig von:** BOOK-07.

> Umgesetzt: `WaitlistPromoted` (queued), in `BookingController@destroy` beim
> Nachrücken an den nachgerückten Spieler. Test mit `Notification::fake`.

### NOTI-04 · Event-Absage-Benachrichtigung · ⏱ 2h · ✅
**Beschreibung:** Bei Event-Absage werden alle Gebuchten informiert.
**Akzeptanzkriterien:**
- [x] Massenversand an gebuchte Spieler/Betreuer.
- [x] Test.
**Abhängig von:** ADV-07.

> Umgesetzt: `EventCancelled` (queued), in `AdventureController@cancel` an alle
> gebuchten Spieler mit E-Mail (dedupliziert). Test mit `Notification::fake`.

### NOTI-05 · Event-Erinnerung (X Tage vorher) · ⏱ 4h · 🔲
**Beschreibung:** Geplante Erinnerung vor Eventbeginn.
**Akzeptanzkriterien:**
- [ ] Scheduled Command (`schedule:run`) selektiert anstehende Events.
- [ ] Erinnerung an bestätigte Buchungen; idempotent (kein Doppelversand).
- [ ] Test.
**Abhängig von:** INFRA-05 (Scheduler).

### NOTI-06 · Passwort-Reset-/Aktivierungsmails auf Deutsch & gebrandet · ⏱ 3h · ✅
**Beschreibung:** Mail-Templates im Vereins-Layout (statt Default-Breeze).
**Akzeptanzkriterien:**
- [x] Gemeinsames Mail-Layout (Logo, Footer) wie Legacy `email_footer_system`.
- [x] Verifizierungs-/Reset-/Admin-Mails nutzen es; Deutsch.
- [x] Visuelle Prüfung (Mailpit) dokumentiert.

> Umgesetzt: Mail-Komponenten publiziert (`resources/views/vendor/mail`),
> Header (Vereinsname/Branding) und Footer (Verein, Hinweis) angepasst – greift
> für **alle** Markdown-Mails (Auth + Notifications). Verifizierungs- und
> Reset-Mail auf Deutsch via `VerifyEmail::toMailUsing`/`ResetPassword::toMailUsing`
> in `AppServiceProvider` (deutsche Betreffs, Texte, Grußformel). Admin-/
> Buchungs-Mails sind bereits deutsch. Tests: `BrandedMailTest` (4): deutsche
> Verify-/Reset-Mail, gebrandetes Layout im gerenderten HTML, Footer-Branding.
>
> Mailpit-Prüfung: lokal `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`,
> `MAIL_PORT=1025` auf Mailpit zeigen; Registrierung/„Passwort vergessen"
> auslösen und Betreff/Layout im Mailpit-UI (http://localhost:8025) prüfen.

### NOTI-07 · In-App-Benachrichtigungen (optional) · ⏱ 4h · 🔲
**Beschreibung:** Datenbank-Notifications + Glocke in der Navigation.
**Akzeptanzkriterien:**
- [ ] `notifications`-Tabelle; Anzeige ungelesener im Header.
- [ ] Markieren als gelesen; Test.
