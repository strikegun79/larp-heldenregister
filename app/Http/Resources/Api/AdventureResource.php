<?php

namespace App\Http\Resources\Api;

use App\Models\Adventure;
use App\Models\EventCategory;
use App\Models\EventStatus;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ARCH-007: Adventure-Repräsentation für externe Konsumenten.
 * Enthält bewusst KEINE internen Felder (is_hidden, reminder_sent_at,
 * function_email, Spielleiter-Details).
 *
 * @mixin Adventure
 */
class AdventureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at->toIso8601String(),
            'location' => $this->when(
                $this->relationLoaded('location') && $this->location,
                function () {
                    $loc = $this->location;
                    if (! $loc instanceof Location) {
                        return null;
                    }

                    return ['name' => $loc->titel, 'city' => $loc->city];
                }
            ),
            'status' => $this->when(
                $this->relationLoaded('status') && $this->status,
                function () {
                    $st = $this->status;
                    if (! $st instanceof EventStatus) {
                        return null;
                    }

                    return ['id' => $st->id, 'label' => $st->description];
                }
            ),
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category,
                function () {
                    $cat = $this->category;
                    if (! $cat instanceof EventCategory) {
                        return null;
                    }

                    return $cat->name;
                }
            ),
            'max_player' => $this->max_player,
            'confirmed_bookings_count' => $this->whenCounted('confirmedBookings'),
            'fee' => (float) $this->fee,
            'registration_open' => $this->registrationOpen(),
        ];
    }
}
