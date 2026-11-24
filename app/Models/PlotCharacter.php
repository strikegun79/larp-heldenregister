<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Issue #30/#34: Bekannter Plot-Charakter (z. B. Babayaga, Bran), der im öffentlichen
 * Heldenverzeichnis (/h) gesucht und unter /h/plot/{slug} angesehen werden kann.
 *
 * @property list<string>|null $known_titles
 * @property list<string>|null $abilities
 * @property list<string>|null $artifacts
 * @property list<string>|null $connections
 * @property list<array{date: ?string, title: string, url: ?string}>|null $chronicle
 */
class PlotCharacter extends Model
{
    /** Typ: Nichtspieler- oder Spielercharakter. */
    public const TYPES = [
        'nsc' => 'NSC',
        'sc' => 'SC',
    ];

    /** Status mit Fomantic-Labelfarbe. */
    public const STATUSES = [
        'active' => 'Aktiv',
        'deceased' => 'Verstorben',
        'banished' => 'Gebannt',
        'missing' => 'Verschollen',
        'unknown' => 'Unbekannt',
    ];

    public const STATUS_COLORS = [
        'active' => 'green',
        'deceased' => 'black',
        'banished' => 'purple',
        'missing' => 'orange',
        'unknown' => 'grey',
    ];

    public const SPECIES = [
        'human' => 'Mensch',
        'elf' => 'Elf',
        'dwarf' => 'Zwerg',
        'kobold' => 'Kobold',
        'undead' => 'Untoter',
        'other' => 'Sonstiges Wesen',
        'unknown' => 'Unbekannt',
    ];

    /** Gesinnung / Verhältnis zu den Waldrittern mit Labelfarbe. */
    public const ALIGNMENTS = [
        'allied' => 'Verbündet',
        'neutral' => 'Neutral',
        'hostile' => 'Feindlich',
        'unknown' => 'Unbekannt',
    ];

    public const ALIGNMENT_COLORS = [
        'allied' => 'teal',
        'neutral' => 'yellow',
        'hostile' => 'red',
        'unknown' => 'grey',
    ];

    /** Listenfelder (JSON, eine Zeile je Eintrag im Formular). */
    public const LIST_FIELDS = ['known_titles', 'abilities', 'artifacts', 'connections'];

    protected $fillable = [
        'name',
        'title',
        'character_type',
        'status',
        'class_name',
        'home',
        'species',
        'faction',
        'alignment',
        'location',
        'first_seen',
        'missing_since',
        'last_seen',
        'description',
        'known_titles',
        'abilities',
        'artifacts',
        'connections',
        'chronicle',
        'quote',
        'motto',
        'public_lore',
        'gm_notes',
        'image_path',
        'public_visible',
    ];

    protected $attributes = [
        'character_type' => 'nsc',
        'status' => 'unknown',
        'species' => 'unknown',
        'alignment' => 'unknown',
        'public_visible' => true,
    ];

    protected function casts(): array
    {
        return [
            'public_visible' => 'boolean',
            'known_titles' => 'array',
            'abilities' => 'array',
            'artifacts' => 'array',
            'connections' => 'array',
            'chronicle' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Slug beim Anlegen automatisch und eindeutig aus dem Namen erzeugen.
        static::creating(function (PlotCharacter $character) {
            if (blank($character->slug)) {
                $character->slug = static::uniqueSlug($character->name);
            }
        });
    }

    /**
     * Routen-Bindung über den Slug (öffentliche URL /h/plot/{slug}).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Nur öffentlich sichtbare Plot-Charaktere.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('public_visible', true);
    }

    /**
     * URL des Bildes oder null, wenn keines hochgeladen wurde.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->character_type] ?? self::TYPES['nsc'];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['unknown'];
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'grey';
    }

    public function speciesLabel(): string
    {
        return self::SPECIES[$this->species] ?? self::SPECIES['unknown'];
    }

    public function alignmentLabel(): string
    {
        return self::ALIGNMENTS[$this->alignment] ?? self::ALIGNMENTS['unknown'];
    }

    public function alignmentColor(): string
    {
        return self::ALIGNMENT_COLORS[$this->alignment] ?? 'grey';
    }

    /**
     * Faktentabelle für die öffentliche Seite: nur Felder mit bekanntem Inhalt.
     *
     * @return array<string, string>
     */
    public function facts(): array
    {
        $facts = [
            'Typ' => $this->typeLabel(),
            'Volk / Wesen' => $this->species !== 'unknown' ? $this->speciesLabel() : null,
            'Klasse' => $this->class_name,
            'Heimatort' => $this->home,
            'Zugehörigkeit' => $this->faction,
            'Bekannter Aufenthaltsort' => $this->location,
            'Erste Erblickung' => $this->first_seen,
            'Verschollen / verstorben seit' => $this->missing_since,
            'Letzte Sichtung' => $this->last_seen,
        ];

        return array_filter($facts, fn ($value) => filled($value) && ! static::isUnknown($value));
    }

    /**
     * „Unbekannt“ bzw. „–“ werden auf der öffentlichen Seite ausgeblendet.
     */
    public static function isUnknown(?string $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['unbekannt', '-', '–', ''], true);
    }

    /**
     * Mehrzeiligen Text in eine Liste zerlegen (leere Zeilen entfallen).
     *
     * @return list<string>|null
     */
    public static function parseList(?string $text): ?array
    {
        $lines = collect(preg_split('/\R/', (string) $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return $lines ?: null;
    }

    /**
     * Chronik-Zeilen „Datum | Titel | Link“ zerlegen; Datum und Link sind optional.
     * Eine Zeile ohne „|“ gilt als reiner Titel.
     *
     * @return list<array{date: ?string, title: string, url: ?string}>|null
     */
    public static function parseChronicle(?string $text): ?array
    {
        $entries = [];

        foreach (static::parseList($text) ?? [] as $line) {
            $parts = array_map('trim', explode('|', $line));

            if (count($parts) === 1) {
                $entries[] = ['date' => null, 'title' => $parts[0], 'url' => null];

                continue;
            }

            $title = $parts[1] ?? '';
            $url = $parts[2] ?? '';
            $entries[] = [
                'date' => $parts[0] !== '' ? $parts[0] : null,
                'title' => $title !== '' ? $title : $parts[0],
                'url' => filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url) ? $url : null,
            ];
        }

        return $entries ?: null;
    }

    /**
     * Chronik für das Formular wieder als Zeilen „Datum | Titel | Link“ ausgeben.
     */
    public function chronicleText(): string
    {
        return collect($this->chronicle ?? [])
            ->map(function ($e) {
                $date = $e['date'] ?? '';
                $title = $e['title'];
                $url = $e['url'] ?? '';

                // Ohne Datum und Link reicht der Titel; sonst immer alle Spalten.
                if ($date === '' && $url === '') {
                    return $title;
                }

                return rtrim($date.' | '.$title.' | '.$url, ' |');
            })
            ->implode("\n");
    }

    /**
     * Listenfeld für das Formular als mehrzeiligen Text ausgeben.
     */
    public function listText(string $field): string
    {
        return implode("\n", $this->{$field} ?? []);
    }

    /**
     * Eindeutigen Slug erzeugen (bei Kollision mit Zähler-Suffix).
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-', 'de') ?: 'plot-charakter';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
