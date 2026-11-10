<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsletterSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'token',
        'confirmed_at',
        'consent_text',
        'consented_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'confirmed_at'    => 'datetime',
        'consented_at'    => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subscription): void {
            if (empty($subscription->token)) {
                $subscription->token = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(NewsletterSend::class, 'subscription_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->isConfirmed() && $this->unsubscribed_at === null;
    }

    /** Öffentlicher URL für den Double-Opt-in-Bestätigungslink. */
    public function getConfirmUrlAttribute(): string
    {
        return route('newsletter.confirm', ['token' => $this->token]);
    }
}
