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
 * Abenteuer-Detail im Waldritter-Design (Seite und Modal): sichtbarer Sperrgrund,
 * eigene Anmeldungen mit Aktionen als Text, Eckdaten, Tab-Mechanik erhalten.
 */
class AdventureDetailTest extends TestCase
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

    private function adventure(array $attributes = []): Adventure
    {
        return Adventure::factory()->create(array_merge([
            'start_at' => '2026-03-14 10:00',
            'end_at' => '2026-03-15 15:00',
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
            'max_player' => 10,
        ], $attributes));
    }

    private function modal(User $user, Adventure $adventure)
    {
        return $this->actingAs($user)
            ->get(route('adventures.show', $adventure), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_blocked_reason_is_visible_text_not_only_tooltip(): void
    {
        $user = $this->booker(); // ohne Spieler

        $this->modal($user, $this->adventure())
            ->assertOk()
            ->assertSee('Bevor du dich anmelden kannst:')
            ->assertSee('Spieler anlegen')
            ->assertSee(route('players.create'), false)
            ->assertDontSee('data-tooltip="Du benötigst', false);
    }

    public function test_no_blocked_notice_when_user_can_book(): void
    {
        $user = $this->booker();
        $user->players()->attach(Player::factory()->create()->id, ['self' => true]);

        $adventure = $this->adventure();

        $this->modal($user, $adventure)
            ->assertOk()
            ->assertDontSee('Bevor du dich anmelden kannst:')
            ->assertSee('data-modal-stack="'.route('adventures.bookings.create', $adventure).'"', false);
    }

    public function test_own_bookings_with_text_actions_and_no_foreign_bookings(): void
    {
        $user = $this->booker();
        $own = Player::factory()->create(['name' => 'Lena Eigen']);
        $user->players()->attach($own->id, ['self' => false]);
        $adventure = $this->adventure(['fee' => 25]);
        Booking::factory()->for($adventure)->create(['player_id' => $own->id, 'status' => 'bestaetigt', 'booked_by_user_id' => $user->id]);
        Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create(['name' => 'Fremdes Kind'])->id,
            'booked_by_user_id' => User::factory()->create()->id,
        ]);

        $this->modal($user, $adventure)
            ->assertOk()
            ->assertSee('Deine Anmeldungen (1)')
            ->assertSee('Lena Eigen')
            ->assertSee('bestätigt')
            ->assertSee('25,00 € offen')
            ->assertSee('Stornieren')
            ->assertSee('Bestätigung erneut senden')
            ->assertDontSee('Fremdes Kind');
    }

    public function test_facts_show_weekday_dates_free_fee_and_keep_tabs(): void
    {
        $this->modal($this->booker(), $this->adventure(['fee' => 0]))
            ->assertOk()
            ->assertSee('Das Wichtigste')
            ->assertSee('Sa, 14.03., 10:00 Uhr – So, 15.03.2026, 15:00 Uhr')
            ->assertSee('kostenlos')
            ->assertSee('Noch 10 von 10 frei')
            ->assertSee('Anmeldung möglich')
            ->assertSee('data-tab="event"', false)
            ->assertSee('data-tab="bookings"', false)
            ->assertSee('class="wr-v1"', false);
    }

    public function test_full_page_has_heading_and_back_link(): void
    {
        $adventure = $this->adventure(['name' => 'Die Nebelburg']);

        $this->actingAs($this->booker())->get(route('adventures.show', $adventure))
            ->assertOk()
            ->assertSee('Zurück zu den Abenteuern')
            ->assertSee('<h1 class="wr-heading', false)
            ->assertSee('Die Nebelburg');
    }
}
