<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'adventure_id',
        'player_id',
        'event_role_id',
        'fotoerlaubnis',
        'vegetarier',
        'leih_tunika',
        'leih_waffe',
        'nsc',
        'agb',
        'paid',
        'allergien',
        'medikamente',
        'erreichbarkeit',
        'signature',
        'waitlisted',
        'approved_at',
    ];

    protected $casts = [
        'fotoerlaubnis' => 'boolean',
        'vegetarier' => 'boolean',
        'leih_tunika' => 'boolean',
        'leih_waffe' => 'boolean',
        'nsc' => 'boolean',
        'agb' => 'boolean',
        'paid' => 'boolean',
        'waitlisted' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(EventRole::class, 'event_role_id');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }
}
