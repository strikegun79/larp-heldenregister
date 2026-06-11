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
        'description',
        'image',
        'active',
        'legacy_id',
    ];

    protected $casts = [
        'born' => 'date',
        'died' => 'date',
        'active' => 'boolean',
    ];

    /** Öffentliche URL des Avatar-Bilds (HERO-09) bzw. null. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image) : null;
    }

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

    /** EP-Buchungsart „Abenteuer bestritten". */
    public const ADVENTURE_EP_TYPE = 50;

    /**
     * Abenteuerhistorie: EP-Buchungen vom Typ „Abenteuer bestritten", die mit
     * einem Abenteuer verknüpft sind – chronologisch (neueste zuerst).
     */
    public function getAdventureHistoryAttribute(): \Illuminate\Support\Collection
    {
        return $this->epTransactions
            ->where('ep_transaction_type_id', self::ADVENTURE_EP_TYPE)
            ->whereNotNull('adventure_id')
            ->sortByDesc('transacted_at')
            ->values();
    }

    /**
     * Summe der aus Abenteuern erhaltenen EP.
     */
    public function getAdventuresEpTotalAttribute(): float
    {
        return $this->adventure_history->sum(fn (EpTransaction $tx) => (float) $tx->ep_count);
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
