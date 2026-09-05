<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_link_id',
        'submitted_at',
        'ip_address',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(SurveyLink::class, 'survey_link_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
