<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PUB-02: Öffentliches Helden-Profil – keine Authentifizierung erforderlich.
 * Zeigt ausschließlich Charakterdaten, keine personenbezogenen Spielerdaten.
 */
class PublicHeroController extends Controller
{
    /** PUB-03: Suchformular anzeigen. */
    public function index(): View
    {
        return view('public.search');
    }

    /** PUB-03: Code validieren und auf das Helden-Profil weiterleiten. */
    public function search(Request $request): RedirectResponse|View
    {
        $code = strtoupper(trim($request->input('code', '')));

        if (! preg_match('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/', $code)) {
            return view('public.search', [
                'error' => 'Bitte gib einen gültigen 6-stelligen Helden-Code ein.',
                'input' => $request->input('code', ''),
            ]);
        }

        return redirect()->route('public.hero', $code);
    }

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
