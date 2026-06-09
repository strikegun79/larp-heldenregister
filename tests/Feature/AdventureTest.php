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

class AdventureTest extends TestCase
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

    private function booker(): User
    {
        return $this->userWithRole(60); // Event buchen
    }

    private function admin(): User
    {
        return $this->userWithRole(10);
    }

    public function test_guests_cannot_access_adventures(): void
    {
        $this->get(route('adventures.index'))->assertRedirect(route('login'));
    }

    public function test_participants_cannot_access_adventures(): void
    {
        $this->actingAs($this->userWithRole(70))
            ->get(route('adventures.index'))
            ->assertForbidden();
    }

    public function test_a_viewer_role_sees_adventures(): void
    {
        Adventure::factory()->create(['name' => 'Burg Staufenberg LARP']);

        $this->actingAs($this->userWithRole(40)) // Spielleiter: nur ansehen
            ->get(route('adventures.index'))
            ->assertOk()
            ->assertSee('Burg Staufenberg LARP');
    }

    public function test_booking_role_cannot_create_events_but_admin_can(): void
    {
        $payload = [
            'name' => 'Tulderon-Zeltfreizeit',
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-05 16:00',
            'event_status_id' => 30,
            'event_client_id' => 1,
            'event_category_id' => 0,
            'max_player' => 20,
            'fee' => 12,
        ];

        // "Event buchen" hat kein events.edit -> darf keine Events anlegen.
        $this->actingAs($this->booker())->get(route('adventures.create'))->assertForbidden();
        $this->actingAs($this->booker())->post(route('adventures.store'), $payload)->assertForbidden();

        // Admin (events.edit) darf.
        $this->actingAs($this->admin())->post(route('adventures.store'), $payload)
            ->assertRedirect();
        $this->assertNotNull(Adventure::firstWhere('name', 'Tulderon-Zeltfreizeit'));
    }

    public function test_a_spielleiter_can_book_but_not_create_events(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $player = Player::factory()->create();
        $spielleiter = $this->userWithRole(40); // hat adventure.book, aber kein events.edit

        $this->actingAs($spielleiter)
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])->assertSessionHas('status');

        $this->actingAs($spielleiter)->get(route('adventures.create'))->assertForbidden();
    }

    public function test_end_must_not_be_before_start(): void
    {
        $this->actingAs($this->admin())
            ->post(route('adventures.store'), [
                'name' => 'Kaputt',
                'start_at' => '2026-08-05 10:00',
                'end_at' => '2026-08-01 10:00',
                'event_status_id' => 30,
                'event_client_id' => 1,
                'event_category_id' => 0,
                'max_player' => 10,
                'fee' => 12,
            ])
            ->assertSessionHasErrors('end_at');
    }

    public function test_a_booker_can_book_an_open_adventure(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $player = Player::factory()->create();

        $this->actingAs($this->booker())
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])->assertSessionHas('status');

        $booking = Booking::firstWhere('player_id', $player->id);
        $this->assertNotNull($booking);
        $this->assertFalse($booking->waitlisted);
    }

    public function test_participants_cannot_book(): void
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create();

        $this->actingAs($this->userWithRole(70)) // Teilnehmer: kein adventure.book
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])->assertForbidden();
    }

    public function test_ajax_booking_returns_json_on_success(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $player = Player::factory()->create();

        $this->actingAs($this->booker())
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])
            ->assertOk()
            ->assertJson(['reload' => true])
            ->assertJsonStructure(['message', 'reload']);
    }

    public function test_ajax_booking_returns_422_on_validation_error(): void
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create();

        $this->actingAs($this->booker())
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                // agb fehlt -> Validierungsfehler als 422-JSON (Toast)
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('agb');
    }

    public function test_ajax_booking_returns_422_on_business_error(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $player = Player::factory()->create();

        $this->actingAs($this->booker())
            ->postJson(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Für dieses Abenteuer ist die Anmeldung nicht geöffnet.');
    }

    public function test_booking_a_full_adventure_goes_to_the_waitlist(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 1]);
        Booking::factory()->for($adventure)->create(['waitlisted' => false]);
        $player = Player::factory()->create();

        $this->actingAs($this->booker())
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ]);

        $this->assertTrue(Booking::firstWhere('player_id', $player->id)->waitlisted);
    }

    public function test_cannot_book_when_registration_is_closed(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $player = Player::factory()->create();

        $this->actingAs($this->booker())
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_agb_must_be_accepted(): void
    {
        $adventure = Adventure::factory()->create();
        $player = Player::factory()->create();

        $this->actingAs($this->booker())
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
            ])->assertSessionHasErrors('agb');
    }

    public function test_a_player_cannot_book_the_same_adventure_twice(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $player = Player::factory()->create();
        Booking::factory()->for($adventure)->create(['player_id' => $player->id]);

        $this->actingAs($this->booker())
            ->post(route('adventures.bookings.store', $adventure), [
                'player_id' => $player->id,
                'event_role_id' => 1,
                'agb' => '1',
            ])->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 1);
    }
}
