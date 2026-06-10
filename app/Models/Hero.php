<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hero extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'character_name',
        'born',
        'died',
        'homeplace',
        'active',
        'legacy_id',
    ];

    protected $casts = [
        'born' => 'date',
        'died' => 'date',
        'active' => 'boolean',
    ];

    /**
     * Spieler hinter dem Helden.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Klassen des Helden (Legacy: hero2classes).
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(HeroClass::class);
    }

    /**
     * Gelernte Fertigkeiten (Legacy: hero2skill).
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('trained_at');
    }

    /**
     * EP-Transaktionsbuch (Legacy: hero2ep).
     */
    public function epTransactions(): HasMany
    {
        return $this->hasMany(EpTransaction::class);
    }

    /**
     * Aktueller EP-Saldo (Gutschriften minus Kosten).
     * Ersetzt die Legacy-View view_heroT1Statistic.
     */
    public function getEpBalanceAttribute(): float
    {
        return $this->epTransactions
            ->reduce(fn (float $carry, EpTransaction $tx) => $carry + $tx->signedAmount(), 0.0);
    }

    /**
     * Insgesamt erworbene EP (nur Gutschriften, ohne Abzüge).
     */
    public function getEpTotalAttribute(): float
    {
        return $this->epTransactions
            ->filter(fn (EpTransaction $tx) => $tx->type?->is_credit)
            ->sum(fn (EpTransaction $tx) => (float) $tx->ep_count);
    }

    /**
     * Komma-separierte Klassen-Slugs (ersetzt GROUP_CONCAT aus view_heroT1).
     */
    public function getClassListAttribute(): string
    {
        return $this->classes->pluck('slug')->implode(', ');
    }
}
