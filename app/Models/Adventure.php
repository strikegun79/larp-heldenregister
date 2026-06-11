<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adventure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'function_email',
        'location_id',
        'start_at',
        'end_at',
        'loot_ep_day',
        'gamemaster_id',
        'eventleader_id',
        'event_status_id',
        'event_client_id',
        'event_category_id',
        'max_player',
        'waitlist',
        'fee',
        'legacy_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'fee' => 'decimal:2',
        'loot_ep_day' => 'integer',
        'max_player' => 'integer',
        'waitlist' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(EventStatus::class, 'event_status_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(EventClient::class, 'event_client_id');
    }

    public function gamemaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gamemaster_id');
    }

    public function eventleader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eventleader_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(EventVisit::class);
    }

    /**
     * Anmeldungen auf einem regulären Platz (nicht auf der Warteliste).
     */
    public function confirmedBookings(): HasMany
    {
        return $this->bookings()->where('waitlisted', false);
    }

    /**
     * Ist die Anmeldung grundsätzlich geöffnet (Status "Anmeldung offen")?
     */
    public function registrationOpen(): bool
    {
        return $this->event_status_id === EventStatus::REGISTRATION_OPEN;
    }

    /**
     * Ist der Check-in erlaubt? Erst ab „Anmeldung geschlossen" (Status ≥ 40, ADV-14).
     */
    public function checkinAllowed(): bool
    {
        return (int) $this->event_status_id >= EventStatus::REGISTRATION_CLOSED;
    }

    /**
     * Anzahl freier regulärer Plätze.
     */
    public function freeSlots(): int
    {
        return max(0, $this->max_player - $this->confirmedBookings()->count());
    }

    /**
     * Sind alle regulären Plätze belegt?
     */
    public function isFull(): bool
    {
        return $this->freeSlots() === 0;
    }
}
