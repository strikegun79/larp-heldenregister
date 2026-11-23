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

/**
 * Verwaltungs-Modal eines Abenteuers im Waldritter-Design (V2b): Wrapper, Reiter-Beschriftung
 * als Text, Status-Pills, zugängliche Icon-Buttons, Gefahrenzone, Formular einspaltig auf dem Handy.
 */
class AdventureManageModalWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat

        return $user;
    }

    public function test_modal_uses_text_labels_pills_and_accessible_icon_buttons(): void
    {
        $adventure = Adventure::factory()->create(['fee' => 10]);
        Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create(['name' => 'Lena', 'lastname' => 'Test'])->id,
            'status' => 'bestaetigt',
            'paid' => false,
        ]);

        $this->actingAs($this->registrar())
            ->get(route('adventures.manage', $adventure), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSee('Anmeldungen (1 aktiv · 0 Warteliste · 0 storniert)')
            ->assertSee('wr-pill wr-pill--small wr-pill--success', false)
            ->assertSee('aria-label="Beitrag von Lena Test als bezahlt markieren"', false)
            ->assertSee('aria-label="Anmeldung von Lena Test bearbeiten"', false)
            ->assertSee('data-tooltip="Als bezahlt markieren"', false)
            ->assertSee('Gefahrenzone')
            ->assertSee('wr-form space-y-6', false)
            ->assertDontSee('⏳')
            ->assertDontSee('✓ bestätigt');
    }

    public function test_full_page_has_heading_and_visible_delete_reason(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'Burgsturm']);
        Booking::factory()->for($adventure)->create();

        $this->actingAs($this->registrar())->get(route('adventures.manage', $adventure))
            ->assertOk()
            ->assertSee('<h1 class="wr-heading break-words">Burgsturm</h1>', false)
            ->assertSee('Zurück zur Verwaltungsübersicht')
            ->assertDontSee('data-tooltip="Nicht löschbar', false);
    }
}
