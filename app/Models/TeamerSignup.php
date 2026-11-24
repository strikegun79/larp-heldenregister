<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamerSignup extends Model
{
    use HasFactory;

    protected $fillable = [
        'adventure_id',
        'user_id',
        'event_role_id',
        'allergien',
        'medikamente',
        'health_data_consent_at',
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
        'health_data_consent_at' => 'datetime',
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

    /** Issue #19: Pauschale für diese Anmeldung (null = keine Abrechnung möglich). */
    public function compensationAmount(): ?string
    {
        return $this->adventure?->compensationFor($this->event_role_id);
    }

    /**
     * Issue #19: Warum die Abrechnung (noch) nicht heruntergeladen werden kann – null = möglich.
     * Fehlende Profilangaben werden separat über User::missingTeamerBillingFields() gemeldet.
     */
    public function compensationBlocker(): ?string
    {
        if ($this->approved_at === null) {
            return 'Die Abrechnung ist möglich, sobald deine Teamer-Anmeldung bestätigt ist.';
        }
        if ($this->event_role_id === null) {
            return 'Dir ist für diese Veranstaltung noch keine Teamer-Rolle zugewiesen.';
        }
        if ($this->compensationAmount() === null) {
            return 'Für deine Rolle ist bei dieser Veranstaltung keine Aufwandsentschädigung festgelegt.';
        }

        return null;
    }

    /** @return BelongsTo<Adventure, $this> */
    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventRole(): BelongsTo
    {
        return $this->belongsTo(EventRole::class);
    }
}
