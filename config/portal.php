<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portal-Identität
    |--------------------------------------------------------------------------
    | Name und Beschreibung des Portals – erscheinen in Titel, Footer, Mails
    | und der PWA-Manifest-Datei.
    */
    'name'             => env('PORTAL_NAME', 'Heldenregister'),
    'short_name'       => env('PORTAL_SHORT_NAME', 'Heldenregister'),
    'description'      => env('PORTAL_DESCRIPTION', 'LARP-Charakterverwaltung für Waldritter Gießen e.V.'),
    'larp_type'        => env('PORTAL_LARP_TYPE', 'Kinder- und Jugend-LARP'),

    /*
    |--------------------------------------------------------------------------
    | Organisation
    |--------------------------------------------------------------------------
    */
    'organization'       => env('PORTAL_ORGANIZATION', 'Waldritter-Gießen e.V.'),
    'organization_short' => env('PORTAL_ORGANIZATION_SHORT', 'Waldritter'),

    /*
    |--------------------------------------------------------------------------
    | Medien: Logo + Icons
    |--------------------------------------------------------------------------
    | logo: Dateiname unter public/images/
    | favicon: absoluter Pfad ab public/
    | apple_touch_icon: absoluter Pfad ab public/
    | icons: PWA-Icons für manifest.webmanifest
    */
    'logo'             => env('PORTAL_LOGO', 'Waldritter-Logo2_mit_Untertitel.png'),
    'favicon'          => env('PORTAL_FAVICON', '/favicon.ico'),
    'apple_touch_icon' => env('PORTAL_APPLE_TOUCH_ICON', '/icons/apple-touch-icon.png'),

    'icons' => [
        ['src' => '/icons/icon-192.png',          'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/icons/icon-512.png',          'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/icons/icon-512-maskable.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kontakt
    |--------------------------------------------------------------------------
    | contact_name:  Ansprechpartner (Entwicklung/Support)
    | contact_email: direkte E-Mail des Ansprechpartners
    | email:         allgemeine Portal-/Vereins-E-Mail
    */
    'contact_name'  => env('PORTAL_CONTACT_NAME', ''),
    'contact_email' => env('PORTAL_CONTACT_EMAIL', ''),
    'email'         => env('PORTAL_EMAIL', ''),

    /*
    |--------------------------------------------------------------------------
    | Design-Token
    |--------------------------------------------------------------------------
    | Werden in <meta name="theme-color"> und manifest.webmanifest verwendet.
    */
    'theme_color'      => env('PORTAL_THEME_COLOR', '#5a3a22'),
    'background_color' => env('PORTAL_BACKGROUND_COLOR', '#e4cea5'),

];
