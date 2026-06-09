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

### ROLE-04 · Rollen-Verwaltung im Admin (Lesen) · ⏱ 2h · 🔲
**Beschreibung:** Übersicht aller Rollen samt zugeordneter Rechte (read-only),
damit Admins die Matrix einsehen können.
**Akzeptanzkriterien:**
- [ ] Admin-Seite listet Rollen mit ihren Permissions (aus Config).
- [ ] Anzahl Nutzer je Rolle wird angezeigt.
- [ ] Nur mit `portal.manage` erreichbar.

### ROLE-05 · Projektleitung-Rechte verifizieren/abschließen · ⏱ 2h · 🔲
**Beschreibung:** Projektleitung wurde in der YAML ergänzt; End-to-End prüfen,
dass alle UI-Pfade (Events anlegen via `events.edit`) für sie funktionieren.
**Akzeptanzkriterien:**
- [ ] Feature-Tests: Projektleitung kann Events anlegen/bearbeiten, buchen, Helden ansehen.
- [ ] Dashboard-/Nav-Sichtbarkeit für Projektleitung korrekt.

### ROLE-06 · Permission-gestützte Sichtbarkeit für Profil/Spieler entscheiden · ⏱ 3h · 🔲
**Beschreibung:** `profile.view`/`player.view` sind aktuell nicht per Middleware
erzwungen (Selbstbedienung). Entscheiden + ggf. konsistent gaten.
**Akzeptanzkriterien:**
- [ ] Entscheidung dokumentiert (bewusst ungegated vs. gegated).
- [ ] Falls gegated: Middleware + Tests; neue Nutzer (Teilnehmer) behalten Zugriff.

### ROLE-07 · Blade-Direktive/Helper für Mehrfach-Permission-Checks · ⏱ 2h · 🔲
**Beschreibung:** Wiederkehrende `@can('a') || @can('b')`-Muster kapseln.
**Akzeptanzkriterien:**
- [ ] `@canany`-Nutzung vereinheitlicht oder eigener Helper `@haspermission`.
- [ ] `adventure.access`-Logik zentral, nicht dupliziert.

### ROLE-08 · Rollenänderungen im Audit-Log erfassen · ⏱ 3h · 🔲
**Beschreibung:** Wer hat wem wann welche Rolle gegeben.
**Akzeptanzkriterien:**
- [ ] Rollen-Sync in `Admin\UserController@update` schreibt Audit-Eintrag.
- [ ] Sichtbar im Audit-Log (siehe ADM-Audit).
**Abhängig von:** ADM-08.
