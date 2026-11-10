<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'target_group',
        'active',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public const TARGET_GROUPS = [
        'all'               => 'Alle Gruppen',
        'participant_child' => 'Teilnehmer (Kinder 8–12)',
        'participant_teen'  => 'Teilnehmer (Jugendliche 13–17)',
        'teamer'            => 'Teamer',
        'parent'            => 'Eltern / Erziehungsberechtigte',
    ];

    /** Zielgruppen, die den Wizard (eine Frage pro Schritt) erhalten. */
    public const WIZARD_GROUPS = ['participant_child', 'participant_teen'];

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyTemplateQuestion::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /** Soll dieser Template-Typ den Wizard anzeigen? */
    public function usesWizard(): bool
    {
        return in_array($this->target_group, self::WIZARD_GROUPS, true);
    }

    public function getTargetGroupLabelAttribute(): string
    {
        return self::TARGET_GROUPS[$this->target_group] ?? $this->target_group;
    }
}
