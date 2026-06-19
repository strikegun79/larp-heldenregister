<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ep_costs',
        'level',
        'hero_class_id',
        'perl_color_id',
        'perl_count',
        'icon',
        'legacy_id',
    ];

    protected $casts = [
        'ep_costs' => 'integer',
        'perl_count' => 'integer',
    ];

    /**
     * Masterclass der Fertigkeit (Legacy: skills.masterclass).
     */
    public function heroClass(): BelongsTo
    {
        return $this->belongsTo(HeroClass::class);
    }

    /**
     * Perlenfarbe (Legacy: skills.perlcolor).
     */
    public function perlColor(): BelongsTo
    {
        return $this->belongsTo(PerlColor::class);
    }

    /**
     * Klassen, die diese Fertigkeit nutzen können (Legacy: skills2class).
     * Pivot-Tabelle explizit, da der Default-Name (hero_class_skill) abweicht.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(HeroClass::class, 'skill_hero_class');
    }

    /**
     * Helden, die diese Fertigkeit gelernt haben.
     */
    public function heroes(): BelongsToMany
    {
        return $this->belongsToMany(Hero::class)->withPivot('trained_at');
    }

    /**
     * Fertigkeiten, die ein Held bereits kennen muss, bevor er diese lernen kann.
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_prerequisites', 'skill_id', 'required_skill_id');
    }

    /**
     * Fertigkeiten, für die diese Fertigkeit Voraussetzung ist.
     */
    public function prerequisiteFor(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_prerequisites', 'required_skill_id', 'skill_id');
    }
}
