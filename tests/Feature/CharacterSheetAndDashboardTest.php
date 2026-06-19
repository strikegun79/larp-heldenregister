<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * REP-05: Charakterbogen-PDF. REP-06: Admin-Dashboard-Kennzahlen.
 */
class CharacterSheetAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_character_sheet_pdf_is_generated(): void
    {
        $hero = Hero::factory()->create(['player_id' => Player::factory()->create()->id, 'character_name' => 'Eldric']);

        $response = $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->get(route('heroes.sheet-pdf', $hero));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    public function test_character_sheet_requires_view_permission(): void
    {
        $hero = Hero::factory()->create(['player_id' => Player::factory()->create()->id]);

        $this->actingAs($this->userWithRole(70)) // Teilnehmer
            ->get(route('heroes.sheet-pdf', $hero))
            ->assertForbidden();
    }

    public function test_admin_dashboard_shows_metrics(): void
    {
        Player::factory()->count(2)->create();
        Hero::factory()->create(['player_id' => Player::factory()->create()->id]);
        Adventure::factory()->create(['start_at' => now()->addWeek(), 'end_at' => now()->addWeek()->addDay()]);
        Booking::factory()->for(Adventure::factory()->create())->create([
            'player_id' => Player::factory()->create()->id,
            'status' => 'offen',
        ]);

        $this->actingAs($this->userWithRole(10)) // Admin
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kommende Abenteuer')
            ->assertSee('Offene Anmeldungen');
    }

    public function test_non_admin_dashboard_has_no_metrics(): void
    {
        $this->actingAs($this->userWithRole(20)) // Bürokrat: kein Admin
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Offene Anmeldungen');
    }
}
