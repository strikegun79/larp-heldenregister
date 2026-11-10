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
 * REP-03/04: Belegungsreport je Event als Excel und Spielerübersicht als CSV.
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

    public function test_participation_xlsx_lists_bookings(): void
    {
        $adventure = Adventure::factory()->create(['fee' => 20, 'fee_reduced' => 10]);
        $player = Player::factory()->create(['name' => 'Mira', 'lastname' => 'Tan']);
        Booking::factory()->for($adventure)->create(['player_id' => $player->id, 'paid' => true]);
        $adventure->visits()->create(['player_id' => $player->id]);

        $response = $this->actingAs($this->userWithRole(30)) // Projektleitung: events.edit
            ->get(route('adventures.participation-xlsx', $adventure));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString(
            'belegung-' . $adventure->id . '-',
            $response->headers->get('content-disposition')
        );
    }

    public function test_participation_xlsx_requires_events_edit(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(60)) // Event buchen: kein events.edit
            ->get(route('adventures.participation-xlsx', $adventure))
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
