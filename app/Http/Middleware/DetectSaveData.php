<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * UI-45: Datensparmodus erkennen.
 * Aktiv wenn der Browser `Save-Data: on` sendet ODER der Nutzer den Modus
 * manuell per Session aktiviert hat. Stellt $saveData für alle Blade-Views
 * bereit und setzt die Klasse „save-data" am <body>.
 */
class DetectSaveData
{
    public function handle(Request $request, Closure $next): Response
    {
        $saveData = $request->header('Save-Data') === 'on'
            || $request->session()->get('save_data', false);

        View::share('saveData', $saveData);

        $response = $next($request);

        // <body>-Klasse im gerenderten HTML ergänzen, damit CSS-Selektoren greifen.
        if ($saveData && $response instanceof \Illuminate\Http\Response) {
            $content = $response->getContent();
            $content = preg_replace('/<body([^>]*)>/', '<body$1 data-save-data="1">', $content, 1);
            $response->setContent($content);
        }

        return $response;
    }
}
