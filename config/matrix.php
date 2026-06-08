<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Matrix-Homeserver
    |--------------------------------------------------------------------------
    */
    'domain' => env('MATRIX_DOMAIN', 'waldritter-giessen.de'),

    /*
    |--------------------------------------------------------------------------
    | matrix-corporal Policy-Endpoint
    |--------------------------------------------------------------------------
    | Bearer-Token, mit dem matrix-corporal die Policy abruft. Im Legacy war
    | er fest im Code; hier kommt er aus der Umgebung. Ohne Token -> 401.
    */
    'corporal_token' => env('MATRIX_CORPORAL_TOKEN'),

    // Statische Policy-Flags (1:1 aus dem Legacy corporal.php).
    'flags' => [
        'allowCustomUserDisplayNames' => false,
        'allowCustomUserAvatars' => true,
        'allowCustomPassthroughUserPasswords' => false,
        'allowUnauthenticatedPasswordResets' => false,
        'forbidRoomCreation' => false,
        'forbidEncryptedRoomCreation' => false,
        'forbidUnencryptedRoomCreation' => false,
    ],

    // Statische Policy-Hooks (1:1 aus dem Legacy corporal.php).
    'hooks' => [
        [
            'id' => 'custom-hook-to-prevent-banning',
            'eventType' => 'beforeAnyRequest',
            'matchRules' => [
                ['type' => 'method', 'regex' => 'POST'],
                ['type' => 'route', 'regex' => '^/_matrix/client/r0/rooms/([^/]+)/ban'],
            ],
            'action' => 'reject',
            'responseStatusCode' => 403,
            'rejectionErrorCode' => 'M_FORBIDDEN',
            'rejectionErrorMessage' => 'Banning ist verboten auf diesem Server. Melde Verstöße über MELDEN',
        ],
        [
            'id' => 'custom-hook-to-prevent-change-displayname',
            'eventType' => 'beforeAnyRequest',
            'matchRules' => [
                ['type' => 'method', 'regex' => 'PUT'],
                ['type' => 'route', 'regex' => '^/_matrix/client/r0/profile/[^/]+/displayname'],
            ],
            'action' => 'reject',
            'responseStatusCode' => 403,
            'rejectionErrorCode' => 'M_FORBIDDEN',
            'rejectionErrorMessage' => 'Ändern des Displaynamen ist nicht gestattet.',
        ],
    ],

];
