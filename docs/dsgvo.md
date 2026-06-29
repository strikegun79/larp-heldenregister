# DSGVO-Datenschutzkonzept – Heldenregister

Stand: 2026-06-29 · Verantwortlicher: Waldritter Gießen e. V.

---

## 1. Inventar personenbezogener Daten

### 1.1 Benutzerkonten (`users`)

| Feld | Kategorie | Zweck |
|---|---|---|
| `name`, `lastname` | Identität | Anzeige, Kontakt |
| `email` | Kontakt | Login, Benachrichtigungen |
| `phone` | Kontakt | Erreichbarkeit bei Events |
| `street`, `house_number`, `zip`, `city` | Adresse | Erziehungsberechtigten-Anschrift (ADV-24) |
| `password` (bcrypt) | Auth | Zugang |
| `lastlogin_at` | Nutzungsmetadaten | Inaktivitätserkennung |
| `email_verified_at` | Status | E-Mail-Verifikation |
| `deleted_at` | Verwaltung | SoftDelete |

Benutzer sind in der Regel **Erziehungsberechtigte** (EBP) oder volljährige Teamer.
Adressfelder sind Pflicht, wenn minderjährige Spieler angemeldet werden (§ 1626 BGB,
Aufsichtspflicht).

### 1.2 Spieler (`players`)

| Feld | Kategorie | Besonderheit |
|---|---|---|
| `name`, `lastname` | Identität | oft Minderjährige |
| `email` | Kontakt | optional bei Minderjährigen |
| `dayofbirth` | **Sensibel** | Altersverifizierung, Jugendschutz |
| `gender` | **Sensibel** | Statistik (freiwillig) |
| `image` | Bild | Profilfoto im Storage |
| `street`, `house_number`, `zip`, `city` | Adresse | falls abw. von EBP |
| `deleted_at` | Verwaltung | SoftDelete |

Spieler können **Minderjährige** sein. Rechtsgrundlage für die Verarbeitung ist
die Einwilligung der Erziehungsberechtigten (Art. 6 Abs. 1 lit. a DSGVO i. V. m.
Art. 8 DSGVO).

### 1.3 Helden (`heroes`)

| Feld | Kategorie | Hinweis |
|---|---|---|
| `character_name` | Spielfigur | kein Realname |
| `born`, `died`, `homeplace`, `description` | Spielfigur | fiktive Daten |
| `image` | Bild | Charakterfoto |
| `public_code`, `public_visible`, `public_searchable` | Sichtbarkeit | opt-in |

Heldendaten sind **keine personenbezogenen Daten** im DSGVO-Sinne, sofern keine
Rückschlüsse auf die reale Person möglich sind. Der Bezug wird durch `player_id`
hergestellt.

### 1.4 Buchungen / Teilnahmelisten (`bookings`, `event_visits`)

| Feld | Kategorie | Hinweis |
|---|---|---|
| `player_id` | Referenz | → players |
| `participant_name`, `guest_name`, `guest_lastname` | Identität | Gäste ohne Konto |
| `signature` (base64) | **Besonders sensibel** | handschriftliche Unterschrift |
| `erreichbarkeit` | Kontakt | Notfallrufnummer für Veranstaltung |

Teilnehmerlisten (inkl. Unterschriften) unterliegen der **Löschpflicht** nach
Ablauf der Aufbewahrungsfrist (s. Abschnitt 3).

### 1.5 Audit-Log (`audit_logs`)

Enthält `actor_name` (Snapshot) und `subject_label` (Snapshot). Kein Verweis auf
gelöschte Konten nötig – Snapshots bleiben lesbar. Keine Echtzeit-Personendaten.

### 1.6 Benachrichtigungen (`notifications`)

Transiente Systembenachrichtigungen (ungelesen/gelesen). Werden mit dem Benutzer
gelöscht.

### 1.7 Matrix-Konten (`matrix_accounts`)

