<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Task-Definitionen
    |--------------------------------------------------------------------------
    | Jeder Task hat ein Label, eine Beschreibung, ein Icon und Standardwerte
    | für Referenz (start_at / end_at), Richtung (before / after) und Tage.
    | Kategorien: status, communication, management
    */

    'tasks' => [

        // --- Status-Automationen ------------------------------------------------

        'open_registration' => [
            'label'              => 'Anmeldung öffnen',
            'description'        => 'Setzt den Event-Status automatisch auf „Anmeldung offen" (Status 30).',
            'icon'               => 'lock open',
            'category'           => 'status',
            'default_reference'  => 'start_at',
            'default_direction'  => 'before',
            'default_days'       => 30,
        ],

        'close_registration' => [
            'label'              => 'Anmeldung schließen',
            'description'        => 'Setzt den Event-Status auf „Anmeldung geschlossen" (Status 40). Check-in wird damit möglich.',
            'icon'               => 'lock',
            'category'           => 'status',
            'default_reference'  => 'start_at',
            'default_direction'  => 'before',
            'default_days'       => 10,
        ],

        'complete_event' => [
            'label'              => 'Event abschließen',
            'description'        => 'Setzt den Event-Status auf „Abgeschlossen" (Status 60).',
            'icon'               => 'check circle',
            'category'           => 'status',
            'default_reference'  => 'end_at',
            'default_direction'  => 'after',
            'default_days'       => 3,
        ],

        'disable_waitlist' => [
            'label'              => 'Wartelistenmodus deaktivieren',
            'description'        => 'Deaktiviert den Wartelistenmodus und rückt Teilnehmer automatisch auf freie Plätze nach.',
            'icon'               => 'list ol',
            'category'           => 'status',
            'default_reference'  => 'start_at',
            'default_direction'  => 'before',
            'default_days'       => 5,
        ],

        // --- Kommunikation ------------------------------------------------------

        'send_reminder' => [
            'label'              => 'Erinnerungsmail versenden',
            'description'        => 'Sendet automatisch Erinnerungsmails an alle bestätigten Teilnehmer.',
            'icon'               => 'bell',
            'category'           => 'communication',
            'default_reference'  => 'start_at',
            'default_direction'  => 'before',
            'default_days'       => 3,
        ],

        'send_survey' => [
            'label'              => 'Feedback-Umfrage versenden',
            'description'        => '(noch nicht implementiert) Versendet die verknüpfte Feedback-Umfrage an alle Teilnehmer.',
            'icon'               => 'clipboard list',
            'category'           => 'communication',
            'default_reference'  => 'end_at',
            'default_direction'  => 'after',
            'default_days'       => 2,
        ],

        'notify_ep_entry' => [
            'label'              => 'EP-Vergabe erinnern',
            'description'        => 'Erinnert den Spielleiter per E-Mail daran, die EP für dieses Event einzutragen.',
            'icon'               => 'star',
            'category'           => 'communication',
            'default_reference'  => 'end_at',
            'default_direction'  => 'after',
            'default_days'       => 5,
        ],

        'send_participants_pdf' => [
            'label'              => 'Teilnehmerliste versenden',
            'description'        => 'Sendet die Teilnehmerliste als PDF-Link per E-Mail an den Eventleiter.',
            'icon'               => 'file pdf outline',
            'category'           => 'communication',
            'default_reference'  => 'start_at',
            'default_direction'  => 'before',
            'default_days'       => 2,
        ],

        // --- Verwaltung ---------------------------------------------------------

        'promote_waitlist' => [
            'label'              => 'Warteliste nachrücken lassen',
            'description'        => 'Rückt Teilnehmer von der Warteliste auf freie Plätze nach (ohne Wartelistenmodus zu deaktivieren).',
            'icon'               => 'arrow up',
            'category'           => 'management',
            'default_reference'  => 'start_at',
            'default_direction'  => 'before',
            'default_days'       => 1,
        ],

        'archive_bookings' => [
            'label'              => 'Stornierte Buchungen archivieren',
            'description'        => '(noch nicht implementiert) Markiert stornierte Buchungen nach der Veranstaltung als archiviert.',
            'icon'               => 'archive',
            'category'           => 'management',
            'default_reference'  => 'end_at',
            'default_direction'  => 'after',
            'default_days'       => 14,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Kategorie-Labels (für die Anzeige im Taskmanager-Tab)
    |--------------------------------------------------------------------------
    */

    'categories' => [
        'status'        => ['label' => 'Status-Automationen', 'icon' => 'sync alternate'],
        'communication' => ['label' => 'Kommunikation',       'icon' => 'mail'],
        'management'    => ['label' => 'Verwaltung',          'icon' => 'cogs'],
    ],

];
