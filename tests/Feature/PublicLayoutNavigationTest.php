<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Hero;
use App\Models\Survey;
use App\Models\SurveyLink;
use App\Models\SurveyTemplate;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #37: Öffentliche Seiten (Heldenverzeichnis /h u. a.) zeigen Angemeldeten
 * die Hauptnavigation statt nur „Anmelden“.
 */
class PublicLayoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class, EventLookupSeeder::class]);
    }

    private function member(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(70);

        return $user;
    }

    public function test_heldenverzeichnis_zeigt_angemeldeten_die_hauptnavigation(): void
    {
        $this->actingAs($this->member())
            ->get(route('public.hero.search'))
            ->assertOk()
            ->assertSee('aria-label="Hauptnavigation"', false)
            ->assertSee('Abmelden')
            ->assertDontSee('>Anmelden<', false)
            ->assertDontSee(route('login'), false);
    }

    public function test_heldenverzeichnis_zeigt_gaesten_nur_anmelden(): void
    {
        $this->get(route('public.hero.search'))
            ->assertOk()
            ->assertSee(route('login'), false)
            ->assertSee('Anmelden')
            ->assertDontSee('aria-label="Hauptnavigation"', false);
    }

    public function test_heldenseite_und_datenschutz_zeigen_die_hauptnavigation(): void
    {
        Hero::factory()->create(['public_code' => 'NAV37', 'public_visible' => true]);
        $user = $this->member();

        foreach ([route('public.hero', 'NAV37'), route('datenschutz')] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('aria-label="Hauptnavigation"', false);
        }
    }

    public function test_umfrage_bleibt_schlank_mit_link_zum_heldenregister(): void
    {
        $user = $this->member();
        $template = SurveyTemplate::create([
            'name' => 'Vorlage',
            'target_group' => 'teamer',
            'active' => true,
            'created_by' => $user->id,
        ]);
        $survey = Survey::create([
            'adventure_id' => Adventure::factory()->create()->id,
            'survey_template_id' => $template->id,
            'title' => 'Umfrage',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $link = SurveyLink::create([
            'survey_id' => $survey->id,
            'target_type' => 'teamer',
            'name' => 'Test Person',
            'email' => 'test@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('survey.start', $link->token))
            ->assertOk()
            ->assertDontSee('aria-label="Hauptnavigation"', false)
            ->assertSee('Zum Heldenregister')
            ->assertSee(route('dashboard'), false);
    }
}
