<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Startseite: freie Plätze und Anmeldestatus im Segment „Nächstes Abenteuer“.
 */
class DashboardNextAdventureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function dashboard()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(10);

        return $this->actingAs($user)->get(route('dashboard'));
    }

    private function adventure(array $attributes = []): Adventure
    {
        return Adventure::factory()->create(array_merge([
            'start_at' => now()->addWeek(),
            'end_at' => now()->addWeek()->addDay(),
            'max_player' => 10,
        ], $attributes));
    }

    private function book(Adventure $adventure, int $count, array $attributes = []): void
    {
        Booking::factory()->count($count)->for($adventure)->create(array_merge(['status' => 'bestaetigt'], $attributes));
    }

    public function test_many_free_slots_are_green_and_open(): void
    {
        $this->book($this->adventure(), 2);

        $this->dashboard()
            ->assertOk()
            ->assertSee('8 von 10 Plätzen frei')
            ->assertSee('text-green-700')
            ->assertSee('Anmeldung offen');
    }

    public function test_few_free_slots_are_yellow(): void
    {
        $adventure = $this->adventure();
        $this->book($adventure, 5);
        $this->book($adventure, 1, ['status' => 'storniert']);

        $this->dashboard()
            ->assertSee('5 von 10 Plätzen frei')
            ->assertSee('text-amber-600');
    }

    public function test_full_adventure_is_red_with_waitlist(): void
    {
        $this->book($this->adventure(), 10);

        $this->dashboard()
            ->assertSee('0 von 10 Plätzen frei')
            ->assertSee('text-red-700')
            ->assertSee('Warteliste')
            ->assertDontSee('Anmeldung offen');
    }

    public function test_waitlist_shown_when_someone_is_waiting(): void
    {
        $adventure = $this->adventure(['waitlist_mode' => true]);
        $this->book($adventure, 1, ['waitlisted' => true, 'status' => 'offen']);

        $this->dashboard()
            ->assertSee('10 von 10 Plätzen frei')
            ->assertSee('Warteliste');
    }

    public function test_closed_registration(): void
    {
        $this->adventure(['event_status_id' => 40]);

        $this->dashboard()
            ->assertSee('Anmeldung geschlossen')
            ->assertDontSee('Anmeldung offen');
    }
}
