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
        'groups.manage',
        'roles.view',     // Rollen- & DSGVO-Übersicht einsehen
        // Umfrage-System (SURV-01)
        'survey.admin',        // Vorlagen verwalten, Umfragen erstellen/versenden, Ergebnisse einsehen
        'survey.view',         // Nur Ergebnisse einsehen
        // Newsletter-System (NL-01)
        'newsletter.manage',   // Newsletter erstellen, bearbeiten, versenden
        // Datenpannen-Protokoll (DSGVO Art. 33) – nur Admin via '*'
        'data-breach.manage',
        // Verwaltungs-Dashboards für Teilrollen (ROLE-10)
        'events.admin',   // Veranstaltungs-Lookups pflegen (Orte, Kategorien …)
        'heroes.admin',   // Helden-Konfiguration pflegen (Klassen, Skills …)
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
            'groups.manage',
            'roles.view',
        ],

        // Projektleitung
        'project_lead' => [
            'profile.view', 'player.view',
            'heldenregister.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view', 'events.edit',
            'events.admin',       // Veranstaltungs-Lookups in der Verwaltung pflegen (ROLE-10)
            'survey.admin',       // Vorlagen + Umfragen vollständig verwalten (inkl. survey.view)
            'survey.view',
            'roles.view',
            'newsletter.manage',
        ],

        // Spielleiter
        'game_master' => [
            'profile.view', 'player.view',
            'heldenregister.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view',
            'groups.manage',
            'heroes.admin',       // Helden-Konfiguration in der Verwaltung pflegen (ROLE-10)
            'roles.view',
        ],

        // Lehrmeister (erweiterter Teamer mit Helden-Einsicht, ROLE-09)
        'lehrmeister' => [
            'profile.view', 'player.view',
            'heldenregister.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view',
            'roles.view',
        ],

        // Teamer (kein heldenregister.view, ROLE-09)
        'teamer' => [
            'profile.view', 'player.view',
            'adventure.book', 'adventure.modify', 'adventure.cancel',
            'events.view',
            'roles.view',
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
