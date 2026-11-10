<?php

namespace App\Http\Resources\Api;

use App\Models\Hero;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ARCH-007: Öffentliche Helden-Repräsentation (PUB-02-Kontrakt).
 * Enthält bewusst KEINEN Realnamen, keine Spieler-ID, keine internen Daten.
 *
 * @mixin Hero
 */
class HeroResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_code' => $this->public_code,
            'character_name' => $this->character_name,
            'homeplace' => $this->homeplace,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'active' => $this->active,
            'born' => $this->born?->toDateString(),
            'died' => $this->died?->toDateString(),
            'class_summary' => $this->when(
                $this->relationLoaded('classes'),
                fn () => $this->classes->pluck('name')
            ),
            'skill_count' => $this->when(
                $this->relationLoaded('skills'),
                fn () => $this->skills->count()
            ),
        ];
    }
}
