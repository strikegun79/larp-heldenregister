<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Event-Einstellung „Nach vegetarischem Essen fragen“ (Standard: aus) und
 * Gast-Anmeldung mit Leih-Tunika, Leih-Waffe und NSC.
 */
class AdventureVegetarianSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function booker(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(60);
        $user->players()->attach(Player::factory()->create()->id, ['self' => true]);

        return $user;
    }

    private function openAdventure(array $attributes = []): Adventure
    {
        return Adventure::factory()->create(array_merge(['event_status_id' => EventStatus::REGISTRATION_OPEN], $attributes));
    }

    public function test_new_adventures_do_not_ask_by_default(): void
    {
        $this->assertFalse($this->openAdventure()->fresh()->asks_vegetarian);
    }

    public function test_forms_hide_vegetarian_when_setting_is_off(): void
    {
        $user = $this->booker();
        $adventure = $this->openAdventure(['asks_vegetarian' => false]);

        foreach (['adventures.bookings.create', 'adventures.bookings.create-guest'] as $route) {
            $this->actingAs($user)->get(route($route, $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()
                ->assertDontSee('name="vegetarier"', false)
                ->assertDontSee('Vegetarisches Essen');
        }
    }

    public function test_forms_show_vegetarian_when_setting_is_on(): void
    {
        $user = $this->booker();
        $adventure = $this->openAdventure(['asks_vegetarian' => true]);

        foreach (['adventures.bookings.create', 'adventures.bookings.create-guest'] as $route) {
            $this->actingAs($user)->get(route($route, $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()
                ->assertSee('Vegetarisches Essen');
        }
    }

    public function test_edit_keeps_existing_vegetarian_value_when_setting_is_off(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);
        $adventure = $this->openAdventure(['asks_vegetarian' => false]);
        $booking = Booking::factory()->for($adventure)->create(['player_id' => Player::factory()->create()->id, 'vegetarier' => true]);

        $this->actingAs($admin)
            ->get(route('adventures.bookings.edit', [$adventure, $booking]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('Vegetarisches Essen')
            ->assertSee('<input type="hidden" name="vegetarier" value="1">', false);
    }

    public function test_guest_booking_stores_rental_and_nsc_options(): void
    {
        $user = $this->booker();
        $adventure = $this->openAdventure();

        $this->actingAs($user)
            ->get(route('adventures.bookings.create-guest', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertSee('name="leih_tunika"', false)
            ->assertSee('name="leih_waffe"', false)
            ->assertSee('name="nsc"', false);

        $this->actingAs($user)->postJson(route('adventures.bookings.store-guest', $adventure), [
            'guest_name' => 'Gerd',
            'guest_lastname' => 'Gast',
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '0123 456',
            'leih_tunika' => '1',
            'leih_waffe' => '1',
            'nsc' => '1',
        ])->assertSuccessful();

        $this->assertDatabaseHas('bookings', [
            'guest_name' => 'Gerd',
            'leih_tunika' => true,
            'leih_waffe' => true,
            'nsc' => true,
        ]);
    }
}
