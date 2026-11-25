<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

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

    /**
     * Validierungsregeln und Meldungen für die Antworten auf die übergebenen Fragen
     * (echtes Ausfüllen und Vorschau, Issue #46).
     *
     * @param  Collection<int, SurveyTemplateQuestion>  $questions  ohne Bereiche
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public static function answerRules(Collection $questions): array
    {
        $rules = [];
        $messages = [];
        foreach ($questions as $question) {
            $key = "answers.{$question->id}";
            $requiredRule = $question->required ? 'required' : 'nullable';

            // Issue #24: Optionen (Einfach-/Mehrfachauswahl) plus optionale Nachricht.
            if ($question->type === 'choice') {
                $choicesRule = ['nullable', 'array'];
                if ($question->required) {
                    $choicesRule[0] = $question->hasFreeText() ? "required_without:{$key}.text" : 'required';
                }
                if (! $question->allowsMultiple()) {
                    $choicesRule[] = 'max:1';
                }

                $rules[$key] = ['nullable', 'array'];
                $rules["{$key}.choices"] = $choicesRule;
                $rules["{$key}.choices.*"] = ['string', Rule::in($question->choices())];
                $rules["{$key}.text"] = ['nullable', 'string', 'max:2000'];

                $messages["{$key}.choices.required"] = 'Bitte wähle eine Antwort aus.';
                $messages["{$key}.choices.required_without"] = 'Bitte wähle eine Antwort aus oder schreib eine Nachricht.';
                $messages["{$key}.choices.max"] = 'Bitte wähle nur eine Antwort aus.';
                $messages["{$key}.choices.*.in"] = 'Diese Antwort gibt es bei der Frage nicht.';

                continue;
            }

            $rules[$key] = match ($question->type) {
                'rating' => [$requiredRule, 'integer', 'min:1', 'max:10'],
                // Issue #46: 0 = Zusatzantwort ohne Smiley (nur wenn aktiviert).
                'smiley' => [$requiredRule, 'integer', 'min:'.($question->hasNaOption() ? 0 : 1), 'max:5'],
                'text' => [$requiredRule, 'string', 'max:2000'],
                'yes_no' => [$requiredRule, 'in:1,0'],
                default => [$requiredRule],
            };
        }

        return [$rules, $messages];
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
