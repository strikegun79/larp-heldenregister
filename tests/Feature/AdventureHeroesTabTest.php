<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reiter „Helden" in der Event-Detailansicht: aktive Helden der Teilnehmer
 * mit Klassen, nur für Lehrmeister, Projektleitung, Spielleiter, Bürokrat, Admin.
 */
class AdventureHeroesTabTest extends TestCase
{
    use RefreshDatabase;

    private Adventure $adventure;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
        $this->adventure = Adventure::factory()->create();
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    /** Legt einen gebuchten Spieler mit aktivem Held (inkl. Klasse) an. */
    private function bookHero(string $heroName, array $bookingAttributes = []): Hero
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id, 'character_name' => $heroName]);
        $player->update(['active_hero_id' => $hero->id]);

        Booking::factory()->create(array_merge([
            'adventure_id' => $this->adventure->id,
            'player_id' => $player->id,
        ], $bookingAttributes));

        return $hero;
    }

    public static function allowedRoles(): array
    {
        return [
            'Admin' => [10],
            'Bürokrat' => [20],
            'Projektleitung' => [30],
            'Spielleiter' => [40],
            'Lehrmeister' => [45],
        ];
    }

    public static function deniedRoles(): array
    {
        return [
            'Teamer' => [50],
            'Event buchen' => [60],
        ];
    }

    /** @dataProvider allowedRoles */
    public function test_allowed_roles_see_heroes_tab_with_classes(int $roleId): void
    {
        $hero = $this->bookHero('Alrik vom Walde');
        HeroClass::create(['id' => 1, 'slug' => 'warrior', 'name' => 'Krieger', 'ep_cost' => 5]);
        $hero->classes()->attach(1);

        $this->actingAs($this->userWithRole($roleId))
            ->get(route('adventures.show', $this->adventure))
            ->assertOk()
            ->assertSee('Helden (1)')
            ->assertSee('Alrik vom Walde')
            ->assertSee('Krieger')
            ->assertSee(route('heroes.show', $hero), false);
    }

    /** @dataProvider deniedRoles */
    public function test_other_roles_do_not_see_heroes_tab(int $roleId): void
    {
        $this->bookHero('Alrik vom Walde');

        $this->actingAs($this->userWithRole($roleId))
            ->get(route('adventures.show', $this->adventure))
            ->assertOk()
            ->assertDontSee('data-tab="heroes"', false)
            ->assertDontSee('Alrik vom Walde');
    }

    public function test_cancelled_waitlisted_and_nsc_bookings_are_excluded(): void
    {
        $this->bookHero('Aktiver Teilnehmer');
        $this->bookHero('Stornierter Held', ['status' => 'storniert']);
        $this->bookHero('Wartelisten Held', ['waitlisted' => true]);
        $this->bookHero('NSC Held', ['event_role_id' => 3]); // Teamer-Rolle

        $this->actingAs($this->userWithRole(45))
            ->get(route('adventures.show', $this->adventure))
            ->assertOk()
            ->assertSee('Helden (1)')
            ->assertSee('Aktiver Teilnehmer')
            ->assertDontSee('Stornierter Held')
            ->assertDontSee('Wartelisten Held')
            ->assertDontSee('NSC Held');
    }

    public function test_hero_without_name_is_linked_with_italic_player_name(): void
    {
        $hero = $this->bookHero('');

        $this->actingAs($this->userWithRole(45))
            ->get(route('adventures.show', $this->adventure))
            ->assertOk()
            ->assertSee(route('heroes.show', $hero), false)
            ->assertSee('<em>'.e($hero->player->full_name).'</em>', false);
    }

    public function test_player_without_active_hero_is_listed_with_hint(): void
    {
        $player = Player::factory()->create(['active_hero_id' => null]);
        Booking::factory()->create(['adventure_id' => $this->adventure->id, 'player_id' => $player->id]);

        $this->actingAs($this->userWithRole(40))
            ->get(route('adventures.show', $this->adventure))
            ->assertOk()
            ->assertSee('kein aktiver Held')
            ->assertSee($player->full_name);
    }
}
