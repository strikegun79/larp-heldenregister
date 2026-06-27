<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PUB-02/03/08: Öffentliches Helden-Profil & Suche – keine Authentifizierung.
 * Zeigt ausschließlich Charakterdaten, keine personenbezogenen Spielerdaten.
 */
class PublicHeroController extends Controller
{
    /** PUB-03: Suchformular anzeigen. */
    public function index(): View
    {
        return view('public.search');
    }

    /**
     * PUB-03/08: Code oder Heldennamen suchen.
     * Gültiger 6-Zeichen-Code → direkte Weiterleitung auf /h/{code}.
     * Sonst → Namenssuche (nur public_searchable + public_visible).
     */
    public function search(Request $request): RedirectResponse|View
    {
        $query = trim($request->input('code', ''));
        $upper = strtoupper($query);

        // Sieht nach einem Code aus → direkt weiterleiten.
        if (preg_match('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/', $upper)) {
            return redirect()->route('public.hero', $upper);
        }

        // Zu kurze oder leere Eingabe ohne Treffer → Fehlermeldung.
        if (mb_strlen($query) < 2) {
            return view('public.search', [
                'error' => 'Bitte gib mindestens 2 Zeichen oder ein 6-stelliges Helden-Siegel ein.',
                'input' => $query,
            ]);
        }

        // PUB-08: Namenssuche – nur sichtbare & suchbare Helden.
        $results = Hero::where('public_visible', true)
            ->where('public_searchable', true)
            ->where('character_name', 'like', '%'.$query.'%')
            ->with('classes')
            ->orderBy('character_name')
            ->limit(20)
            ->get();

        return view('public.search', [
            'results' => $results,
            'input'   => $query,
        ]);
    }

    public function show(string $code): View
    {
        $hero = Hero::where('public_code', strtoupper($code))
            ->where('public_visible', true)
            ->with([
                'player',
                'classes.skills.perlColor',
                'skills.perlColor',
                'groups',
                'epTransactions.type',
            ])
            ->firstOrFail();

        $learnedIds  = $hero->skills->pluck('id');
        $perlSummary = $hero->perlSummary;

        return view('public.hero', compact('hero', 'perlSummary', 'learnedIds'));
    }
}
