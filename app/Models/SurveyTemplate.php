<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $target_group Zielgruppe, siehe TARGET_GROUPS (inkl. „all“)
 */
class SurveyTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'target_group',
        'active',
        'section_pages',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        // Issue #46: Bereiche als einzelne Seiten anzeigen.
        'section_pages' => 'boolean',
    ];

    public const TARGET_GROUPS = [
        'all' => 'Alle Gruppen',
        'participant_child' => 'Teilnehmer (Kinder 8–12)',
        'participant_teen' => 'Teilnehmer (Jugendliche 13–17)',
        'teamer' => 'Teamer',
        'parent' => 'Eltern / Erziehungsberechtigte',
    ];

    /** Zielgruppen, die den Wizard (eine Frage pro Schritt) erhalten. */
    public const WIZARD_GROUPS = ['participant_child', 'participant_teen'];

    /**
     * Aktive Fragen (Ausfüllen, Editor, Fragenanzahl).
     *
     * @return HasMany<SurveyTemplateQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyTemplateQuestion::class)
            ->whereNull('archived_at')
            ->orderBy('sort_order');
    }

    /**
     * Issue #35: Alle Fragen inkl. archivierter – für Auswertung, Einzelantwort und PDF,
     * damit Antworten auf später entfernte Fragen sichtbar bleiben.
     *
     * @return HasMany<SurveyTemplateQuestion, $this>
     */
    public function questionsWithArchived(): HasMany
    {
        return $this->hasMany(SurveyTemplateQuestion::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Issue #46: Fragen nach Bereichen gruppieren (Bereiche werden fortlaufend nummeriert).
     * Fragen vor dem ersten Bereich bilden eine Gruppe ohne Bereich.
     *
     * @param  Collection<int, SurveyTemplateQuestion>  $items
     * @return list<array{section: SurveyTemplateQuestion|null, number: int|null, questions: list<SurveyTemplateQuestion>}>
     */
    public static function groupBySection(Collection $items): array
    {
        $groups = [];
        $current = ['section' => null, 'number' => null, 'questions' => []];
        $number = 0;

        foreach ($items as $item) {
            if ($item->isSection()) {
                if ($current['section'] !== null || $current['questions'] !== []) {
                    $groups[] = $current;
                }
                $current = ['section' => $item, 'number' => ++$number, 'questions' => []];

                continue;
            }
            $current['questions'][] = $item;
        }

        if ($current['section'] !== null || $current['questions'] !== []) {
            $groups[] = $current;
        }

        return $groups;
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
