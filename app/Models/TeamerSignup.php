<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamerSignup extends Model
{
    use HasFactory;

    /** Mögliche Teamer-Rollen, die der Projektleiter zuweisen kann. */
    public const ROLES = [
        'Spielleitung',
        'Projektleiter',
        'Teamer A',
        'Teamer B',
        'Teamer C',
        'Bürokrat',
    ];

    protected $fillable = [
        'adventure_id',
        'user_id',
        'teamer_role',
        'allergien',
        'medikamente',
        'kontakt_telefon',
        'agb',
        'leih_tunika',
        'leih_waffe',
        'anmerkung',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'agb' => 'boolean',
        'leih_tunika' => 'boolean',
        'leih_waffe' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function getStatusLabelAttribute(): string
    {
        if ($this->approved_at) {
            return 'bestätigt';
        }
        if ($this->rejected_at) {
            return 'abgelehnt';
        }

        return 'offen';
    }

    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
