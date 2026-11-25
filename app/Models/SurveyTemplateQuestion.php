<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $answers_count Nur mit withCount('answers') geladen
 * @property string $type Fragetyp, siehe TYPES (seit Issue #24 VARCHAR statt ENUM)
 * @property array{choices?: list<string>, multiple?: bool, free_text?: bool, labels?: array<int|string, string>, subtitle?: string, na_option?: bool, na_label?: string}|null $options
 */
class SurveyTemplateQuestion extends Model
{
    protected $fillable = [
        'survey_template_id',
        'question_text',
        'type',
        'options',
        'sort_order',
        'required',
        'archived_at',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
        'archived_at' => 'datetime',
    ];

    public const TYPES = [
        'rating' => 'Bewertung (1–10)',
        'text' => 'Freitext',
        'yes_no' => 'Ja / Nein',
        'choice' => 'Optionen mit Freitext',
        'smiley' => 'Smileys (1–5)',
    ];

    /**
     * Issue #46: Bereich (Zwischenüberschrift mit Untertext) – keine Frage, wird nicht beantwortet.
     * Bewusst nicht in TYPES, damit er nicht im Typ-Auswahlfeld einer Frage erscheint.
     */
    public const SECTION = 'section';

    /** Issue #46: Vorauswahl der Smiley-Zusatzantwort ohne Smiley (zählt nicht mit). */
    public const DEFAULT_NA_LABEL = 'Habe ich nicht mitgemacht';

    /**
     * Alle speicherbaren Typen (Fragen und Bereiche).
     *
     * @return list<string>
     */
    public static function allTypes(): array
    {
        return [...array_keys(self::TYPES), self::SECTION];
    }

    /** Issue #24: Vorauswahl der Antwortoptionen (je Frage anpassbar). */
    public const DEFAULT_CHOICES = ['Bin dabei', 'Komme später', 'Schaffe es nicht'];

    /** Issue #24: Feste Smileys je Punktzahl (5 = bestes Ergebnis). */
    public const SMILEYS = [5 => '🤩', 4 => '🙂', 3 => '😐', 2 => '😕', 1 => '😞'];

    /** Issue #24: Vorauswahl der Smiley-Texte (je Frage anpassbar). */
    public const DEFAULT_SMILEY_LABELS = [5 => 'Sehr viel', 4 => 'Viel', 3 => 'Ging so', 2 => 'Wenig', 1 => 'Gar keinen'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /** Issue #46: Bereich statt Frage? */
    public function isSection(): bool
    {
        return $this->type === self::SECTION;
    }

    /** Issue #46: Untertext eines Bereichs. */
    public function subtitle(): ?string
    {
        $text = trim((string) ($this->options['subtitle'] ?? ''));

        return $text !== '' ? $text : null;
    }

    /** Issue #46: Hat die Smiley-Frage eine Zusatzantwort ohne Smiley (gespeichert als 0)? */
    public function hasNaOption(): bool
    {
        return $this->type === 'smiley' && (bool) ($this->options['na_option'] ?? false);
    }

    /** Issue #46: Text der Smiley-Zusatzantwort. */
    public function naLabel(): string
    {
        $label = trim((string) ($this->options['na_label'] ?? ''));

        return $label !== '' ? $label : self::DEFAULT_NA_LABEL;
    }

    /** Issue #35: Frage wurde aus der Vorlage entfernt, hat aber Antworten. */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Antwortoptionen einer Options-Frage.
     *
     * @return list<string>
     */
    public function choices(): array
    {
        return $this->options['choices'] ?? self::DEFAULT_CHOICES;
    }

    /** Darf mehr als eine Option gewählt werden? */
    public function allowsMultiple(): bool
    {
        return (bool) ($this->options['multiple'] ?? false);
    }

    /** Gibt es am Ende ein Freitextfeld für eine persönliche Nachricht? */
    public function hasFreeText(): bool
    {
        return (bool) ($this->options['free_text'] ?? true);
    }

    /**
     * Smiley-Texte von 5 bis 1 (fehlende Texte fallen auf die Vorauswahl zurück).
     *
     * @return array<int, string>
     */
    public function smileyLabels(): array
    {
        $labels = [];
        foreach (self::DEFAULT_SMILEY_LABELS as $points => $default) {
            $custom = trim((string) ($this->options['labels'][$points] ?? ''));
            $labels[$points] = $custom !== '' ? $custom : $default;
        }

        return $labels;
    }

    /** Anzeige einer Smiley-Antwort, z. B. „🤩 Sehr viel“. */
    public function smileyText(?int $points): string
    {
        // Issue #46: Zusatzantwort ohne Smiley.
        if ($points === 0) {
            return $this->naLabel();
        }
        if (! isset(self::SMILEYS[$points])) {
            return '–';
        }

        return self::SMILEYS[$points].' '.$this->smileyLabels()[$points];
    }

    /**
     * Formulareingaben des Vorlagen-Editors in die gespeicherten Einstellungen übersetzen.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    public static function optionsFromInput(string $type, array $input): ?array
    {
        if ($type === 'choice') {
            $choices = collect(preg_split('/\R/', (string) ($input['choices'] ?? '')))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'choices' => $choices ?: self::DEFAULT_CHOICES,
                'multiple' => (bool) ($input['multiple'] ?? false),
                'free_text' => (bool) ($input['free_text'] ?? false),
            ];
        }

        if ($type === 'smiley') {
            $labels = [];
            foreach (self::DEFAULT_SMILEY_LABELS as $points => $default) {
                $custom = trim((string) ($input['labels'][$points] ?? ''));
                $labels[$points] = $custom !== '' ? $custom : $default;
            }

            $naLabel = trim((string) ($input['na_label'] ?? ''));

            return [
                'labels' => $labels,
                // Issue #46: Zusatzantwort ohne Smiley.
                'na_option' => (bool) ($input['na_option'] ?? false),
                'na_label' => $naLabel !== '' ? mb_substr($naLabel, 0, 100) : self::DEFAULT_NA_LABEL,
            ];
        }

        if ($type === self::SECTION) {
            $subtitle = trim((string) ($input['subtitle'] ?? ''));

            return $subtitle !== '' ? ['subtitle' => $subtitle] : null;
        }

        return null;
    }

    /** Icon für Auswertung und Einzelantwort. */
    public function typeIcon(): string
    {
        return match ($this->type) {
            'rating' => '📊',
            'text' => '📝',
            'choice' => '☑️',
            'smiley' => '🙂',
            self::SECTION => '📑',
            default => '✅',
        };
    }
}
