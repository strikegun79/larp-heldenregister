<?php

namespace App\Http\Controllers;

use App\Models\HeroClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Editor zum Platzieren der Fertigkeits-Marker auf dem Klassen-Baum-Bild
 * (HERO-17). Die Positionen (x/y in %) gelten je Klasse und liegen auf der
 * Pivot-Tabelle skill_hero_class.
 */
class SkilltreeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:heldenregister.edit');
    }

    public function edit(HeroClass $heroClass): View
    {
        $heroClass->load('skills');

        return view('skilltree.edit', ['class' => $heroClass]);
    }

    public function update(Request $request, HeroClass $heroClass): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'positions' => ['array'],
            'positions.*.skill_id' => ['required', 'integer', 'exists:skills,id'],
            'positions.*.x' => ['required', 'numeric', 'min:0', 'max:100'],
            'positions.*.y' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($data['positions'] ?? [] as $position) {
            DB::table('skill_hero_class')
                ->where('hero_class_id', $heroClass->id)
                ->where('skill_id', $position['skill_id'])
                ->update([
                    'x_percentage' => (int) round($position['x']),
                    'y_percentage' => (int) round($position['y']),
                ]);
        }

        $message = 'Positionen gespeichert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }
}
