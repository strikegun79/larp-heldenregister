<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'dayofbirth',
        'gender',
        'active',
        'active_hero_id',
        'legacy_id',
    ];

    protected $casts = [
        'dayofbirth' => 'date',
        'active' => 'boolean',
    ];

    /**
     * Helden dieses Spielers.
     */
    public function heroes(): HasMany
    {
        return $this->hasMany(Hero::class);
    }

    /**
     * Der aktuell aktive Held (Legacy: player.hero_active).
     */
    public function activeHero(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'active_hero_id');
    }

    /**
     * Benutzer, denen dieser Spieler zugeordnet ist (Legacy: user2player).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('self');
    }

    /**
     * Voller Name des Spielers.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->lastname}");
    }
}
