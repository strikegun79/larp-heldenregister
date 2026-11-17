<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mews\Purifier\Facades\Purifier;

/**
 * Zusätzliche Anforderung eines Auftraggebers (Issue #14), die bei der
 * Anmeldung zu dessen Events wie eine AGB bestätigt werden muss.
 */
class EventClientRequirement extends Model
{
    protected $fillable = [
        'event_client_id',
        'title',
        'description',
        'pdf_path',
        'pdf_name',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(EventClient::class, 'event_client_id');
    }

    /** Anmeldungen, bei denen dieser Anforderung zugestimmt wurde. */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_requirement_consents')
            ->withPivot('accepted_at')
            ->withTimestamps();
    }

    /** Bereinigte, formatierte Beschreibung zur Ausgabe im Popup. */
    public function getDescriptionHtmlAttribute(): string
    {
        return Purifier::clean((string) $this->description, 'requirement');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
