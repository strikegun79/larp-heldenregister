<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRole extends Model
{
    /** IDs stammen aus dem Legacy-System (type_event_role). */
    public $incrementing = false;

    /** NSC-Elternteil-Rolle — wird separat im Teamer/NSC-Tab gelistet (ADV-29). */
    public const NSC_ROLE_ID = 2;

    protected $fillable = [
        'id',
        'description',
        'for_participant',
        'for_teamer',
    ];

    protected $casts = [
        'for_participant' => 'boolean',
        'for_teamer'      => 'boolean',
    ];

    public function scopeForParticipant(Builder $query): Builder
    {
        return $query->where('for_participant', true);
    }

    public function scopeForTeamer(Builder $query): Builder
    {
        return $query->where('for_teamer', true);
    }

    /** Alle Rollen, die wie Teamer behandelt werden: kein Beitrag, kein Listenplatz, keine Warteliste. */
    public function scopeTeamerLike(Builder $query): Builder
    {
        return $query->where('for_teamer', true)->orWhere('id', self::NSC_ROLE_ID);
    }

    public function getIsTeamerLikeAttribute(): bool
    {
        return $this->for_teamer || $this->id === self::NSC_ROLE_ID;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
