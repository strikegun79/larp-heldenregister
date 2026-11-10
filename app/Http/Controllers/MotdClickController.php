<?php

namespace App\Http\Controllers;

use App\Models\Motd;
use App\Models\MotdLinkClick;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Zählt Klicks auf Links der Startseiten-Nachricht (per navigator.sendBeacon).
 */
class MotdClickController extends Controller
{
    public function store(Request $request, Motd $motd): Response
    {
        $url = (string) $request->input('url', '');

        // Nur Links zählen, die tatsächlich in der Nachricht vorkommen.
        if ($url === '' || ! in_array($url, $motd->trackableUrls(), true)) {
            return response()->noContent(422);
        }

        // Schnelle Mehrfachklicks desselben Nutzers auf denselben Link nur einmal zählen.
        $lock = 'motd_click:'.$motd->id.':'.$request->user()->id.':'.hash('sha256', $url);
        if (Cache::add($lock, true, 10)) {
            MotdLinkClick::record($motd, $url);
        }

        return response()->noContent();
    }
}
