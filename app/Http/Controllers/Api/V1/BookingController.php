<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * ARCH-007: Buchungs-API – nur eigene Buchungen (auth:sanctum).
 * Gibt alle Buchungen zurück, die über eigene Spieler des Nutzers laufen.
 */
class BookingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $playerIds = $request->user()->players()->pluck('players.id');

        $bookings = Booking::with('adventure')
            ->whereIn('player_id', $playerIds)
            ->orderByDesc('created_at')
            ->paginate(50);

        return BookingResource::collection($bookings);
    }
}
