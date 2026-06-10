<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BOOK-10: Buchungsformular zeigt Nicht-Bürokraten nur eigene/betreute Spieler,
 * und das Buchen fremder Spieler wird serverseitig abgewiesen.
 */
class BookingPlayerScopeTest extends TestCase
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

    public function test_booker_form_lists_only_own_players(): void
    {
        $booker = $this->userWithRole(60); // Event buchen
        $own = Player::factory()->create();
        $foreign = Player::factory()->create();
        $booker->players()->attach($own->id, ['self' => true]);

        $adventure = Adventure::factory()->create(['max_player' => 5]);

        // Das Anmeldeformular (ADV-15-Unteransicht) listet die Spieler.
        $response = $this->actingAs($booker)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk();

        $response->assertSee('value="'.$own->id.'"', false);
        $response->assertDontSee('value="'.$foreign->id.'"', false);
    }

    public function test_registrar_form_lists_all_players(): void
    {
        $registrar = $this->userWithRole(20); // Bürokrat
        $a = Player::factory()->create();
        $b = Player::factory()->create();

        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $response = $this->actingAs($registrar)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk();

        $response->assertSee('value="'.$a->id.'"', false);
        $response->assertSee('value="'.$b->id.'"', false);
    }

    public function test_booker_cannot_book_a_foreign_player(): void
    {
        $booker = $this->userWithRole(60);
        $foreign = Player::factory()->create();
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($booker)
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $foreign->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Für diesen Spieler darfst du keine Buchung anlegen.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_registrar_can_book_any_player(): void
    {
        $registrar = $this->userWithRole(20);
        $foreign = Player::factory()->create();
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($registrar)
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $foreign->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])
            ->assertOk();

        $this->assertDatabaseHas('bookings', [
            'adventure_id' => $adventure->id,
            'player_id' => $foreign->id,
        ]);
    }
}
