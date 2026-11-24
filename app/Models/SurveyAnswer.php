<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string>|null $choice_answer
 */
class SurveyAnswer extends Model
{
    protected $fillable = [
        'survey_response_id',
        'survey_template_question_id',
        'rating_answer',
        'text_answer',
        'yes_no_answer',
        'choice_answer',
    ];

    protected $casts = [
        'yes_no_answer' => 'boolean',
        'choice_answer' => 'array',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplateQuestion::class, 'survey_template_question_id');
    }
}
