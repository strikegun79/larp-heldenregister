<?php

namespace App\Http\Controllers;

use App\Models\HeroClass;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'can:heldenregister.view']);
    }

    /**
     * Fertigkeiten-Katalog: alle Fertigkeiten gruppiert nach Klasse (SKILL-04).
     * Filterbar per ?class_id=; innerhalb einer Klasse nach Level, dann Name sortiert.
     */
    public function index(Request $request): View
    {
        $classId = $request->integer('class_id') ?: null;

        $heroClasses = HeroClass::where('disabled', false)
            ->with(['skills' => function ($q) {
                $q->with('perlColor')
                    // SKILL-09: Anzahl aktiver Helden je Fertigkeit.
                    ->withCount(['heroes as active_heroes_count' => fn ($h) => $h->whereNull('died')->where('active', true)])
                    ->orderBy('level')
                    ->orderBy('name');
            }])
            ->when($classId, fn ($q) => $q->where('id', $classId))
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->skills->isNotEmpty());

        $allClasses = HeroClass::where('disabled', false)->orderBy('name')->get();

        return view('skills.index', compact('heroClasses', 'allClasses', 'classId'));
    }

    /**
     * SKILL-09: Modal – aktive Helden die diese Fertigkeit erworben haben.
     */
    public function heroes(Skill $skill): \Illuminate\View\View
    {
        $heroes = $skill->heroes()
            ->with('player')
            ->whereNull('died')
            ->where('active', true)
            ->orderBy('character_name')
            ->get();

        return view('admin.skills._heroes', compact('skill', 'heroes'));
    }
}
