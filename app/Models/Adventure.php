<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'reminder_sent_at',
        'event_client_id',
        'event_category_id',
        'max_player',
        'waitlist',
        'is_hidden',
        'fee',
        'legacy_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'fee' => 'decimal:2',
        'loot_ep_day' => 'integer',
        'max_player' => 'integer',
        'waitlist' => 'integer',
        'is_hidden' => 'boolean',
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

    public function teamerSignups(): HasMany
    {
        return $this->hasMany(TeamerSignup::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(EventVisit::class);
    }

    public function epTransactions(): HasMany
    {
        return $this->hasMany(EpTransaction::class);
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
     * Vom aktuellen Status erlaubte Ziel-Status inkl. des aktuellen (ADV-05).
     *
     * @return array<int>
     */
    public function allowedStatusIds(): array
    {
        $current = (int) $this->event_status_id;

        return array_values(array_unique(array_merge([$current], EventStatus::TRANSITIONS[$current] ?? [])));
    }

    /**
     * Ist der Wechsel auf den Zielstatus erlaubt? Gleicher Status ist immer ok.
     */
    public function canTransitionTo(int $statusId): bool
    {
        return in_array($statusId, $this->allowedStatusIds(), true);
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

    /**
     * Scope: nur für diesen Nutzer sichtbare Abenteuer.
     * Verwalter (events.edit) sehen immer alles.
     * Alle anderen sehen nur nicht-ausgeblendete Events – und bei Status
     * „Abgeschlossen" oder „abgesagt" nur dann, wenn sie angemeldet sind.
     *
     * @param  Builder<Adventure>  $query
     */
    public function scopeVisibleFor(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('events.edit')) {
            return $query;
        }

        $playerIds = $user->players()->pluck('players.id');

        return $query
            ->where('is_hidden', false)
            ->where(function (Builder $q) use ($user, $playerIds) {
                // Status weder abgeschlossen noch abgesagt: immer zeigen.
                $q->whereNotIn('event_status_id', [EventStatus::COMPLETED, EventStatus::CANCELLED])
                    // Abgeschlossen/abgesagt: nur zeigen, wenn angemeldet.
                    ->orWhere(function (Builder $reg) use ($user, $playerIds) {
                        $reg->whereHas('teamerSignups', fn (Builder $ts) => $ts->where('user_id', $user->id));
                        if ($playerIds->isNotEmpty()) {
                            $reg->orWhereHas('bookings', fn (Builder $b) => $b->whereIn('player_id', $playerIds->all()));
                        }
                    });
            });
    }

    /**
     * Gibt den Grund zurück, warum das Abenteuer nicht gelöscht werden kann,
     * oder null wenn es löschbar ist.
     * Sperrgründe (in Priorität): Buchungen → Teamer-Anmeldungen → EP-Transaktionen.
     */
    public function deletionBlocker(): ?string
    {
        if ($this->bookings()->exists()) {
            return 'Es gibt bereits Spieler-Anmeldungen für dieses Abenteuer.';
        }
        if ($this->teamerSignups()->exists()) {
            return 'Es gibt bereits Teamer-Anmeldungen für dieses Abenteuer.';
        }
        if ($this->epTransactions()->exists()) {
            return 'Es wurden bereits EP-Transaktionen für dieses Abenteuer erfasst.';
        }

        return null;
    }

    /**
     * Ist dieses Abenteuer für den angegebenen Nutzer sichtbar (für show()-Zugriff).
     */
    public function isVisibleFor(User $user): bool
    {
        if ($user->hasPermission('events.edit')) {
            return true;
        }
        if ($this->is_hidden) {
            return false;
        }
        if (! in_array($this->event_status_id, [EventStatus::COMPLETED, EventStatus::CANCELLED], true)) {
            return true;
        }
        // Abgeschlossen/abgesagt: nur wenn angemeldet.
        $playerIds = $user->players()->pluck('players.id');

        return $this->teamerSignups()->where('user_id', $user->id)->exists()
            || ($playerIds->isNotEmpty() && $this->bookings()->whereIn('player_id', $playerIds->all())->exists());
    }
}
