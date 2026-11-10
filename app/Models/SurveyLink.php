<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SurveyLink extends Model
{
    protected $fillable = [
        'survey_id',
        'token',
        'target_type',
        'player_id',
        'name',
        'email',
        'expires_at',
        'completed_at',
        'sent_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'completed_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SurveyLink $link): void {
            if (empty($link->token)) {
                $link->token = (string) Str::uuid();
            }
        });
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(SurveyResponse::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isCompleted() && ! $this->isExpired();
    }

    /** Gibt den öffentlichen Formular-URL zurück. */
    public function getUrlAttribute(): string
    {
        return route('survey.start', ['token' => $this->token]);
    }

    /** Wizard für Kinder/Teens, Scroll-Seite für Teamer/Eltern. */
    public function usesWizard(): bool
    {
        return in_array($this->target_type, SurveyTemplate::WIZARD_GROUPS, true);
    }
}
