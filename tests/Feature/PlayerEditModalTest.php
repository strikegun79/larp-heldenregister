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

    public function test_edit_form_has_address_checkbox(): void
    {
        $player = Player::factory()->create(['address_same_as_guardian' => true]);

        $this->actingAs($this->ownerOf($player))
            ->get(route('players.edit', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('erziehungsberechtigten Person')
            ->assertSee('name="address_same_as_guardian"', false);
    }

    public function test_detail_shows_guardian_address_fallback_by_default(): void
    {
        $player = Player::factory()->create(['address_same_as_guardian' => true]);
        $owner = $this->ownerOf($player);

        $this->actingAs($owner)
            ->get(route('players.show', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('wie erziehungsberechtigte Person');
    }

    public function test_detail_shows_child_address_when_different(): void
    {
        $player = Player::factory()->create([
            'address_same_as_guardian' => false,
            'street' => 'Kinderstraße',
            'house_number' => '7',
            'zip' => '35390',
            'city' => 'Gießen',
        ]);

        $this->actingAs($this->ownerOf($player))
            ->get(route('players.show', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Kinderstraße')
            ->assertSee('abweichend');
    }
}
