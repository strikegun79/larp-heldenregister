<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * REP-02: EP-Konto-Auszug als CSV.
 */
class HeroEpExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    private function hero(): Hero
    {
        $hero = Hero::factory()->create(['player_id' => Player::factory()->create()->id]);
        $hero->epTransactions()->create(['ep_transaction_type_id' => 10, 'ep_count' => 100, 'transacted_at' => now()->subDay()]);
        $hero->epTransactions()->create(['ep_transaction_type_id' => 20, 'ep_count' => 30, 'transacted_at' => now()]);

        return $hero;
    }

    public function test_viewer_can_download_ep_csv_with_running_balance(): void
    {
        $hero = $this->hero();

        $response = $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->get(route('heroes.ep.export', $hero));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('ep-auszug-'.$hero->id.'.csv', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Datum;Art;Betrag;Saldo', $csv);
        $this->assertStringContainsString('Initiale EP', $csv);
        $this->assertStringContainsString('70', $csv); // Endsaldo 100 − 30
    }

    public function test_participant_cannot_export(): void
    {
        $hero = $this->hero();

        $this->actingAs($this->userWithRole(70)) // Teilnehmer: kein heldenregister.view
            ->get(route('heroes.ep.export', $hero))
            ->assertForbidden();
    }
}
