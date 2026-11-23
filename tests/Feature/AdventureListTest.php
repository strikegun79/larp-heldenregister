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
 * Abenteuerliste im Waldritter-Design: Ansichten, Suche, Anmeldestatus als Text,
 * Platzangabe nur bei offener Anmeldung, Sichtbarkeit unverändert.
 */
class AdventureListTest extends TestCase
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
        $user->roles()->attach(40); // Spielleiter: nur ansehen

        return $user;
    }

    private function adventure(array $attributes = []): Adventure
    {
        return Adventure::factory()->create(array_merge([
            'start_at' => now()->addWeek(),
            'end_at' => now()->addWeek()->addDay(),
            'max_player' => 10,
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
        ], $attributes));
    }

    public function test_open_adventure_shows_status_text_and_free_slots(): void
    {
        $adventure = $this->adventure(['name' => 'Sommercamp', 'min_age' => 7, 'max_age' => 17]);
        Booking::factory()->count(3)->for($adventure)->create(['status' => 'bestaetigt']);

        $this->actingAs($this->viewer())->get(route('adventures.index'))
            ->assertOk()
            ->assertSee('Sommercamp')
            ->assertSee('Anmeldung möglich')
            ->assertSee('Noch 7 Plätze frei')
            ->assertSee('7–17 Jahre')
            ->assertSee('wr-adventure-link', false)
            ->assertSee('href="'.route('adventures.show', $adventure).'"', false);
    }

    public function test_full_open_adventure_shows_waitlist(): void
    {
        $adventure = $this->adventure(['max_player' => 2]);
        Booking::factory()->count(2)->for($adventure)->create(['status' => 'bestaetigt']);

        $this->actingAs($this->viewer())->get(route('adventures.index'))
            ->assertSee('Nur noch Warteliste')
            ->assertDontSee('Plätze frei');
    }

    public function test_closed_adventure_never_shows_waitlist_or_slots(): void
    {
        $adventure = $this->adventure(['max_player' => 1, 'event_status_id' => EventStatus::REGISTRATION_CLOSED]);
        Booking::factory()->for($adventure)->create(['status' => 'bestaetigt']);

        $this->actingAs($this->viewer())->get(route('adventures.index'))
            ->assertSee('Anmeldung geschlossen')
            ->assertDontSee('Nur noch Warteliste')
            ->assertDontSee('Plätze frei');
    }

    public function test_sort_views_are_links_with_current_marker(): void
    {
        $this->adventure();

        $this->actingAs($this->viewer())->get(route('adventures.index', ['sort' => 'asc']))
            ->assertOk()
            ->assertSee('Nach Status')
            ->assertSee('Letztes zuerst')
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('ui selection dropdown', false);
    }

    public function test_grouped_view_has_status_sections_in_order(): void
    {
        $this->adventure(['name' => 'Geplantes Abenteuer', 'event_status_id' => 20]);
        $this->adventure(['name' => 'Offenes Abenteuer']);

        $this->actingAs($this->viewer())->get(route('adventures.index', ['sort' => 'grouped']))
            ->assertOk()
            ->assertSeeInOrder(['Anmeldung offen', 'Offenes Abenteuer', 'geplant', 'Geplantes Abenteuer']);
    }

    public function test_search_without_result_shows_hint(): void
    {
        $this->adventure(['name' => 'Burgfest am See']);

        $this->actingAs($this->viewer())->get(route('adventures.index', ['q' => 'Winterzauber', 'sort' => 'asc']))
            ->assertOk()
            ->assertDontSee('Burgfest am See')
            ->assertSee('Kein Abenteuer zu „Winterzauber“ gefunden.')
            ->assertSee('Alle Abenteuer zeigen');
    }

    public function test_card_shows_only_own_bookings(): void
    {
        $user = $this->viewer();
        $own = Player::factory()->create(['name' => 'Lena Eigen']);
        $foreign = Player::factory()->create(['name' => 'Fremdes Kind']);
        $user->players()->attach($own->id, ['self' => false]);

        $adventure = $this->adventure(['name' => 'Burgfest']);
        Booking::factory()->for($adventure)->create(['player_id' => $own->id, 'status' => 'bestaetigt']);
        Booking::factory()->for($adventure)->create(['player_id' => $foreign->id, 'status' => 'bestaetigt']);

        $this->actingAs($user)->get(route('adventures.index'))
            ->assertOk()
            ->assertSee('Deine Anmeldungen')
            ->assertSee('Lena Eigen')
            ->assertDontSee('Fremdes Kind');
    }

    public function test_cancelled_own_booking_is_not_shown(): void
    {
        $user = $this->viewer();
        $own = Player::factory()->create(['name' => 'Lena Eigen']);
        $user->players()->attach($own->id, ['self' => false]);

        $adventure = $this->adventure();
        Booking::factory()->for($adventure)->create(['player_id' => $own->id, 'status' => 'storniert']);

        $this->actingAs($user)->get(route('adventures.index'))
            ->assertOk()
            ->assertDontSee('Deine Anmeldungen');
    }

    public function test_several_adventures_with_bookings_do_not_lazy_load(): void
    {
        // Lazy-Loading-Schutz greift erst ab mehreren geladenen Modellen.
        $user = $this->viewer();
        $own = Player::factory()->create();
        $user->players()->attach($own->id, ['self' => true]);

        foreach (['Erstes', 'Zweites'] as $name) {
            $adventure = $this->adventure(['name' => $name.' Abenteuer', 'fee' => 10]);
            Booking::factory()->for($adventure)->create(['player_id' => $own->id, 'status' => 'offen']);
        }

        foreach (['grouped', 'asc'] as $sort) {
            $this->actingAs($user)->get(route('adventures.index', ['sort' => $sort]))
                ->assertOk()
                ->assertSee('Erstes Abenteuer')
                ->assertSee('Zweites Abenteuer')
                ->assertSee('wartet auf Bestätigung');
        }
    }

    public function test_hidden_adventures_stay_invisible_for_viewers(): void
    {
        $this->adventure(['name' => 'Geheimes Abenteuer', 'is_hidden' => true]);

        $this->actingAs($this->viewer())->get(route('adventures.index'))
            ->assertOk()
            ->assertDontSee('Geheimes Abenteuer');
    }
}
