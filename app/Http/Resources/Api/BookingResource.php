<?php

namespace App\Http\Resources\Api;

use App\Models\Adventure;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ARCH-007: Buchungs-Repräsentation für den Buchungsinhaber.
 * Enthält bewusst KEINE sensiblen Gesundheits-/Kontaktdaten
 * (allergien, medikamente, erreichbarkeit, kontakt_telefon, signature).
 *
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adventure' => $this->when(
                $this->relationLoaded('adventure') && $this->adventure,
                function () {
                    $adv = $this->adventure;
                    if (! $adv instanceof Adventure) {
                        return null;
                    }

                    return [
                        'id' => $adv->id,
                        'name' => $adv->name,
                        'start_at' => $adv->start_at->toIso8601String(),
                    ];
                }
            ),
            'event_role' => $this->event_role_id,
            'status' => $this->status,
            'waitlisted' => $this->waitlisted,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'paid' => $this->paid,
        ];
    }
}
