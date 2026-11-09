<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\EventRole;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAddressGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    private function openAdventure(): Adventure
    {
        return Adventure::factory()->create(['event_status_id' => EventStatus::REGISTRATION_OPEN, 'max_player' => 20]);
    }

    private function role(): EventRole
    {
        return EventRole::first() ?? EventRole::create(['id' => 1, 'description' => 'Teilnehmer']);
    }

    private function userWithAddress(): User
    {
        $user = User::factory()->create([
            'name' => 'Max',
            'lastname' => 'Mustermann',
            'phone' => '01234',
            'street' => 'Hauptstraße',
            'house_number' => '1',
            'zip' => '35390',
            'city' => 'Gießen',
        ]);
        $user->roles()->attach(60); // event_booking

        return $user;
    }

    private function userWithoutAddress(): User
    {
        $user = User::factory()->create([
            'street' => null,
            'city' => null,
        ]);
        $user->roles()->attach(60);

        return $user;
    }

    public function test_user_with_complete_address_can_book(): void
    {
        $user = $this->userWithAddress();
        $adventure = $this->openAdventure();
        $player = Player::factory()->create();
        $user->players()->attach($player, ['self' => false]);

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => $this->role()->id,
                'agb' => true,
                'kontakt_telefon' => '01234',
            ])
            ->assertOk();
    }

    public function test_user_without_address_cannot_book(): void
    {
        $user = $this->userWithoutAddress();
        $adventure = $this->openAdventure();
        $player = Player::factory()->create();
        $user->players()->attach($player, ['self' => false]);

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => $this->role()->id,
                'agb' => true,
                'kontakt_telefon' => '01234',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Kontaktdaten'));
    }

    public function test_admin_can_book_without_own_address(): void
    {
        $admin = User::factory()->create(['street' => null]);
        $admin->roles()->attach(10);

        $adventure = $this->openAdventure();
        $player = Player::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => $this->role()->id,
                'agb' => true,
                'kontakt_telefon' => '01234',
            ])
            ->assertOk();
    }
}
