<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * REP-03/04: Belegungsreport je Event und Spielerübersicht als CSV.
 */
class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_participation_csv_lists_bookings_and_totals(): void
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create(['name' => 'Mira', 'lastname' => 'Tan']);
        Booking::factory()->for($adventure)->create(['player_id' => $player->id, 'paid' => true]);
        $adventure->visits()->create(['player_id' => $player->id]);

        $response = $this->actingAs($this->userWithRole(30)) // Projektleitung: events.edit
            ->get(route('adventures.participation-csv', $adventure));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Spieler;Rolle;Liste;Status;Beitrag;Anwesend', $csv);
        $this->assertStringContainsString('Mira Tan', $csv);
        $this->assertStringContainsString('Bezahlt;1', $csv);
        $this->assertStringContainsString('Anwesend;1', $csv);
    }

    public function test_participation_csv_requires_events_edit(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(60)) // Event buchen: kein events.edit
            ->get(route('adventures.participation-csv', $adventure))
            ->assertForbidden();
    }

    public function test_player_export_csv(): void
    {
        Player::factory()->create(['name' => 'Lea', 'lastname' => 'Berg', 'email' => 'lea@example.test']);

        $response = $this->actingAs($this->userWithRole(10)) // Admin
            ->get(route('admin.players.export'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Nachname;Vorname;E-Mail;Geburtsdatum;Geschlecht;Helden', $csv);
        $this->assertStringContainsString('Berg;Lea;lea@example.test', $csv);
    }

    public function test_player_export_requires_admin(): void
    {
        $this->actingAs($this->userWithRole(20)) // Bürokrat: kein portal.manage
            ->get(route('admin.players.export'))
            ->assertForbidden();
    }
}
