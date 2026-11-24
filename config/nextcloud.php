<?php

/*
|--------------------------------------------------------------------------
| Nextcloud Talk (Issue #31)
|--------------------------------------------------------------------------
| Zugangsdaten nur aus der Umgebung. Ohne URL, Benutzer oder App-Passwort
| gilt die Anbindung als nicht konfiguriert und alle Talk-Funktionen bleiben aus.
| Das App-Passwort gehört zu einem technischen Konto mit Admin-Rechten
| (Kontenverwaltung) und wird nie angezeigt oder geloggt.
*/

return [
    'url' => rtrim((string) env('NEXTCLOUD_URL', ''), '/'),
    'user' => env('NEXTCLOUD_USER'),
    'app_password' => env('NEXTCLOUD_APP_PASSWORD'),
    'timeout' => (int) env('NEXTCLOUD_TIMEOUT', 10),

    // Vorbelegung für den Link zur Hilfe (in der Verwaltung änderbar).
    'default_help_url' => 'https://hilfe.waldritter-giessen.de/70_nextcloud-talk/index.html',
];
