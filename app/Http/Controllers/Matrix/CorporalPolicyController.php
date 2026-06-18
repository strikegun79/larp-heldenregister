<?php

namespace App\Http\Controllers\Matrix;

use App\Http\Controllers\Controller;
use App\Models\MatrixAccount;
use App\Models\MatrixManagedRoom;
use Illuminate\Http\JsonResponse;

/**
 * Liefert die matrix-corporal Policy als JSON. matrix-corporal ruft diesen
 * Endpoint periodisch ab und gleicht den Matrix-Server damit ab (Users, Räume,
 * Mitgliedschaften). Ersetzt das Legacy corporal.php – diese DB ist die
 * Quelle der Wahrheit für die Matrix-Benutzer.
 */
class CorporalPolicyController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $managedRoomIds = MatrixManagedRoom::orderBy('roomid')->pluck('roomid');

        // Nur aktive (nicht gelöschte) Konten, jeweils mit ihren Räumen.
        $accounts = MatrixAccount::with('rooms')->get();

        $users = $accounts->map(fn (MatrixAccount $account) => [
            'id' => $account->mxid,
            'active' => $account->active,
            'authType' => 'plain',
            'authCredential' => (string) $account->auth_credential,
            'displayName' => (string) $account->display_name,
            'avatarUri' => (string) $account->avatar_uri,
            'joinedRoomIds' => $account->rooms->pluck('roomid')->values(),
            'forbidRoomCreation' => $account->forbid_room_creation,
            'forbidEncryptedRoomCreation' => $account->forbid_encrypted_room_creation,
        ])->values();

        return response()->json([
            'schemaVersion' => 1,
            'flags' => (object) config('matrix.flags'),
            'managedRoomIds' => $managedRoomIds,
            'hooks' => config('matrix.hooks'),
            'users' => $users,
        ]);
    }
}
