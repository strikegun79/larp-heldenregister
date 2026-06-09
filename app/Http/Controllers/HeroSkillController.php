<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeroSkillController extends Controller
{
    /** EP-Buchungsart „Fertigkeit erworben" (Kosten). */
    private const SKILL_COST_TYPE = 20;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:heldenregister.edit');
    }

    /**
     * Lässt einen Helden eine Fertigkeit erlernen: legt die Pivot-Verknüpfung an
     * und bucht die EP-Kosten ab (HERO-14). Atomar in einer Transaktion.
     */
    public function store(Request $request, Hero $hero): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'skill_id' => ['required', 'exists:skills,id'],
        ]);

        $skill = Skill::findOrFail($data['skill_id']);

        if ($hero->skills()->whereKey($skill->id)->exists()) {
            return $this->fail($request, 'Diese Fertigkeit wurde bereits erlernt.');
        }

        if ($hero->ep_balance < $skill->ep_costs) {
            return $this->fail($request, 'Nicht genug EP für diese Fertigkeit.');
        }

        DB::transaction(function () use ($hero, $skill) {
            $hero->skills()->attach($skill->id, ['trained_at' => now()]);
            $hero->epTransactions()->create([
                'ep_transaction_type_id' => self::SKILL_COST_TYPE,
                'ep_count' => $skill->ep_costs,
                'transacted_at' => now(),
            ]);
        });

        $message = "„{$skill->name}“ erlernt (−{$skill->ep_costs} EP).";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    private function fail(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('error', $message);
    }
}
