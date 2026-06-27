<?php

namespace Tests\Feature;

use App\Models\EpTransaction;
use App\Models\EpTransactionType;
use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\PerlColor;
use App\Models\Player;
use App\Models\Skill;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-11: Tests für erweiterte Helden-Informationen auf der öffentlichen Seite. */
class PublicHeroInfoTest extends TestCase
{
    use RefreshDatabase;

    private HeroClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
        $this->class = HeroClass::create(['id' => 1, 'name' => 'Waldläufer', 'slug' => 'ranger', 'disabled' => false]);
    }

    private function hero(array $attrs = []): Hero
    {
        return Hero::factory()->create(array_merge(['public_code' => 'TSTCOD'], $attrs));
    }

    // ── Initialien ─────────────────────────────────────────────────────────────

    public function test_initialien_wenn_kein_charaktername(): void
    {
        $player = Player::factory()->create(['name' => 'Franziska', 'lastname' => 'Winkler']);
        $hero   = Hero::factory()->create([
            'player_id'      => $player->id,
            'character_name' => 'Platzhalter', // Observer braucht einen Wert
            'public_code'    => 'INITLS',
        ]);
        // Direktes DB-Update umgeht Observer und setzt null
        Hero::whereKey($hero->id)->update(['character_name' => null]);

        $this->get(route('public.hero', 'INITLS'))
             ->assertOk()
             ->assertSee('FW')
             ->assertSee('noch namenlos');
    }

    public function test_charaktername_hat_vorrang_vor_initialien(): void
    {
        $player = Player::factory()->create(['name' => 'Karl', 'lastname' => 'Bauer']);
        Hero::factory()->create([
            'player_id'      => $player->id,
            'character_name' => 'Thorvald Eisenfaust',
            'public_code'    => 'THORVD',
        ]);

        $this->get(route('public.hero', 'THORVD'))
             ->assertOk()
             ->assertSee('Thorvald Eisenfaust')
             ->assertDontSee('noch namenlos');
    }

    // ── Erblickungsdatum ────────────────────────────────────────────────────────

    public function test_erblickungsdatum_wird_angezeigt(): void
    {
        $this->hero(['born' => '2019-03-08', 'public_code' => 'BORNDT']);

        $this->get(route('public.hero', 'BORNDT'))
             ->assertOk()
             ->assertSee('März')
             ->assertSee('2019');
    }

    public function test_erblickungsdatum_unbekannt_wenn_leer(): void
    {
        $this->hero(['born' => null, 'public_code' => 'NOBORN']);

        $this->get(route('public.hero', 'NOBORN'))
             ->assertOk()
             ->assertSee('Unbekannt');
    }

    // ── Verfügbare EP ───────────────────────────────────────────────────────────

    public function test_verfuegbare_ep_label_sichtbar(): void
    {
        $this->hero(['public_code' => 'EPTEST']);

        $this->get(route('public.hero', 'EPTEST'))
             ->assertOk()
             ->assertSee('Verfügbare EP');
    }

    public function test_ep_saldo_wird_berechnet_angezeigt(): void
    {
        $hero = $this->hero(['public_code' => 'EPSLD']);
        // Typ 10 = Initiale EP (is_credit = true)
        $type = EpTransactionType::where('id', 10)->first();
        EpTransaction::create([
            'hero_id'                => $hero->id,
            'ep_transaction_type_id' => $type->id,
            'ep_count'               => 30,
            'transacted_at'          => now(),
        ]);

        $this->get(route('public.hero', 'EPSLD'))
             ->assertOk()
             ->assertSee('30');
    }

    // ── Leere Felder zeigen ─────────────────────────────────────────────────────

    public function test_steckbrief_abschnitt_immer_sichtbar(): void
    {
        $this->hero(['description' => null, 'public_code' => 'NODESC']);

        $this->get(route('public.hero', 'NODESC'))
             ->assertOk()
             ->assertSee('Steckbrief')
             ->assertSee('Noch keine Eintragungen');
    }

    public function test_steckbrief_inhalt_wird_angezeigt(): void
    {
        $this->hero(['description' => 'Ein mutiger Waldläufer.', 'public_code' => 'WDESC']);

        $this->get(route('public.hero', 'WDESC'))
             ->assertOk()
             ->assertSee('Ein mutiger Waldläufer.');
    }

    public function test_gruppen_abschnitt_immer_sichtbar(): void
    {
        $this->hero(['public_code' => 'NOGRP']);

        $this->get(route('public.hero', 'NOGRP'))
             ->assertOk()
             ->assertSee('Gruppen')
             ->assertSee('Noch keine Eintragungen');
    }

    public function test_baendchen_abschnitt_immer_sichtbar(): void
    {
        $this->hero(['public_code' => 'NOPERL']);

        $this->get(route('public.hero', 'NOPERL'))
             ->assertOk()
             ->assertSee('Bändchen')
             ->assertSee('Noch keine Eintragungen');
    }

    // ── Fertigkeitsbäume ────────────────────────────────────────────────────────

    public function test_fertigkeitsabschnitt_immer_sichtbar(): void
    {
        $this->hero(['public_code' => 'NOSKLL']);

        $this->get(route('public.hero', 'NOSKLL'))
             ->assertOk()
             ->assertSee('Fertigkeiten')
             ->assertSee('Noch keine Eintragungen');
    }

    public function test_fertigkeiten_nach_klasse_gruppiert(): void
    {
        $player = Player::factory()->create();
        $hero   = Hero::factory()->create(['player_id' => $player->id, 'public_code' => 'SKLTST']);

        $skill = Skill::create([
            'name'          => 'Spurenlesen',
            'hero_class_id' => $this->class->id,
            'level'         => 1,
            'ep_costs'      => 5,
        ]);
        $this->class->skills()->syncWithoutDetaching([$skill->id]);
        $hero->classes()->attach($this->class);
        $hero->skills()->attach($skill);

        $this->get(route('public.hero', 'SKLTST'))
             ->assertOk()
             ->assertSee('Waldläufer')
             ->assertSee('Spurenlesen')
             ->assertSee('gelernt');
    }

    public function test_nicht_erlernte_fertigkeiten_werden_angezeigt(): void
    {
        $player = Player::factory()->create();
        $hero   = Hero::factory()->create(['player_id' => $player->id, 'public_code' => 'GREYSK']);

        $learned = Skill::create(['name' => 'Schwertkunst',  'hero_class_id' => $this->class->id, 'level' => 1, 'ep_costs' => 5]);
        $locked  = Skill::create(['name' => 'Meisterklinge', 'hero_class_id' => $this->class->id, 'level' => 2, 'ep_costs' => 10]);
        $this->class->skills()->syncWithoutDetaching([$learned->id, $locked->id]);
        $hero->classes()->attach($this->class);
        $hero->skills()->attach($learned);

        $this->get(route('public.hero', 'GREYSK'))
             ->assertOk()
             ->assertSee('Schwertkunst')
             ->assertSee('Meisterklinge')
             ->assertSee('gelernt');
    }

    public function test_perlenfarbe_im_baum_sichtbar(): void
    {
        $player = Player::factory()->create();
        $hero   = Hero::factory()->create(['player_id' => $player->id, 'public_code' => 'PRLCLR']);

        $color = PerlColor::create(['code' => '#0055aa', 'name' => 'Blau']);
        $skill = Skill::create([
            'name'          => 'Feuerball',
            'hero_class_id' => $this->class->id,
            'perl_color_id' => $color->id,
            'level'         => 1,
            'ep_costs'      => 8,
        ]);
        $this->class->skills()->syncWithoutDetaching([$skill->id]);
        $hero->classes()->attach($this->class);
        $hero->skills()->attach($skill);

        $this->get(route('public.hero', 'PRLCLR'))
             ->assertOk()
             ->assertSee('#0055aa', false);
    }
}
