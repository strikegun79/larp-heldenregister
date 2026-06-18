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
 * ADV-22: Anmeldung/Gast/Editieren als gestapeltes Modal (data-modal-stack),
 * Speichern-Button im Footer (data-modal-actions), kein „Zurück" mehr.
 */
class EventStackedModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function bookerWithPlayer(): array
    {
        $user = User::factory()->create();
        $user->roles()->attach(60);
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);

        return [$user, $player];
    }

    public function test_detail_uses_stack_triggers_for_anmelden_and_guest(): void
    {
        [$user] = $this->bookerWithPlayer();
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $response = $this->actingAs($user)
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $response->assertSee('data-modal-stack="'.route('adventures.bookings.create', $adventure).'"', false);
        $response->assertSee('data-modal-stack="'.route('adventures.bookings.create-guest', $adventure).'"', false);
    }

    public function test_create_form_has_footer_submit_and_no_back_link(): void
    {
        [$user] = $this->bookerWithPlayer();
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $response = $this->actingAs($user)
            ->get(route('adventures.bookings.create', $adventure))
            ->assertOk();

        $response->assertSee('id="booking-create-form"', false);
        $response->assertSee('data-modal-actions', false);
        $response->assertSee('form="booking-create-form"', false);
        $response->assertDontSee('data-modal-subview', false);
    }

    public function test_edit_form_uses_footer_submit_via_form_attribute(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = Booking::factory()->for($adventure)->create(['player_id' => Player::factory()->create()->id]);

        $this->actingAs($this->registrar())
            ->get(route('adventures.bookings.edit', [$adventure, $booking]))
            ->assertOk()
            ->assertSee('id="booking-edit-form"', false)
            ->assertSee('form="booking-edit-form"', false)
            ->assertDontSee('data-modal-subview', false);
    }

    public function test_booking_list_edit_uses_stack(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = Booking::factory()->for($adventure)->create(['player_id' => Player::factory()->create()->id]);

        $this->actingAs($this->registrar())
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('data-modal-stack="'.route('adventures.bookings.edit', [$adventure, $booking]).'"', false);
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20);

        return $user;
    }
}
