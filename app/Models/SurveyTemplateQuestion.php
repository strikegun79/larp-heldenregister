<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyTemplateQuestion extends Model
{
    protected $fillable = [
        'survey_template_id',
        'question_text',
        'type',
        'sort_order',
        'required',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public const TYPES = [
        'rating' => 'Bewertung (1–10)',
        'text'   => 'Freitext',
        'yes_no' => 'Ja / Nein',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
