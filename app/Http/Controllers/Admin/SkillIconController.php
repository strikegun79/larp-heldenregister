<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Support\SkillIconStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * SKILL-08: Fertigkeits-Symbol hochladen und löschen.
 */
class SkillIconController extends Controller
{
    public function store(Request $request, Skill $skill): RedirectResponse|JsonResponse
    {
        $request->validate([
            'icon' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($skill->icon) {
            Storage::disk('public')->delete($skill->icon);
        }

        $skill->update([
            'icon' => SkillIconStorage::store($request->file('icon'), 'skills/icons'),
        ]);

        $message = 'Symbol hochgeladen.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    public function destroy(Request $request, Skill $skill): RedirectResponse|JsonResponse
    {
        if ($skill->icon) {
            Storage::disk('public')->delete($skill->icon);
            $skill->update(['icon' => null]);
        }

        $message = 'Symbol gelöscht.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }
}