`mxid` ist pseudonym (Vorname.Nachname:domain) und wird bei Deaktivierung des
Matrix-Kontos durch Corporal gelöscht. Kein separates Löschkonzept erforderlich,
da Corporal der Datenverarbeiter ist.

---

## 2. Rechtsgrundlagen

| Verarbeitung | Rechtsgrundlage |
|---|---|
| Benutzerkonto (EBP) | Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung / Vereinsmitgliedschaft) |
| Spielerdaten Minderjähriger | Art. 6 Abs. 1 lit. a DSGVO + Art. 8 DSGVO (Einwilligung EBP) |
| Geburtsdatum | Art. 6 Abs. 1 lit. c DSGVO (Jugendschutzgesetz) |
| Teilnehmerlisten / Unterschriften | Art. 6 Abs. 1 lit. c DSGVO (Aufsichtspflicht, Versicherung) |
| Audit-Log | Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse: Nachvollziehbarkeit) |
| Fotos (Spieler/Held) | Art. 6 Abs. 1 lit. a DSGVO (Einwilligung) |
| E-Mail-Benachrichtigungen | Art. 6 Abs. 1 lit. b DSGVO |

---

## 3. Aufbewahrungsfristen

| Daten | Frist | Begründung |
|---|---|---|
| Benutzerkonten (aktiv) | Bis Vereinsaustritt + 3 Jahre | Vereinsrecht, steuerliche Aufbewahrung |
| Spielerdaten | Bis Vereinsaustritt + 3 Jahre | |
| Teilnehmerlisten / Buchungen | 10 Jahre | Haftungsrecht (§ 195 BGB) |
| Unterschriften (`bookings.signature`) | 10 Jahre | Haftungsrecht |
| Audit-Log | 2 Jahre | Nachvollziehbarkeit |
| System-Logs (`storage/logs`) | 30 Tage | INFRA-07 |
| Backups | 30 Tage täglich, 1 Jahr monatlich | INFRA-06 |
| Profilfotos (nach Löschung) | Sofort | Technisch zu implementieren (s. Abschnitt 5) |

---

## 4. Lösch- und Anonymisierungskonzept

### 4.1 Benutzer löschen (Art. 17 DSGVO)

Wenn ein Benutzer die Löschung seines Kontos verlangt:

1. **Prüfen:** Hat der Benutzer laufende Buchungen oder aktive Spieler? Falls ja,
   erst abschließen oder auf anderen Betreuer übertragen.
2. **`User::anonymize()` aufrufen** (s. Abschnitt 4.3) — überschreibt alle
   Klardaten, löscht Passwort-Token, soft-deleted den Datensatz.
3. **Spieler prüfen:** Sind die verknüpften Spieler ebenfalls zu löschen?
   `Player::anonymize()` für jeden betroffenen Spieler aufrufen.
4. **Audit-Log:** Der `actor_name`-Snapshot bleibt erhalten (berechtigtes Interesse).
   Der `subject_label`-Snapshot wird beim nächsten Audit-Eintrag nicht mehr ergänzt.

### 4.2 Spieler löschen

1. **Prüfen:** Hat der Spieler aktive Helden oder offene Buchungen?
2. **`Player::anonymize()` aufrufen** — überschreibt Klardaten, löscht Foto,
   soft-deleted.
3. **Helden:** `heroes.player_id` bleibt als anonymisierter Datensatz erhalten
   (Spielhistorie). Charaktername ist kein Realname.

### 4.3 Technische Umsetzung

`User::anonymize()` und `Player::anonymize()` sind in den jeweiligen Models
implementiert (s. `app/Models/User.php`, `app/Models/Player.php`).

```php
// Beispiel:
$user->anonymize();   // überschreibt Klardaten, soft-deletes
$player->anonymize(); // überschreibt Klardaten, löscht Foto, soft-deletes
```

### 4.4 Hard-Delete nach Ablauf der Frist

Nach Ablauf der Aufbewahrungsfrist (s. Abschnitt 3) können soft-deletete Datensätze
endgültig gelöscht werden:

