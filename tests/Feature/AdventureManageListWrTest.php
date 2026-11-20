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
 * Veranstaltungsliste der Verwaltung im Waldritter-Design: H1, Name als Link,
 * Belegung als Text, Löschen mit Rückfrage bzw. sichtbarem Sperrgrund.
 */
class AdventureManageListWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(30); // Projektleitung

        return $user;
    }

    public function test_list_has_heading_link_and_occupancy_text(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'Burgsturm', 'max_player' => 8]);

        $html = $this->actingAs($this->manager())->get(route('adventures.manage-index'))
            ->assertOk()
            ->assertSee('<h1 class="wr-heading">Abenteuer verwalten</h1>', false)
            ->assertSee('href="'.route('adventures.manage', $adventure).'"', false)
            ->assertSee('0 Teamer')
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/<tr[^>]*role="button"/', $html);
    }

    public function test_deletable_adventure_has_confirm_and_blocked_one_shows_reason(): void
    {
        $free = Adventure::factory()->create(['name' => 'Leer']);
        $booked = Adventure::factory()->create(['name' => 'Gebucht']);
        Booking::factory()->for($booked)->create();

        $this->actingAs($this->manager())->get(route('adventures.manage-index'))
            ->assertOk()
            ->assertSee('data-confirm="Abenteuer „Leer“ wirklich löschen?', false)
            ->assertSee('aria-label="Abenteuer Leer löschen"', false)
            ->assertSee('Nicht löschbar: 1 Spieler-Anmeldung');
    }
}
