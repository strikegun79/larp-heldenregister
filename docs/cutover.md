# Cutover-Plan · Legacy → Heldenregister

Geordneter Wechsel vom Legacy-PHP-Portal (`larp_buerokrat`) zum neuen
Laravel-Heldenregister. Alle Schritte in Reihenfolge abarbeiten.

---

## Voraussetzungen (Tage/Wochen vor Cutover)

- [ ] ETL-04: `hero2classes.class_id`-Inkonsistenz bereinigt
- [ ] ETL-05: Trockenlauf (`--dry-run`) ohne Fehler durchgelaufen
- [ ] ETL-06: Datenqualitäts-Report geprüft, bekannte Abweichungen dokumentiert
- [ ] ETL-07: Re-Run-Verhalten nach Go-Live dokumentiert
- [ ] Alle offenen QA-Punkte (PHPStan, N+1) bewertet
- [ ] Testzugang für 2–3 Mitglieder eingerichtet; Feedback eingeholt
- [ ] Kommunikations-E-Mail vorbereitet (Vorlage unten)
- [ ] Termin festgelegt und Mitglieder informiert (mind. 1 Woche vorher)
- [ ] Backup des Legacy-Systems erstellt (DB + Dateien)

---

## Cutover-Tag

### Phase 1 · Vorbereitung (ca. 30 min vor Wartungsfenster)

- [ ] Letztes Backup der Legacy-DB erstellen:
  ```bash
  mysqldump -u root -p larp_buerokrat > /var/backups/legacy_larp_buerokrat_final_$(date +%Y%m%d).sql
  gzip /var/backups/legacy_larp_buerokrat_final_$(date +%Y%m%d).sql
  ```
- [ ] Backup des Heldenregisters erstellen:
  ```bash
  php artisan backup:run
  ```
- [ ] Wartungsseite im Legacy-Portal aktivieren (verhindert neue Schreibzugriffe)

### Phase 2 · ETL-Endlauf (ca. 15–30 min)

- [ ] Finalen ETL-Lauf auf der Produktiv-DB durchführen:
  ```bash
  php artisan migrate:legacy
  ```
- [ ] Validierungs-Report prüfen (Zähler Users/Heroes/Events/Bookings stimmen):
  ```bash
  php artisan migrate:legacy --report-only
  ```
- [ ] Stichprobe: mind. 5 Helden manuell in Legacy ↔ Heldenregister vergleichen

### Phase 3 · Heldenregister produktiv schalten (ca. 15 min)

- [ ] `.env` auf Produktivwerte prüfen (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] Config-Cache auffrischen:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] DNS umstellen: Domain zeigt auf den neuen Server/Docroot `public/`
  - TTL vorher auf 300s senken, danach wieder auf 3600s
- [ ] SSL-Zertifikat für neue Domain prüfen (`https://` erreichbar)
- [ ] Smoke-Test im Browser:
  - [ ] Login funktioniert
  - [ ] Heldenliste zeigt korrekte Daten
  - [ ] Event-Anmeldung funktioniert
  - [ ] E-Mail-Versand (Test-Notification) funktioniert

### Phase 4 · matrix-corporal umstellen (ca. 5 min)

- [ ] In der matrix-corporal-Konfiguration die Policy-URL auf das Heldenregister umstellen:
  ```
  https://<neue-domain>/api/matrix/corporal/policy
  ```
  (Bearer-Token: `MATRIX_CORPORAL_TOKEN` aus `.env`)
- [ ] corporal neu laden / Policy abrufen lassen
- [ ] Prüfen: Matrix-Konten werden korrekt provisioniert

### Phase 5 · Legacy abschalten

- [ ] Legacy-Portal auf dauerhaft read-only / Offline-Seite umstellen:
  - Apache/Nginx: Weiterleitung auf neue Domain oder statische Offline-Seite
  - Oder: `APP_MAINTENANCE=true` im Legacy setzen
- [ ] Legacy-DB-Zugriff auf read-only beschränken (optional):
  ```sql
  REVOKE INSERT, UPDATE, DELETE ON larp_buerokrat.* FROM 'legacy_user'@'localhost';
  ```
- [ ] Legacy-Dateien und DB-Dump archivieren (mind. 6 Monate aufbewahren)

---

## Rollback

Falls kritische Fehler auftreten:

- [ ] DNS zurück auf Legacy-Server (TTL beachten)
- [ ] matrix-corporal Policy-URL zurück auf Legacy
- [ ] Wartungsseite Legacy deaktivieren
- [ ] Fehler dokumentieren, Ursache beheben, neuen Cutover-Termin festlegen

---

## Post-Cutover (erste 48 Stunden)

- [ ] Log-Monitoring: `tail -f storage/logs/laravel-*.log` auf Fehler prüfen
- [ ] Queue-Worker läuft (`supervisorctl status heldenregister-worker`)
- [ ] Backup am Folgetag prüfen (`php artisan backup:list`)
- [ ] Mitglieder-Feedback einsammeln
- [ ] Legacy-DB-Verbindung (`LEGACY_DB_*`) aus `.env` entfernen, sobald kein Re-Run mehr nötig

---

## Kommunikation an Mitglieder

**Betreff:** Waldritter-Portal: Wechsel auf das neue Heldenregister am [DATUM]

> Liebe Waldritter,
>
> am **[DATUM]** zwischen **[UHRZEIT]** und **[UHRZEIT]** wechseln wir auf
> das neue Heldenregister-Portal. In dieser Zeit ist das Portal kurz nicht
> erreichbar.
>
> **Was ändert sich?**
> Das neue Portal ist unter **[NEUE URL]** erreichbar. Alle Helden, Abenteuer
> und Buchungen wurden übernommen. Euer Passwort bleibt unverändert.
>
> **Was müsst ihr tun?**
> Nichts — außer die neue URL bookmarken. Bei Fragen oder Problemen meldet
> euch bitte bei [KONTAKT-MAIL].
>
> Viele Grüße,
> [VEREINSNAME]

---

## Verantwortlichkeiten

| Schritt | Verantwortlich | Anmerkung |
|---|---|---|
| ETL-Endlauf | Entwickler | Protokoll aufbewahren |
| DNS-Umstellung | Server-Admin | TTL vorab senken |
| matrix-corporal | Server-Admin | Token aus `.env` |
| Kommunikation | Vereinsvorstand | mind. 1 Woche vorher |
| Monitoring 48h | Entwickler | Erreichbarkeit sicherstellen |
