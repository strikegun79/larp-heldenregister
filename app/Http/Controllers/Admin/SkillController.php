<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroClass;
use App\Models\PerlColor;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->withCount(['classes', 'heroes'])
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
        $skill->update($this->validated($request));
        $skill->classes()->sync($request->input('classes', []));

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

    private function formData(Skill $skill): array
    {
        return [
            'skill' => $skill,
            'heroClasses' => HeroClass::where('disabled', false)->orderBy('name')->get(),
            'perlColors' => PerlColor::orderBy('name')->get(),
            'assigned' => $skill->exists ? $skill->classes->pluck('id')->all() : [],
        ];
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
