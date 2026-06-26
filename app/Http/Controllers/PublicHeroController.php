<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use Illuminate\View\View;

/**
 * PUB-02: Öffentliches Helden-Profil – keine Authentifizierung erforderlich.
 * Zeigt ausschließlich Charakterdaten, keine personenbezogenen Spielerdaten.
 */
class PublicHeroController extends Controller
{
    public function show(string $code): View
    {
        $hero = Hero::where('public_code', strtoupper($code))
            ->with(['classes', 'skills.perlColor', 'groups'])
            ->firstOrFail();

        $perlSummary = $hero->skills
            ->filter(fn ($s) => $s->perlColor !== null)
            ->groupBy('perl_color_id')
            ->map(fn ($group) => (object) [
                'color' => $group->first()->perlColor,
                'count' => $group->count(),
            ])
            ->sortBy('color.name')
            ->values();

        return view('public.hero', compact('hero', 'perlSummary'));
    }
}
