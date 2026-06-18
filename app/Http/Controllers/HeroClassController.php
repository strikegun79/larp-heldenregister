<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use App\Models\HeroClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Klassenzuordnung eines Helden mit EP-Verbuchung (HERO-06).
 * Hinzufügen kostet EP (Typ 40 „Klasse hinzugefügt"); Entfernen erstattet
 * die Kosten (Typ 60 Gutschrift). Analog zu HeroSkillController.
 */
class HeroClassController extends Controller
{
    /** EP-Buchungsart „Klasse hinzugefügt" (Kosten). */
    private const CLASS_COST_TYPE = 40;

    /** EP-Buchungsart „Allgemein" (Gutschrift) – Rückerstattung beim Entfernen. */
    private const CLASS_REFUND_TYPE = 60;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:heldenregister.edit');
    }

    /**
     * Fügt einem Helden eine Klasse hinzu und bucht die EP-Kosten ab.
     * Der Saldo darf nicht negativ werden – außer der Admin übersteuert.
     */
    public function store(Request $request, Hero $hero): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'hero_class_id' => ['required', 'exists:hero_classes,id'],
            'free' => ['boolean'],
        ]);

        $class = HeroClass::findOrFail($data['hero_class_id']);

        if ($class->disabled) {
            return $this->fail($request, 'Diese Klasse ist deaktiviert.');
        }

        if ($hero->classes()->whereKey($class->id)->exists()) {
            return $this->fail($request, 'Diese Klasse besitzt der Held bereits.');
        }

        // HERO-20: „Korrektur" fügt ohne EP-Abzug hinzu (z. B. versehentlich entfernt).
        $free = $request->boolean('free');
        $cost = $free ? 0 : $class->ep_cost;

        // Saldo-Schutz; Admin darf den Helden ins Minus setzen (Override).
        if ($cost > 0 && ! $request->user()->isAdmin() && $hero->ep_balance < $cost) {
            return $this->fail($request, 'Nicht genug EP für diese Klasse.');
        }

        DB::transaction(function () use ($hero, $class, $cost) {
            $hero->classes()->attach($class->id);
            if ($cost > 0) {
                $hero->epTransactions()->create([
                    'ep_transaction_type_id' => self::CLASS_COST_TYPE,
                    'ep_count' => $cost,
                    'transacted_at' => now(),
                ]);
            }
        });

        $message = $free
            ? "Klasse „{$class->name}“ kostenfrei hinzugefügt (Korrektur)."
            : "Klasse „{$class->name}“ hinzugefügt (−{$cost} EP).";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Entfernt eine Klasse vom Helden und erstattet die EP-Kosten als Gutschrift.
     */
    public function destroy(Request $request, Hero $hero, HeroClass $heroClass): JsonResponse|RedirectResponse
    {
        if (! $hero->classes()->whereKey($heroClass->id)->exists()) {
            return $this->fail($request, 'Diese Klasse besitzt der Held nicht.');
        }

        DB::transaction(function () use ($hero, $heroClass) {
            $hero->classes()->detach($heroClass->id);
            $hero->epTransactions()->create([
                'ep_transaction_type_id' => self::CLASS_REFUND_TYPE,
                'ep_count' => $heroClass->ep_cost,
                'transacted_at' => now(),
            ]);
        });

        $message = "Klasse „{$heroClass->name}“ entfernt (+{$heroClass->ep_cost} EP).";

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
