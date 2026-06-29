# Backlog · Matrix-Integration (MTX)

Diese DB ist die User-Quelle für den Matrix-Server via matrix-corporal.

## Inventar (✅)

### MTX-01 · Schema (accounts/rooms/memberships) · ⏱ 3h · ✅
`matrix_accounts`, `matrix_managed_rooms`, `matrix_room_memberships`.

### MTX-02 · Models + Relationen · ⏱ 2h · ✅
`MatrixAccount` (String-PK), `MatrixManagedRoom`, `Player::matrixAccount`.

### MTX-03 · corporal-Policy-Endpoint + Token-Middleware · ⏱ 3h · ✅
`GET /api/matrix/corporal/policy`, Bearer-Token.

### MTX-04 · Provisionierung pro Spieler (Admin) · ⏱ 4h · ✅
Konto anlegen/aktivieren, Räume zuweisen, Zugang entziehen.

## Offen (🔲)

### MTX-05 · Matrix-Räume verwalten (CRUD) · ⏱ 3h · ✅
**Beschreibung:** `matrix_managed_rooms` werden nur in der Provisionierung
referenziert, sind aber nicht pflegbar.
**Akzeptanzkriterien:**
- [x] Admin-CRUD für Räume (roomid, Name, Typ Raum/Space, default_allow/deny).
- [x] Verwendung in der Provisionierungs-Raumauswahl.
- [x] Tests.

> Umgesetzt: `MatrixRoomController` (index/create/store/edit/update/destroy),
> Views `admin/matrix/rooms/index.blade.php` + `_form.blade.php`. Route:
> `admin.matrix.rooms.*` unter `can:portal.manage`. Löschen verhindert wenn
> Mitglieder vorhanden. 11 Tests grün.

### MTX-06 · Default-Raum-Zuordnung anwenden · ⏱ 3h · 🔲
**Beschreibung:** `default_allow` Räume sollten neuen Matrix-Konten automatisch
zugeordnet werden.
**Akzeptanzkriterien:**
- [ ] Bei Neuanlage eines Kontos werden `default_allow`-Räume vorbelegt.
- [ ] `default_deny` schließt Räume aus.
- [ ] Tests.

### MTX-07 · mxid-Kollision/Sanitisierung · ⏱ 3h · 🔲
**Beschreibung:** Abgeleitete mxid `@vorname.nachname:domain` kann Leer-/
Sonderzeichen enthalten (Legacy hatte `@mia lenja...`). Matrix-IDs erlauben das nicht.
**Akzeptanzkriterien:**
- [ ] Sanitisierung (Kleinbuchstaben, Leerzeichen→`_`/entfernt, ASCII).
- [ ] Kollisionsbehandlung bei doppelten Namen (Suffix).
- [ ] Tests mit problematischen Namen.

### MTX-08 · Policy-Caching/Performance · ⏱ 2h · 🔲
**Beschreibung:** corporal pollt regelmäßig; Policy-Antwort cachen/invalidieren.
**Akzeptanzkriterien:**
- [ ] Kurzes Caching der Policy mit Invalidierung bei Konto-/Raumänderung.
- [ ] Test, dass Änderungen zeitnah sichtbar werden.

### MTX-09 · Provisionierungs-Audit & Statusanzeige · ⏱ 2h · 🔲
**Beschreibung:** Nachvollziehbarkeit von Konto-/Raumänderungen.
**Akzeptanzkriterien:**
- [ ] Änderungen am Matrix-Konto landen im Audit-Log (ADM-08).
- [ ] Admin-Liste zeigt Raumzahl/aktiv je Konto.
