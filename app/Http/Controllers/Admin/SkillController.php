<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroClass;
use App\Models\PerlColor;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Fertigkeiten-Verwaltung (SKILL-02 + SKILL-03).
 * Erstellen, Bearbeiten, Löschen von Fertigkeiten inkl. Klassen-Zuordnung.
 */
class SkillController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->string('q')->toString();
        $classId = $request->integer('class_id') ?: null;

        $skills = Skill::with(['heroClass', 'perlColor'])
            ->withCount([
                'classes',
                // SKILL-09: nur aktive Helden (kein died, active=true).
                'heroes as active_heroes_count' => fn ($q) => $q->whereNull('died')->where('active', true),
            ])
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->when($classId, fn ($query) => $query->where('hero_class_id', $classId))
            ->orderBy('hero_class_id')
            ->orderBy('level')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $heroClasses = HeroClass::where('disabled', false)->orderBy('name')->get();

        return view('admin.skills.index', compact('skills', 'heroClasses', 'q', 'classId'));
    }

    public function create(Request $request): View
    {
        $data = $this->formData(new Skill);

        return $request->expectsJson()
            ? view('admin.skills._form', $data)
            : view('admin.skills.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request);

        $skill = Skill::create($data);
        $skill->classes()->sync($request->input('classes', []));
        $this->syncPrerequisites($skill, $request);

        return $this->respond($request, 'Fertigkeit wurde angelegt.');
    }

    public function edit(Skill $skill, Request $request): View
    {
        $data = $this->formData($skill);

        return $request->expectsJson()
            ? view('admin.skills._form', $data)
            : view('admin.skills.edit', $data);
    }

    public function update(Request $request, Skill $skill): RedirectResponse|JsonResponse
    {
        // Alle Voraussetzungen aller Klassen gesammelt für Zyklen-Prüfung.
        $allPrereqIds = collect($request->input('prereqs', []))
            ->flatten()->map('intval')->filter()->unique()->values()->all();

        if ($this->hasCycle($skill->id, $allPrereqIds)) {
            $msg = 'Voraussetzungen würden einen Kreisbezug erzeugen.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withInput()->with('error', $msg);
        }

        $skill->update($this->validated($request));
        $skill->classes()->sync($request->input('classes', []));
        $this->syncPrerequisites($skill, $request);

        return $this->respond($request, 'Fertigkeit wurde aktualisiert.');
    }

    public function destroy(Request $request, Skill $skill): RedirectResponse|JsonResponse
    {
        if ($skill->heroes()->exists()) {
            $msg = 'Fertigkeit wird von Helden verwendet und kann nicht gelöscht werden.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $skill->classes()->detach();
        $skill->delete();

        return $this->respond($request, 'Fertigkeit wurde gelöscht.');
    }

    /**
     * SKILL-09: Modal-Inhalt – aktive Helden, die diese Fertigkeit erworben haben.
     */
    public function heroes(Skill $skill): \Illuminate\View\View
    {
        $heroes = $skill->heroes()
            ->with('player', 'classes')
            ->whereNull('died')
            ->where('active', true)
            ->orderBy('character_name')
            ->get();

        return view('admin.skills._heroes', compact('skill', 'heroes'));
    }

    private function formData(Skill $skill): array
    {
        $allSkills = Skill::with('heroClass')
            ->orderBy('hero_class_id')
            ->orderBy('level')
            ->orderBy('name')
            ->when($skill->exists, fn ($q) => $q->where('id', '!=', $skill->id))
            ->get();

        // SKILL-10: Voraussetzungen pro Klasse laden.
        $assignedPrerequisitesByClass = [];
        if ($skill->exists) {
            DB::table('skill_class_prerequisites')
                ->where('skill_id', $skill->id)
                ->get()
                ->each(function ($row) use (&$assignedPrerequisitesByClass) {
                    $assignedPrerequisitesByClass[$row->hero_class_id][] = $row->required_skill_id;
                });
        }

        return [
            'skill'                       => $skill,
            'heroClasses'                 => HeroClass::where('disabled', false)->orderBy('name')->get(),
            'perlColors'                  => PerlColor::orderBy('name')->get(),
            'assigned'                    => $skill->exists ? $skill->classes->pluck('id')->all() : [],
            'allSkills'                   => $allSkills,
            'assignedPrerequisitesByClass' => $assignedPrerequisitesByClass,
        ];
    }

    /**
     * SKILL-10: Voraussetzungen pro Klasse speichern.
     * Input: $request->prereqs[hero_class_id][] = skill_id
     */
    private function syncPrerequisites(Skill $skill, Request $request): void
    {
        DB::table('skill_class_prerequisites')->where('skill_id', $skill->id)->delete();

        foreach ($request->input('prereqs', []) as $classId => $skillIds) {
            foreach (array_filter(array_map('intval', (array) $skillIds)) as $reqId) {
                DB::table('skill_class_prerequisites')->insertOrIgnore([
                    'skill_id'          => $skill->id,
                    'hero_class_id'     => (int) $classId,
                    'required_skill_id' => $reqId,
                ]);
            }
        }
    }

    /**
     * BFS-Zyklen-Prüfung: ergibt true, wenn eine der $prerequisiteIds über ihre
     * transitive Voraussetzungskette wieder zu $skillId führt.
     */
    private function hasCycle(int $skillId, array $prerequisiteIds): bool
    {
        $visited = [];
        $queue   = array_values(array_unique($prerequisiteIds));

        while ($current = array_shift($queue)) {
            if ($current === $skillId) {
                return true;
            }
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $next = DB::table('skill_class_prerequisites')
                ->where('skill_id', $current)
                ->pluck('required_skill_id')
                ->unique()
                ->values()
                ->all();
            array_push($queue, ...$next);
        }

        return false;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'ep_costs' => ['required', 'integer', 'min:0'],
            'level' => ['required', 'integer', 'min:1', 'max:10'],
            'hero_class_id' => ['nullable', 'exists:hero_classes,id'],
            'perl_color_id' => ['nullable', 'exists:perl_colors,id'],
            'perl_count' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.skills.index')->with('status', $message);
    }
}