```bash
# Benutzer die seit > 3 Jahren soft-deleted sind:
php artisan tinker
> User::onlyTrashed()->where('deleted_at', '<', now()->subYears(3))->forceDelete();

# Spieler entsprechend:
> Player::onlyTrashed()->where('deleted_at', '<', now()->subYears(3))->forceDelete();
```

Ein automatisierter Artisan-Befehl hierfür ist noch nicht implementiert
(s. Abschnitt 5, offene Punkte).

---

## 5. Datensparsamkeit in Exporten

### 5.1 Spieler-CSV-Export (`/admin/players/export`)

**Zugriff:** Nur `can:portal.manage` (Admin/Bürokrat).  
**Inhalt:** Nachname, Vorname, E-Mail, Geburtsdatum, Geschlecht, Heldenanzahl.  
**Begründung Geburtsdatum:** Pflicht für Altersverifizierung (Jugendschutz).  
**Begründung Geschlecht:** Statistik für Veranstaltungsplanung.  
**Maßnahme:** Export-Route ist hinter Admin-Middleware, keine öffentliche URL.

### 5.2 Teilnehmerliste-PDF (`/adventures/{id}/participants-pdf`)

**Zugriff:** Nur `can:take-signatures`.  
**Inhalt:** Name, Alter, Wohnort, Kontaktrufnummer, Unterschrift, EBP-Adresse.  
**Begründung:** Gesetzliche Aufsichtspflicht, Notfallkontakt, Versicherungsnachweis.  
**Maßnahme:** PDF enthält keine Sozialversicherungs- oder Gesundheitsdaten.
Die vollständige EBP-Adresse ist auf das gesetzlich erforderliche Minimum beschränkt.

### 5.3 EP-Export (`/heroes/{hero}/ep-export`)

**Zugriff:** Nur `can:heldenregister.view`.  
**Inhalt:** EP-Transaktionen eines Helden (kein Realname im Export).  
**Maßnahme:** Kein Handlungsbedarf.

### 5.4 Heldenausweis-PDF (`/admin/id-cards/...`)

**Inhalt:** Nur `character_name` + QR-Code (public_code → öffentliche URL).  
**Maßnahme:** Kein Handlungsbedarf — kein Realname enthalten.

---

## 6. Öffentliche Profilseiten

Helden können über `/h/{code}` öffentlich abrufbar sein, wenn:
- `public_visible = true` (opt-in durch Spieler/Admin)
- `public_searchable = true` (erscheint in Suche)

Standardmäßig sind beide Felder `false`. Der Spieler/Betreuer aktiviert die
Sichtbarkeit bewusst. Kein Realname wird auf der öffentlichen Seite angezeigt.

---

## 7. Offene Punkte (technische Schulden)

| # | Beschreibung | Priorität |
|---|---|---|
| 1 | Kein automatischer Hard-Delete-Befehl nach Ablauf der Fristen | Mittel |
| 2 | Profilfotos von Spielern werden beim `Player::delete()` nicht gelöscht | Hoch |
| 3 | Kein Selbstauskunfts-/Export-Feature für Benutzer (Art. 20 DSGVO) | Niedrig |
| 4 | Keine Einwilligungsverwaltung für Fotos (nur implizit durch Upload) | Niedrig |
| 5 | `bookings.signature` (base64) könnte nach Aufbewahrungsfrist gezielt gelöscht werden, ohne die Buchung selbst zu löschen | Mittel |

---

## 8. Technische Schutzmaßnahmen

- Alle Datenbankverbindungen über TLS (produktiv)
- Passwörter nur als bcrypt-Hash gespeichert
- Profilfotos nur über authentifizierten Storage-Zugriff (kein öffentliches S3)
- Admin-Routen durch `can:`-Middleware geschützt
- Audit-Log protokolliert sicherheitsrelevante Aktionen
- Backups verschlüsselt (AES-256, `BACKUP_ARCHIVE_PASSWORD`)
- Logs ohne Personendaten (nur User-ID, keine Klartextnamen)
