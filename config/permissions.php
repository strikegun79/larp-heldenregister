<?php

/*
|--------------------------------------------------------------------------
| Rollen-Rechte-Matrix
|--------------------------------------------------------------------------
| Quelle der Wahrheit für die Berechtigungen je Rolle (Slug). 'admin'
| erhält über '*' alle Rechte. Pro Eintrag in 'all' wird im
| AuthServiceProvider ein gleichnamiges Gate definiert.
*/

return [

    'all' => [
        'profile.view',
        'player.view',
        'heldenregister.view',
        'heldenregister.edit',
        'adventure.book',
        'adventure.modify',
        'adventure.cancel',
        'events.view',
        'events.edit',
        'users.manage',
        'portal.manage',
    ],

    'roles' => [
        // Admin
        'admin' => ['*'],

        // Bürokrat
        'registrar' => [
            'profile.view', 'player.view',
            'heldenregister.view', 'heldenregister.edit',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view', 'events.edit',
        ],

        // Projektleitung
        'project_lead' => [
            'profile.view', 'player.view',
            'heldenregister.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view', 'events.edit',
        ],

        // Spielleiter
        'game_master' => [
            'profile.view', 'player.view',
            'heldenregister.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view',
        ],

        // Teamer
        'teamer' => [
            'profile.view', 'player.view',
            'heldenregister.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view',
        ],

        // Event buchen
        'event_booking' => [
            'profile.view', 'player.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
        ],

        // Teilnehmer
        'participant' => [
            'profile.view', 'player.view',
        ],
    ],
];
