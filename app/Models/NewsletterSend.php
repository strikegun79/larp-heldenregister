<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NewsletterSend extends Model
{
    protected $fillable = [
        'newsletter_id',
        'subscription_id',
        'token',
        'sent_at',
        'opened_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at'    => 'datetime',
        'opened_at'  => 'datetime',
        'failed_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $send): void {
            if (empty($send->token)) {
                $send->token = (string) Str::uuid();
            }
        });
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(Newsletter::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscription::class, 'subscription_id');
    }

    /** Öffentlicher 1-Klick-Abmeldelink für den Footer jeder Newsletter-Mail. */
    public function getUnsubscribeUrlAttribute(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->token]);
    }
}
