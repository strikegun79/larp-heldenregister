<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HeroClass extends Model
{
    /** IDs stammen aus dem Legacy-System (type_classes). */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'slug',
        'name',
        'disabled',
    ];

    protected $casts = [
        'disabled' => 'boolean',
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
}
