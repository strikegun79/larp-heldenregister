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
        'booked_by_user_id',
        'guest_name',
        'guest_lastname',
        'guest_age',
        'guest_place',
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
        'kontakt_telefon',
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

    /** Gast-Anmeldung ohne hinterlegten Spieler (ADV-21). */
    public function getIsGuestAttribute(): bool
    {
        return $this->player_id === null;
    }

    /** Anzeigename: Spieler oder Gast (ADV-21). */
    public function getParticipantNameAttribute(): string
    {
        return $this->is_guest
            ? trim("{$this->guest_name} {$this->guest_lastname}")
            : ($this->player?->full_name ?? '—');
    }

    /** Alter des Teilnehmers: Spieler-Alter oder Gast-Alter (PLAY-07). */
    public function getParticipantAgeAttribute(): ?int
    {
        return $this->is_guest ? $this->guest_age : $this->player?->age;
    }

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

    /** Erziehungsberechtigte Person, die die Anmeldung vorgenommen hat (ORGA-01). */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    /**
     * Gibt den Erziehungsberechtigten zurück (ADV-25):
     * bevorzugt den anmeldenden Nutzer, Fallback auf den ersten
     * zugeordneten Betreuer des Spielers.
     */
    public function guardian(): ?User
    {
        return $this->bookedBy ?? $this->player?->users->first();
    }

    /**
     * Gibt an, ob für diesen Teilnehmer die Elternanschrift gilt (ADV-25).
     * Für Gäste immer false (haben keine Spieler-Verknüpfung).
     */
    public function usesGuardianAddress(): bool
    {
        if ($this->is_guest || $this->player === null) {
            return false;
        }

        return (bool) $this->player->address_same_as_guardian;
    }

    /**
     * Stadt/Ort für die Teilnehmerliste (ADV-25 / PDF):
     * Kinder-Ort bei abweichender Anschrift, sonst Ort der erziehungsberechtigten Person.
     */
    public function getEffectiveCityAttribute(): ?string
    {
        if ($this->is_guest) {
            return $this->guest_place;
        }

        if (! $this->usesGuardianAddress() && filled($this->player?->city)) {
            return $this->player->city;
        }

        return $this->guardian()?->city;
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }
}
