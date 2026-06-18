<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADV-12: Kalenderansicht kommender Events, chronologisch nach Monat.
 */
class EventCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(40); // Spielleiter: events.view -> adventure.access

        return $user;
    }

    public function test_calendar_lists_upcoming_events_with_month_heading(): void
    {
        $future = Adventure::factory()->create([
            'name' => 'Sommerlager',
            'start_at' => now()->addMonths(2)->setDay(15)->setTime(10, 0),
            'end_at' => now()->addMonths(2)->setDay(17),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('adventures.calendar'))
            ->assertOk()
            ->assertSee('Sommerlager')
            ->assertSee('Kommende Events')
            ->assertSee('Plätze');
    }

    public function test_past_events_are_excluded(): void
    {
        Adventure::factory()->create([
            'name' => 'Altes Event',
            'start_at' => now()->subMonth(),
            'end_at' => now()->subMonth()->addDay(),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('adventures.calendar'))
            ->assertOk()
            ->assertDontSee('Altes Event');
    }

    public function test_calendar_requires_access(): void
    {
        $participant = User::factory()->create();
        $participant->roles()->attach(70); // Teilnehmer: kein adventure.access

        $this->actingAs($participant)
            ->get(route('adventures.calendar'))
            ->assertForbidden();
    }
}
