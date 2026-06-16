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

/**
 * BOOK-11: Kontaktrufnummer bei der Event-Anmeldung (Notfallkontakt).
 */
class BookingContactPhoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function booker(?string $phone = null): User
    {
        $user = User::factory()->create(['phone' => $phone]);
        $user->roles()->attach(60); // event_booking: adventure.book

        return $user;
    }

    private function openAdventure(): Adventure
    {
        return Adventure::factory()->create([
            'event_status_id' => \App\Models\EventStatus::REGISTRATION_OPEN,
            'max_player' => 30,
        ]);
    }

    private function storePayload(int $playerId): array
    {
        return [
            'player_id' => $playerId,
            'event_role_id' => EventRole::orderBy('id')->first()->id,
            'agb' => '1',
        ];
    }

    // ── Anmeldung (store) ───────────────────────────────────────────────────

    public function test_booking_requires_kontakt_telefon(): void
    {
        $user = $this->booker(null); // kein Profil-Telefon
        $adventure = $this->openAdventure();
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store', $adventure), $this->storePayload($player->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors('kontakt_telefon');
    }

    public function test_booking_saves_kontakt_telefon(): void
    {
        $user = $this->booker(null);
        $adventure = $this->openAdventure();
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store', $adventure), array_merge(
                $this->storePayload($player->id),
                ['kontakt_telefon' => '+49 123 456789']
            ))
            ->assertOk();

        $this->assertSame('+49 123 456789', $adventure->bookings()->first()->kontakt_telefon);
    }

    public function test_create_form_prefills_phone_from_profile(): void
    {
        $user = $this->booker('+49 999 888777');
        $adventure = $this->openAdventure();
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        $this->actingAs($user)
            ->get(route('adventures.bookings.create', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('+49 999 888777', false);
    }

    public function test_create_form_has_no_prefill_without_profile_phone(): void
    {
        $user = $this->booker(null);
        $adventure = $this->openAdventure();
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        $response = $this->actingAs($user)
            ->get(route('adventures.bookings.create', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        // Kein Profil-Telefon → Hinweis-Text erscheint nicht
        $response->assertDontSee('Aus deinem Profil übernommen', false);
    }

    // ── Bearbeiten (update) ─────────────────────────────────────────────────

    public function test_update_requires_kontakt_telefon(): void
    {
        $booking = Booking::factory()->create(['kontakt_telefon' => '+49 111 222333']);
        $user = User::factory()->create();
        $user->roles()->attach(60);

        $this->actingAs($user)
            ->putJson(route('adventures.bookings.update', [$booking->adventure_id, $booking]), [
                'event_role_id' => EventRole::orderBy('id')->first()->id,
                // kontakt_telefon fehlt
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('kontakt_telefon');
    }

    public function test_update_saves_kontakt_telefon(): void
    {
        $booking = Booking::factory()->create(['kontakt_telefon' => '+49 111 222333']);
        $user = User::factory()->create();
        $user->roles()->attach(60);

        $this->actingAs($user)
            ->putJson(route('adventures.bookings.update', [$booking->adventure_id, $booking]), [
                'event_role_id' => EventRole::orderBy('id')->first()->id,
                'kontakt_telefon' => '+49 777 888999',
            ])
            ->assertOk();

        $this->assertSame('+49 777 888999', $booking->fresh()->kontakt_telefon);
    }

    // ── Gast-Anmeldung (storeGuest) ────────────────────────────────────────

    public function test_guest_booking_requires_kontakt_telefon(): void
    {
        $user = $this->booker(null);
        $adventure = $this->openAdventure();

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store-guest', $adventure), [
                'guest_name' => 'Max',
                'guest_lastname' => 'Muster',
                'event_role_id' => EventRole::orderBy('id')->first()->id,
                'agb' => '1',
                // kontakt_telefon fehlt
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('kontakt_telefon');
    }

    public function test_guest_booking_saves_kontakt_telefon(): void
    {
        $user = $this->booker(null);
        $adventure = $this->openAdventure();

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store-guest', $adventure), [
                'guest_name' => 'Max',
                'guest_lastname' => 'Muster',
                'event_role_id' => EventRole::orderBy('id')->first()->id,
                'agb' => '1',
                'kontakt_telefon' => '+49 555 123456',
            ])
            ->assertOk();

        $this->assertSame('+49 555 123456', $adventure->bookings()->first()->kontakt_telefon);
    }
}
