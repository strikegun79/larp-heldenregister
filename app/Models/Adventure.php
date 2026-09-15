<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\EventRole;

class Adventure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kuerzel',
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
        'min_age',
        'max_age',
        'waitlist',
        'waitlist_mode',
        'is_hidden',
        'fee',
        'fee_reduced',
        'legacy_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'fee' => 'decimal:2',
        'fee_reduced' => 'decimal:2',
        'loot_ep_day' => 'integer',
        'max_player' => 'integer',
        'min_age'    => 'integer',
        'max_age'    => 'integer',
        'waitlist' => 'integer',
        'waitlist_mode' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    /** Gibt die Altersbeschränkung als lesbaren String zurück, z. B. „8–17 Jahre". */
    public function getAgeRangeLabelAttribute(): string
    {
        if ($this->min_age && $this->max_age) {
            return "{$this->min_age}–{$this->max_age} Jahre";
        }
        if ($this->min_age) {
            return "ab {$this->min_age} Jahren";
        }
        if ($this->max_age) {
            return "bis {$this->max_age} Jahren";
        }
        return '—';
    }

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
     * Soll die nächste Buchung auf die Warteliste?
     * Ja, wenn das Event voll ist ODER noch jemand auf der Warteliste wartet
     * (damit keine neue Buchung eine wartende Person "überholt").
     * waitlist_mode allein ohne aktive Warteliste blockiert nicht mehr.
     */
    public function shouldWaitlist(): bool
    {
        if ($this->isFull()) {
            return true;
        }

        return $this->waitlist_mode
            && $this->bookings()->where('waitlisted', true)->exists();
    }

    /**
     * Ist die Anmeldung grundsätzlich geöffnet (Status "Anmeldung offen")?
     */
    public function registrationOpen(): bool
    {
        return $this->event_status_id === EventStatus::REGISTRATION_OPEN;
    }

    /**
     * Beitrags-Zusammenfassung für die Verwaltungsansicht.
     * Berücksichtigt pro Buchung den ermäßigten oder regulären Preis.
     *
     * @return array{paid_count:int,total_count:int,paid_amount:float,open_amount:float,total_amount:float,erm_count:int}
     */
    public function paymentSummary(): array
    {
        $payable = $this->bookings
            ->where('waitlisted', false)
            ->whereNotIn('event_role_id', EventRole::teamerLike()->pluck('id'));
        $paidAmount = 0.0;
        $openAmount = 0.0;

        foreach ($payable as $booking) {
            $fee = ($booking->ermaessigung && $this->fee_reduced !== null)
                ? (float) $this->fee_reduced
                : (float) $this->fee;

            if ($booking->paid) {
                $paidAmount += $fee;
            } else {
                $openAmount += $fee;
            }
        }

        return [
            'paid_count'   => $payable->where('paid', true)->count(),
            'total_count'  => $payable->count(),
            'paid_amount'  => $paidAmount,
            'open_amount'  => $openAmount,
            'total_amount' => $paidAmount + $openAmount,
            'erm_count'    => $payable->where('ermaessigung', true)->count(),
        ];
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
        // Teamer-Rollen zählen nicht gegen das Teilnehmerlimit (sie können sich immer anmelden).
        return max(0, $this->max_player - $this->confirmedBookings()
            ->whereNotIn('event_role_id', EventRole::teamerLike()->pluck('id'))
            ->count());
    }

    /**
     * Sind alle regulären Plätze belegt?
     */
    public function isFull(): bool
    {
        return $this->freeSlots() === 0;
    }

    /**
     * Liegt das Alter des Spielers außerhalb des festgelegten Altersrahmens?
     * Gibt false zurück, wenn keine Altersgrenzen gesetzt sind oder das Geburtsdatum fehlt.
     */
    public function isOutsideAgeRange(?Player $player): bool
    {
        if (! $player || (! $this->min_age && ! $this->max_age)) {
            return false;
        }
        $age = $player->dayofbirth?->age;
        if ($age === null) {
            return false;
        }

        return ($this->min_age && $age < $this->min_age)
            || ($this->max_age && $age > $this->max_age);
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
