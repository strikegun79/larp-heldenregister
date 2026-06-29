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

### MTX-06 · Default-Raum-Zuordnung anwenden · ⏱ 3h · ✅
**Beschreibung:** `default_allow` Räume sollten neuen Matrix-Konten automatisch
zugeordnet werden.
**Akzeptanzkriterien:**
- [x] Bei Neuanlage eines Kontos werden `default_allow`-Räume vorbelegt.
- [x] `default_deny` kennzeichnet Räume im Formular (roter Label „Gesperrt").
- [x] Tests (3 neue Tests in `MatrixProvisioningTest`).

> Umgesetzt: `MatrixAccountController@edit` wählt `default_allow`-Räume für
> neue Konten vor; bestehende Konten behalten ihre tatsächlichen Räume.
> View zeigt „Vorauswahl"-Hinweis und farbige Labels (Standard/Gesperrt).

### MTX-07 · mxid-Kollision/Sanitisierung · ⏱ 3h · ✅
**Beschreibung:** Abgeleitete mxid `@vorname.nachname:domain` kann Leer-/
Sonderzeichen enthalten (Legacy hatte `@mia lenja...`). Matrix-IDs erlauben das nicht.
**Akzeptanzkriterien:**
- [x] Sanitisierung (Kleinbuchstaben, Leerzeichen→`_`, Umlaute aufgelöst, Sonderzeichen entfernt).
- [x] Kollisionsbehandlung bei doppelten Namen (`.2`, `.3`, …-Suffix).
- [x] 15 Tests in `MatrixMxidSanitizationTest` (Umlaute, Akzente, Leerzeichen, Bindestrich, Kollisionen, Integration).

> Umgesetzt: `Player::sanitizeMxidLocalpart()` (private), `deriveMatrixId()` (Vorschau),
> `uniqueMatrixId()` (Kollisions-sicher, wird bei Kontoanlage genutzt).
> Controller nutzt jetzt `uniqueMatrixId()` statt `deriveMatrixId()`.

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
