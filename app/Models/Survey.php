<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    protected $fillable = [
        'adventure_id',
        'survey_template_id',
        'title',
        'status',
        'sent_at',
        'closes_at',
        'created_by',
    ];

    protected $casts = [
        'sent_at'   => 'datetime',
        'closes_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft'  => 'Entwurf',
        'active' => 'Aktiv',
        'closed' => 'Geschlossen',
    ];

    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function links(): HasMany
    {
        return $this->hasMany(SurveyLink::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Anzahl bereits ausgefüllter Links. */
    public function completedCount(): int
    {
        return $this->links()->whereNotNull('completed_at')->count();
    }

    /** Gesamtanzahl versendeter Links. */
    public function totalCount(): int
    {
        return $this->links()->count();
    }
}
