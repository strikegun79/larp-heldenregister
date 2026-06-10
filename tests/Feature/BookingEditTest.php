<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingEditTest extends TestCase
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

    private function booking(): Booking
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create();

        return Booking::factory()->for($adventure)->create([
            'player_id' => $player->id,
            'event_role_id' => EventRole::orderBy('id')->first()->id,
            'vegetarier' => false,
            'allergien' => null,
        ]);
    }

    public function test_modify_permission_can_update_booking_details(): void
    {
        $booking = $this->booking();
        $newRole = EventRole::orderBy('id', 'desc')->first();

        $this->actingAs($this->userWithRole(60)) // Event buchen hat adventure.modify
            ->putJson(route('adventures.bookings.update', [$booking->adventure_id, $booking]), [
                'event_role_id' => $newRole->id,
                'vegetarier' => 1,
                'allergien' => 'Nüsse',
            ])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'event_role_id' => $newRole->id,
            'vegetarier' => 1,
            'allergien' => 'Nüsse',
        ]);
    }

    public function test_admin_can_open_edit_form(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.bookings.edit', [$booking->adventure_id, $booking]))
            ->assertOk()
            ->assertSee('Anmeldung bearbeiten');
    }

    public function test_participant_cannot_edit_booking(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->userWithRole(70)) // Teilnehmer: kein adventure.modify
            ->putJson(route('adventures.bookings.update', [$booking->adventure_id, $booking]), [
                'event_role_id' => $booking->event_role_id,
            ])
            ->assertForbidden();
    }

    public function test_validation_rejects_missing_role(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->userWithRole(60))
            ->putJson(route('adventures.bookings.update', [$booking->adventure_id, $booking]), [
                'event_role_id' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('event_role_id');
    }

    public function test_booking_must_belong_to_adventure(): void
    {
        $booking = $this->booking();
        $otherAdventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(60))
            ->putJson(route('adventures.bookings.update', [$otherAdventure, $booking]), [
                'event_role_id' => $booking->event_role_id,
            ])
            ->assertNotFound();
    }
}
