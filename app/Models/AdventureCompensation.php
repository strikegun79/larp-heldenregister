<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Issue #19: Pauschale (Aufwandsentschädigung) einer abrechnungsfähigen Rolle für eine Veranstaltung.
 */
class AdventureCompensation extends Model
{
    // „compensation“ gilt im Pluralizer als unzählbar → Tabellenname ausdrücklich setzen.
    protected $table = 'adventure_compensations';

    protected $fillable = [
        'adventure_id',
        'event_role_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    public function eventRole(): BelongsTo
    {
        return $this->belongsTo(EventRole::class);
    }
}
