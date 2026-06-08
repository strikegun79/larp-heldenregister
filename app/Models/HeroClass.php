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
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }
}
