<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HeroClass extends Model
{
    /** IDs stammen aus dem Legacy-System (type_classes). */
    public $incrementing = false;

    /**
     * Issue #29: Helden-Schwur und feierliche Aufnahme je Klasse (Texte aus dem Issue).
     *
     * @var array<string, array{oath_title: string, oath_text: string, ceremony_text: string}>
     */
    public const DEFAULT_OATHS = [
        'warrior' => [
            'oath_title' => 'Schwur der Tapferkeit',
            'oath_text' => "Ich schwöre, mutig und tapfer zu sein.\nMeinen Freunden treu zur Seite zu stehen.\nMeine Waffe soll stets der Gerechtigkeit dienen.\nFür Ehre, Freundschaft und die Waldritter!",
            'ceremony_text' => "„Hiermit seist du ein Krieger!\nMöge deine Tapferkeit unerschütterlich sein.\nEs sei deine Pflicht, den Schwachen beizustehen und den Pfad der Tugend zu beschreiten, als ein wahrer Waldritter, für immer und ewig!“",
        ],
        'ranger' => [
            'oath_title' => 'Schwur des Waldes',
            'oath_text' => "Ich schwöre, die Natur zu achten.\nIhre Geheimnisse zu entdecken und zu bewahren.\nMeine Fähigkeiten sollen stets dem Guten dienen.\nFür Ehre, Freundschaft und die Waldritter!",
            'ceremony_text' => "„Hiermit seist du ein Waldläufer!\nMöge deine Verbundenheit mit der Natur tief sein.\nEs sei deine Pflicht, die Wege des Waldes zu beschützen und den Ruf der Freiheit zu tragen, als ein wahrer Waldritter, für immer und ewig!“",
        ],
        'healer' => [
            'oath_title' => 'Schwur der Heilung',
            'oath_text' => "Ich schwöre, Kranken und Verletzten zu helfen.\nDen Schwachen Hoffnung und Trost zu schenken.\nMeine Hände sollen heilen und niemals schaden.\nFür Ehre, Freundschaft und die Waldritter!",
            'ceremony_text' => "„Hiermit seist du ein Heiler!\nMöge deine Sanftmut unerschütterlich sein.\nEs sei deine Pflicht, den Verletzten beizustehen und den Ruf der Barmherzigkeit zu tragen, als ein wahrer Waldritter, für immer und ewig!“",
        ],
        'wizard' => [
            'oath_title' => 'Schwur der Weisheit',
            'oath_text' => "Ich schwöre, die Geheimnisse der Magie zu erforschen.\nMeine Kräfte mit Weisheit einzusetzen.\nMeine Magie soll stets dem Guten dienen.\nFür Ehre, Freundschaft und die Waldritter!",
            'ceremony_text' => "„Hiermit seist du ein Magier!\nMöge deine Weisheit unerschütterlich sein.\nEs sei deine Pflicht, das Gleichgewicht der Natur zu wahren und den Ruf der Mystik zu tragen, als ein wahrer Waldritter, für immer und ewig!“",
        ],
        'alchemist' => [
            'oath_title' => 'Schwur der Entdeckung',
            'oath_text' => "Ich schwöre, neugierig und erfinderisch zu sein.\nDie Geheimnisse der Alchemie zu ergründen.\nMeine Tränke und Erfindungen mit Bedacht einzusetzen.\nFür Ehre, Freundschaft und die Waldritter!",
            'ceremony_text' => "„Hiermit seist du ein Alchemist!\nMöge deine Kreativität unerschütterlich sein.\nEs sei deine Pflicht, den Ruf der Innovation zu tragen und die Wunder der Alchemie zu bewahren, als ein wahrer Waldritter, für immer und ewig!“",
        ],
    ];

    /** Issue #29: Diese Fertigkeiten müssen vor Schwur und Bändchen-Übergabe erlernt sein. */
    public const OATH_REQUIRED_SKILLS = ['Erste Hilfe', 'Waffentraining'];

    protected $fillable = [
        'id',
        'slug',
        'name',
        'disabled',
        'ep_cost',
        'ribbon_color',
        'ribbon_image',
        'oath_title',
        'oath_text',
        'ceremony_text',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'ep_cost' => 'integer',
    ];

    /**
     * Helden dieser Klasse (Legacy: hero2classes).
     */
    public function heroes(): BelongsToMany
    {
        return $this->belongsToMany(Hero::class);
    }

    /**
     * Fertigkeiten dieser Klasse (Legacy: skills2class).
     * Pivot-Tabelle explizit, da der Default-Name (hero_class_skill) abweicht.
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_hero_class')
            ->withPivot('x_percentage', 'y_percentage');
    }

    /**
     * Issue #29: Standard-Schwurtexte für Klassen ohne Schwur eintragen (vorhandene bleiben).
     */
    public static function fillDefaultOaths(): void
    {
        foreach (self::DEFAULT_OATHS as $slug => $oath) {
            DB::table('hero_classes')
                ->where('slug', $slug)
                ->whereNull('oath_text')
                ->update($oath);
        }
    }

    /** Issue #29: Ist ein Schwur hinterlegt? */
    public function hasOath(): bool
    {
        return filled($this->oath_text);
    }

    /**
     * Pfad zum Fertigkeitsbaum-Bild (public/images/skilltree_*.jpg).
     * Slug-Sonderfall: „wizard" -> Bild „mage".
     */
    public function skilltreeImage(): string
    {
        $map = ['wizard' => 'mage'];
        $slug = $map[$this->slug] ?? $this->slug;

        return "/images/skilltree_{$slug}.jpg";
    }

    /**
     * URL des Klassenband-Bilds (162×600 px).
     * Vorrang: hochgeladenes Bild → Fallback auf Klassenbanner-{Name}.png in public/images/.
     */
    public function ribbonImageUrl(): ?string
    {
        if ($this->ribbon_image) {
            return Storage::disk('public')->url($this->ribbon_image);
        }

        $fallback = public_path("images/Klassenbanner-{$this->name}.png");
        if (file_exists($fallback)) {
            return asset("images/Klassenbanner-{$this->name}.png");
        }

        return null;
    }
}
