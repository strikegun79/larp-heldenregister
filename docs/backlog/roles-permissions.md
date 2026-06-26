# Backlog · Rollen & Rechte (ROLE)

Rollenmodell und Permission-Matrix (`config/permissions.php`).

## Inventar (✅)

### ROLE-01 · Rollen-Schema + Seeder (Legacy-IDs) · ⏱ 3h · ✅
`roles` + `role_user`, IDs 10–70, Labels Bürokrat/Projektleitung etc.

### ROLE-02 · Permission-Matrix + Gates · ⏱ 4h · ✅
11 Permissions, `User::hasPermission()`, Gate je Permission + `adventure.access`.

### ROLE-03 · `PermissionMatrixTest` · ⏱ 2h · ✅
Schreibt die komplette Rolle×Permission-Matrix fest.

## Offen (🔲)

### ROLE-04 · Rollen-Verwaltung im Admin (Lesen) · ⏱ 2h · ✅
**Beschreibung:** Übersicht aller Rollen samt zugeordneter Rechte (read-only),
damit Admins die Matrix einsehen können.
**Akzeptanzkriterien:**
- [x] Admin-Seite listet Rollen mit ihren Permissions (aus Config).
- [x] Anzahl Nutzer je Rolle wird angezeigt.
- [x] Nur mit `portal.manage` erreichbar.

### ROLE-05 · Projektleitung-Rechte verifizieren/abschließen · ⏱ 2h · ✅
**Beschreibung:** Projektleitung wurde in der YAML ergänzt; End-to-End prüfen,
dass alle UI-Pfade (Events anlegen via `events.edit`) für sie funktionieren.
**Akzeptanzkriterien:**
- [x] Feature-Tests: Projektleitung kann Events anlegen/bearbeiten, buchen, Helden ansehen.
- [x] Dashboard-/Nav-Sichtbarkeit für Projektleitung korrekt.

### ROLE-06 · Permission-gestützte Sichtbarkeit für Profil/Spieler entscheiden · ⏱ 3h · ✅
**Beschreibung:** `profile.view`/`player.view` sind aktuell nicht per Middleware
erzwungen (Selbstbedienung). Entscheiden + ggf. konsistent gaten.
**Akzeptanzkriterien:**
- [x] Entscheidung: **bewusst ungegated** — `players.index` liefert nur eigene Spieler via `$request->user()->players()`; `profile.edit` zeigt nur den eigenen Nutzer. Kein zusätzliches Gate nötig.
- [x] `players.show` ist via `PlayerPolicy::view()` (Eigentümerprüfung) gesichert.
- [x] Dokumentiert in `PlayerController::__construct()`.

### ROLE-07 · Blade-Direktive/Helper für Mehrfach-Permission-Checks · ⏱ 2h · ✅
**Beschreibung:** Wiederkehrende `@can('a') || @can('b')`-Muster kapseln.
**Akzeptanzkriterien:**
- [x] `@canany` in `_bookings.blade.php` bereits korrekt genutzt; Aktionen-Spalte mit `$canAnyBookingAction` (PHP-Variable) für `canAny(['approve-bookings', 'manage-payments', 'adventure.modify', 'adventure.cancel'])` gecacht → Spalte wird für Nutzer ohne Rechte ausgeblendet.
- [x] `adventure.access`-Gate zentralisiert in `AuthServiceProvider` (nicht dupliziert); Richtlinie für neue zusammengesetzte Gates dokumentiert.
- [x] Kein Custom-`@haspermission`-Helper nötig — Laravel `@can` / `@canany` reicht.

### ROLE-08 · Rollenänderungen im Audit-Log erfassen · ⏱ 3h · ✅
**Beschreibung:** Wer hat wem wann welche Rolle gegeben.
**Akzeptanzkriterien:**
- [x] Rollen-Sync in `Admin\UserController@update` schreibt Audit-Eintrag.
- [x] Sichtbar im Audit-Log (siehe ADM-Audit).
**Abhängig von:** ADM-08.

### ROLE-09 · Rollen für Lehrmeister einfügen · ⏱ 3h · ✅
**Beschreibung:** Die Rolle Lehrmeister soll hinzugefügt werden
**Akzeptanzkriterien:**
- [x] Die Lehrmeister-Rolle ist ein erweiterter Teamer (id=45). Mit Einfluss und mehr Rechte als normale Teamer.
- [x] Lehrmeister erhält: profile.view, player.view, heldenregister.view, adventure.book, events.view, adventure.cancel, adventure.modify; hat manage-attendance.
- [x] Die Rolle "Teamer" hat kein heldenregister.view mehr.
