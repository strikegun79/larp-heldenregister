<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Hero extends Model
{
    use HasFactory;

    /**
     * PUB-01: Eindeutigen 6-stelligen Code generieren und beim Erstellen setzen.
     * DSGVO: Heldenfoto beim Löschen aus dem Storage entfernen.
     */
    protected static function booted(): void
    {
        static::creating(function (Hero $hero) {
            if (empty($hero->public_code)) {
                $hero->public_code = static::generatePublicCode();
            }
        });

        static::deleting(function (Hero $hero) {
            if ($hero->image) {
                Storage::disk('public')->delete($hero->image);
            }
            // Galerie-Bilder aus Storage entfernen (DB-Cascade löscht nur die Datensätze).
            $hero->galleryImages()->each(function (HeroGalleryImage $gi) {
                Storage::disk('public')->delete($gi->path);
            });
        });
    }

    /** Kollisionsfreien 6-stelligen Code aus dem BASE31-Alphabet erzeugen. */
    public static function generatePublicCode(): string
    {
        do {
            $code = '';
            $len  = strlen(self::CODE_ALPHABET);
            for ($i = 0; $i < 6; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, $len - 1)];
            }
        } while (static::where('public_code', $code)->exists());

        return $code;
    }

    /** PUB-01: Alphabet ohne visuell verwechselbare Zeichen (0, O, 1, I, L). */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'player_id',
        'character_name',
        'born',
        'died',
        'homeplace',
        'description',
        'image',
        'active',
        'legacy_id',
        'public_code',
        'public_visible',
        'public_searchable',
    ];

    protected $casts = [
        'born'              => 'date',
        'died'              => 'date',
        'active'            => 'boolean',
        'public_visible'    => 'boolean',
        'public_searchable' => 'boolean',
    ];

    /** Öffentliche URL des Helden-Fotos; Standardbild wenn keins hochgeladen (HERO-22). */
    public function getImageUrlAttribute(): string
    {
        return $this->image ? '/storage/'.$this->image : '/images/heroes_db.jpg';
    }

    /**
     * Spieler hinter dem Helden.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Klassen des Helden (Legacy: hero2classes).
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(HeroClass::class);
    }

    /**
     * Gruppen, denen dieser Held angehört (GRP-01).
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('role', 'joined_at');
    }

    /**
     * Gelernte Fertigkeiten (Legacy: hero2skill).
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('trained_at');
    }

    /**
     * EP-Transaktionsbuch (Legacy: hero2ep).
     */
    public function epTransactions(): HasMany
    {
        // type immer mitladen: signedAmount() und ep_total/ep_balance-Accessors
        // greifen auf type->is_credit zu.
        return $this->hasMany(EpTransaction::class)->with('type');
    }

    /**
     * Aktueller EP-Saldo (Gutschriften minus Kosten).
     * Ersetzt die Legacy-View view_heroT1Statistic.
     */
    public function getEpBalanceAttribute(): float
    {
        return $this->epTransactions
            ->reduce(fn (float $carry, EpTransaction $tx) => $carry + $tx->signedAmount(), 0.0);
    }

    /** EP-Buchungsart „Abenteuer bestritten". */
    public const ADVENTURE_EP_TYPE = 50;

    /**
     * Abenteuerhistorie: EP-Buchungen vom Typ „Abenteuer bestritten", die mit
     * einem Abenteuer verknüpft sind – chronologisch (neueste zuerst).
     */
    public function getAdventureHistoryAttribute(): \Illuminate\Support\Collection
    {
        return $this->epTransactions
            ->where('ep_transaction_type_id', self::ADVENTURE_EP_TYPE)
            ->whereNotNull('adventure_id')
            ->sortByDesc('transacted_at')
            ->values();
    }

    /**
     * Summe der aus Abenteuern erhaltenen EP.
     */
    public function getAdventuresEpTotalAttribute(): float
    {
        return $this->adventure_history->sum(fn (EpTransaction $tx) => (float) $tx->ep_count);
    }

    /**
     * Insgesamt erworbene EP (nur Gutschriften, ohne Abzüge).
     */
    public function getEpTotalAttribute(): float
    {
        return $this->epTransactions
            ->filter(fn (EpTransaction $tx) => $tx->type?->is_credit)
            ->sum(fn (EpTransaction $tx) => (float) $tx->ep_count);
    }

    /**
     * Ausgegebene EP (Abzüge): erworbene minus aktuell verfügbare (REP-01).
     */
    public function getEpSpentAttribute(): float
    {
        return $this->ep_total - $this->ep_balance;
    }

    /** Anzahl erlernter Fertigkeiten (REP-01). */
    public function getSkillsCountAttribute(): int
    {
        return $this->skills->count();
    }

    /** Anzahl Klassen (REP-01). */
    public function getClassesCountAttribute(): int
    {
        return $this->classes->count();
    }

    /**
     * Perlen-/Bändchen-Zusammenfassung je Farbe (EP-07).
     * Gibt eine Collection von stdClass {color: PerlColor, count: int} zurück,
     * sortiert nach Farbname. Fertigkeiten ohne Perlenfarbe werden ignoriert.
     */
    public function getPerlSummaryAttribute(): Collection
    {
        $this->loadMissing('skills.perlColor');

        return $this->skills
            ->filter(fn ($s) => $s->perlColor !== null)
            ->groupBy('perl_color_id')
            ->map(fn ($group) => (object) [
                'color' => $group->first()->perlColor,
                'count' => $group->count(),
            ])
            ->sortBy('color.name')
            ->values();
    }

    /**
     * Perlen-/Bändchen-Zusammenfassung je Klasse und Farbe.
     * Gibt eine Collection von stdClass {class: HeroClass, perls: Collection<{color, count}>} zurück.
     *
     * Wichtig: Ausgangspunkt sind die Klassen des Helden, nicht die Masterclass
     * (hero_class_id) der Fertigkeit. Eine Fertigkeit gehört via skill_hero_class
     * zu beliebig vielen Klassen – sie erscheint daher unter jeder Klasse des Helden,
     * der sie zugeordnet ist, unabhängig davon, bei welcher Masterclass sie erworben
     * werden kann.
     *
     * Klassen ohne erlernte Perlenfertigkeiten werden ausgelassen.
     */
    public function getPerlSummaryByClassAttribute(): Collection
    {
        // skills.classes lädt die skill_hero_class-Pivot-Zuordnungen.
        $this->loadMissing('skills.perlColor', 'skills.classes', 'classes');

        return $this->classes
            ->map(function ($class) {
                // Gelernte Fertigkeiten des Helden, die dieser Klasse zugeordnet sind.
                $classSkills = $this->skills->filter(
                    fn ($s) => $s->perlColor !== null
                            && $s->classes->contains('id', $class->id)
                );

                if ($classSkills->isEmpty()) {
                    return null;
                }

                return (object) [
                    'class' => $class,
                    'perls' => $classSkills
                        ->groupBy('perl_color_id')
                        ->map(fn ($pg) => (object) [
                            'color' => $pg->first()->perlColor,
                            'count' => $pg->count(),
                        ])
                        ->sortBy('color.name')
                        ->values(),
                ];
            })
            ->filter()
            ->sortBy('class.name')
            ->values();
    }

    /**
     * Komma-separierte Klassen-Slugs (ersetzt GROUP_CONCAT aus view_heroT1).
     */
    public function getClassListAttribute(): string
    {
        return $this->classes->pluck('slug')->implode(', ');
    }

    /** HERO-24: Galerie-Bilder (max. 4, sortiert). */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(HeroGalleryImage::class)->orderBy('sort_order');
    }
}
