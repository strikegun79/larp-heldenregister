<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spieler-Edit-Modal: Speichern im Footer, kein Abbrechen; Wohnort-Feld.
 */
class PlayerEditModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function ownerOf(Player $player): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);
        $user->players()->attach($player->id, ['self' => false]);

        return $user;
    }

    public function test_edit_modal_has_footer_save_and_no_cancel(): void
    {
        $player = Player::factory()->create();

        $response = $this->actingAs($this->ownerOf($player))
            ->get(route('players.edit', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        // Speichern als Footer-Aktion, per form-Attribut verknüpft.
        $response->assertSee('data-modal-actions', false);
        $response->assertSee('form="player-edit-form"', false);
        // Kein „Abbrechen" mehr (Schließen über den Schließen-Button).
        $response->assertDontSee('Abbrechen');
    }

    public function test_edit_form_has_wohnort_field(): void
    {
        $player = Player::factory()->create(['place' => 'Gießen']);

        $this->actingAs($this->ownerOf($player))
            ->get(route('players.edit', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Wohnort')
            ->assertSee('name="place"', false)
            ->assertSee('value="Gießen"', false);
    }

    public function test_wohnort_is_saved_and_shown_in_detail(): void
    {
        $player = Player::factory()->create();
        $owner = $this->ownerOf($player);

        $this->actingAs($owner)
            ->putJson(route('players.update', $player), [
                'name' => $player->name, 'lastname' => $player->lastname, 'place' => 'Wetzlar',
            ])
            ->assertOk();

        $this->assertSame('Wetzlar', $player->fresh()->place);

        $this->actingAs($owner)
            ->get(route('players.show', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Wohnort')
            ->assertSee('Wetzlar');
    }
}
