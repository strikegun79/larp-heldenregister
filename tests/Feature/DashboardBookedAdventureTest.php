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
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Startseite: das nächste gebuchte Abenteuer als Quest-Banner mit Countdown,
 * Termin mit Wochentag/Uhrzeit, Onboarding ausblendbar.
 */
class DashboardBookedAdventureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
        Carbon::setTestNow('2026-03-02 09:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithPlayer(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(10);
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);

        return [$user, $player];
    }

    private function bookedAdventure(Player $player, array $attributes): Adventure
    {
        $adventure = Adventure::factory()->create($attributes);
        Booking::factory()->for($adventure)->create(['player_id' => $player->id, 'status' => 'bestaetigt']);

        return $adventure;
    }

    public function test_next_booked_adventure_is_shown_as_quest_banner_with_countdown(): void
    {
        [$user, $player] = $this->userWithPlayer();
        $this->bookedAdventure($player, [
            'name' => 'Die Nebelburg',
            'start_at' => '2026-03-14 10:00',
            'end_at' => '2026-03-15 15:00',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Meine Anmeldungen')
            ->assertSee('wr-quest', false)
            ->assertSee('Noch 12 Tage bis zum Abenteuer!')
            ->assertSee('Die Nebelburg')
            ->assertSee('Sa, 14.03., 10:00 Uhr – So, 15.03.2026, 15:00 Uhr')
            ->assertSee('bestätigt')
            ->assertDontSee('Weitere Anmeldungen');
    }

    public function test_further_bookings_are_listed_as_cards(): void
    {
        [$user, $player] = $this->userWithPlayer();
        $this->bookedAdventure($player, ['name' => 'Erstes Abenteuer', 'start_at' => '2026-03-03 10:00', 'end_at' => '2026-03-03 16:00']);
        $this->bookedAdventure($player, ['name' => 'Zweites Abenteuer', 'start_at' => '2026-04-11 10:00', 'end_at' => '2026-04-11 16:00']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Morgen geht es los!')
            ->assertSeeInOrder(['Erstes Abenteuer', 'Weitere Anmeldungen', 'Zweites Abenteuer']);
    }

    public function test_onboarding_can_be_hidden_per_account(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(10);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Deine ersten Schritte')
            ->assertSee('Ausblenden')
            ->assertSee('onboarding_dismissed_'.$user->id, false);
    }
}
