<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class HeroClass extends Model
{
    /** IDs stammen aus dem Legacy-System (type_classes). */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'slug',
        'name',
        'disabled',
        'ep_cost',
        'ribbon_color',
        'ribbon_image',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'ep_cost' => 'integer',
    ];

    /**
     * Helden dieser Klasse (Legacy: hero2classes).
     */
    public function heroes(): BelongsToMany
    {
        return $this->belongsToMany(Hero::class);
    }

    /**
     * Fertigkeiten dieser Klasse (Legacy: skills2class).
     * Pivot-Tabelle explizit, da der Default-Name (hero_class_skill) abweicht.
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_hero_class')
            ->withPivot('x_percentage', 'y_percentage');
    }

    /**
     * Pfad zum Fertigkeitsbaum-Bild (public/images/skilltree_*.jpg).
     * Slug-Sonderfall: „wizard" -> Bild „mage".
     */
    public function skilltreeImage(): string
    {
        $map = ['wizard' => 'mage'];
        $slug = $map[$this->slug] ?? $this->slug;

        return "/images/skilltree_{$slug}.jpg";
    }

    /**
     * URL des Klassenband-Bilds (162×600 px).
     * Vorrang: hochgeladenes Bild → Fallback auf Klassenbanner-{Name}.png in public/images/.
     */
    public function ribbonImageUrl(): ?string
    {
        if ($this->ribbon_image) {
            return Storage::disk('public')->url($this->ribbon_image);
        }

        $fallback = public_path("images/Klassenbanner-{$this->name}.png");
        if (file_exists($fallback)) {
            return asset("images/Klassenbanner-{$this->name}.png");
        }

        return null;
    }
}
