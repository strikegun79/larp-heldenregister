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
        'hero_id',
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
        'status',
        'absence_reason',
    ];

    /** Anmelde-Status (ADV-18) → Anzeige-Label. */
    public const STATUS_LABELS = [
        'offen' => 'offen',
        'bestaetigt' => 'bestätigt',
        'abgelehnt' => 'abgelehnt',
        'abgemeldet' => 'abgemeldet',
    ];

    /** Abwesenheitsgründe bei „abgemeldet". */
    public const ABSENCE_REASONS = [
        'krank' => 'Krank',
        'nicht_erschienen' => 'nicht erschienen',
        'unentschuldigt' => 'unentschuldigt',
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

    /** Lesbares Status-Label (ADV-18). */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status ?? 'offen';
    }

    /** Lesbarer Abwesenheitsgrund. */
    public function getAbsenceReasonLabelAttribute(): ?string
    {
        return $this->absence_reason ? (self::ABSENCE_REASONS[$this->absence_reason] ?? $this->absence_reason) : null;
    }

    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
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
