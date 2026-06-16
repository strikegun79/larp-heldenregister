<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\User;
use App\Notifications\BookingCancelled;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * ADV-21: Gäste-Anmeldungen und Storno-Info an die Projektleitung.
 */
class EventGuestBookingTest extends TestCase
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
        $user->roles()->attach(60); // Event buchen

        return $user;
    }

    public function test_user_can_register_a_guest(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);
        $booker = $this->booker();

        $this->actingAs($booker)
            ->postJson(route('adventures.bookings.store-guest', $adventure), [
                'guest_name' => 'Gerd', 'guest_lastname' => 'Gast', 'guest_age' => 42, 'guest_place' => 'Gießen',
                'event_role_id' => 1, 'agb' => '1', 'kontakt_telefon' => '+49 123 456789',
            ])
            ->assertOk();

        $this->assertDatabaseHas('bookings', [
            'adventure_id' => $adventure->id,
            'player_id' => null,
            'guest_name' => 'Gerd',
            'guest_lastname' => 'Gast',
            'booked_by_user_id' => $booker->id,
        ]);
    }

    public function test_multiple_guests_per_user_and_event(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 10]);
        $booker = $this->booker();

        foreach (['Anna', 'Bob'] as $name) {
            $this->actingAs($booker)->postJson(route('adventures.bookings.store-guest', $adventure), [
                'guest_name' => $name, 'guest_lastname' => 'Gast', 'event_role_id' => 1, 'agb' => '1', 'kontakt_telefon' => '+49 123 456789',
            ])->assertOk();
        }

        $this->assertSame(2, $adventure->bookings()->whereNull('player_id')->count());
    }

    public function test_guest_form_shows_no_ep_hint(): void
    {
        $adventure = Adventure::factory()->create(['max_player' => 5]);

        $this->actingAs($this->booker())
            ->get(route('adventures.bookings.create-guest', $adventure))
            ->assertOk()
            ->assertSee('keine Erfahrungspunkte');
    }

    public function test_guest_is_marked_in_booking_list(): void
    {
        $adventure = Adventure::factory()->create();
        Booking::factory()->for($adventure)->create([
            'player_id' => null, 'guest_name' => 'Gerd', 'guest_lastname' => 'Gast',
            'booked_by_user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->userWithViewAll())
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Gerd Gast')
            ->assertSee('Gast');
    }

    public function test_guests_excluded_from_ep_award(): void
    {
        Notification::fake();
        $adventure = Adventure::factory()->registrationClosed()->create(['loot_ep_day' => 5]);
        Booking::factory()->for($adventure)->create([
            'player_id' => null, 'guest_name' => 'Gerd', 'guest_lastname' => 'Gast',
        ]);

        // Award EP -> Gäste haben keinen event_visit/aktiven Helden -> keine EP.
        $this->actingAs($this->userWithViewAll())
            ->postJson(route('adventures.award-ep', $adventure))
            ->assertOk();

        $this->assertDatabaseCount('ep_transactions', 0);
    }

    public function test_paid_booking_can_be_cancelled_and_notifies_project_lead(): void
    {
        Notification::fake();
        $projectLead = User::factory()->create();
        $projectLead->roles()->attach(30); // Projektleitung

        $adventure = Adventure::factory()->create();
        $booking = Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'paid' => true, // auch bezahlte dürfen storniert werden
        ]);

        $this->actingAs($this->userWithViewAll())
            ->deleteJson(route('adventures.bookings.destroy', [$adventure, $booking]))
            ->assertOk();

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        Notification::assertSentTo($projectLead, BookingCancelled::class);
    }

    public function test_participants_pdf_view_shows_diverse_count(): void
    {
        // ADV-20: „Divers" in der Kopfzeile der Teilnehmer-PDF.
        $adventure = Adventure::factory()->create();
        $html = view('adventures.participants_pdf', [
            'adventure' => $adventure->load(['location', 'category']),
            'bookings' => collect(),
            'male' => 0, 'female' => 0, 'diverse' => 0,
        ])->render();

        $this->assertStringContainsString('Divers:', $html);
    }

    private function userWithViewAll(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat (view-all-bookings + cancel)

        return $user;
    }
}
