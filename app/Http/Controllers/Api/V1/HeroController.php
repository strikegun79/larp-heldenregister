<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\HeroResource;
use App\Models\Hero;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * ARCH-007: Öffentliche Helden-API (kein Auth erforderlich).
 * Liefert nur Helden mit public_visible=true (PUB-02-Kontrakt).
 */
class HeroController extends Controller
{
    /** Liste öffentlich sichtbarer Helden (paginiert). */
    public function index(): AnonymousResourceCollection
    {
        $heroes = Hero::with(['classes', 'skills'])
            ->where('public_visible', true)
            ->where('active', true)
            ->orderBy('character_name')
            ->paginate(50);

        return HeroResource::collection($heroes);
    }

    /** Einzelner Held anhand seines öffentlichen Codes. */
    public function show(string $code): HeroResource
    {
        $hero = Hero::with(['classes', 'skills'])
            ->where('public_code', $code)
            ->where('public_visible', true)
            ->firstOrFail();

        return new HeroResource($hero);
    }
}
