<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Player;
use App\Models\TeamerSignup;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Anmelde-Inspektor im Waldritter-Design (V3): Status als Pills mit Text, keine Emojis,
 * Überschriften im neuen Stil, beschriftete Aktionen unverändert.
 */
class InspectorWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    public function test_booking_inspector_shows_status_pills_and_labelled_actions(): void
    {
        $registrar = User::factory()->create();
        $registrar->roles()->attach(20); // Bürokrat
        $adventure = Adventure::factory()->create(['fee' => 10]);
        $booking = Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
            'status' => 'bestaetigt',
            'paid' => true,
        ]);

        $this->actingAs($registrar)
            ->get(route('adventures.bookings.inspector', [$adventure, $booking]))
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSee('wr-pill wr-pill--success', false)
            ->assertSee('bestätigt')
            ->assertSee('bezahlt')
            ->assertSee('wr-section-heading__title', false)
            ->assertSee('Stornieren')
            ->assertDontSee('✓')
            ->assertDontSee('⏳')
            ->assertDontSee('font-uncial', false);
    }

    public function test_teamer_inspector_shows_status_pills(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);
        $adventure = Adventure::factory()->create();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id, 'approved_at' => now()]);

        $this->actingAs($admin)
            ->get(route('adventures.teamer.inspector', [$adventure, $signup]))
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSee('wr-pill wr-pill--info', false)
            ->assertSee('bestätigt')
            ->assertDontSee('✓')
            ->assertDontSee('font-uncial', false);
    }
}
