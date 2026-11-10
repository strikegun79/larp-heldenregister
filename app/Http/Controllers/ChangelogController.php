<?php

namespace App\Http\Controllers;

use App\Support\Changelog;
use Illuminate\View\View;

/**
 * Versionsverlauf des Portals (nur für angemeldete Nutzer).
 */
class ChangelogController extends Controller
{
    public function __invoke(Changelog $changelog): View
    {
        return view('changelog', [
            'version' => $changelog->currentVersion(),
            'html' => $changelog->html(),
        ]);
    }
}
