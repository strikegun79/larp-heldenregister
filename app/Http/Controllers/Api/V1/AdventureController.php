<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdventureResource;
use App\Models\Adventure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * ARCH-007: Abenteuer-API (auth:sanctum erforderlich).
 * Wendet denselben Sichtbarkeits-Scope wie die Web-Oberfläche an.
 */
class AdventureController extends Controller
{
    /** Liste der für den Nutzer sichtbaren Abenteuer (paginiert). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $adventures = Adventure::with(['location', 'status', 'category'])
            ->withCount('confirmedBookings')
            ->visibleFor($request->user())
            ->orderByDesc('start_at')
            ->paginate(50);

        return AdventureResource::collection($adventures);
    }

    /** Einzelnes Abenteuer – nur wenn für den Nutzer sichtbar. */
    public function show(Request $request, Adventure $adventure): AdventureResource
    {
        if (! $adventure->isVisibleFor($request->user())) {
            abort(404);
        }

        $adventure->loadCount('confirmedBookings')->load(['location', 'status', 'category']);

        return new AdventureResource($adventure);
    }
}
