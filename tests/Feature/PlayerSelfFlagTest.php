<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PLAY-05: Pro Nutzer höchstens ein „self"-Spieler.
 */
class PlayerSelfFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);

        return $user;
    }

    private function selfCount(User $user): int
    {
        return $user->players()->wherePivot('self', true)->count();
    }

    public function test_creating_a_second_self_player_resets_the_first(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('players.store'), [
            'name' => 'Ich', 'lastname' => 'Selbst', 'self' => '1',
        ])->assertRedirect();
        $first = Player::firstWhere('name', 'Ich');
        $this->assertTrue((bool) $user->players()->wherePivot('self', true)->first()?->is($first));

        // Zweiter self-Spieler -> erster wird zurückgesetzt.
        $this->actingAs($user)->post(route('players.store'), [
            'name' => 'Zweit', 'lastname' => 'Charakter', 'self' => '1',
        ])->assertRedirect();

        $this->assertSame(1, $this->selfCount($user));
        $this->assertFalse((bool) $first->fresh()->users()->wherePivot('user_id', $user->id)->wherePivot('self', true)->exists());
    }

    public function test_updating_self_resets_others(): void
    {
        $user = $this->user();
        $a = Player::factory()->create();
        $b = Player::factory()->create();
        $user->players()->attach($a->id, ['self' => true]);
        $user->players()->attach($b->id, ['self' => false]);

        // B per Update auf self setzen.
        $this->actingAs($user)->putJson(route('players.update', $b), [
            'name' => $b->name, 'lastname' => $b->lastname, 'self' => '1',
        ])->assertOk();

        $this->assertSame(1, $this->selfCount($user));
        $this->assertTrue($user->players()->wherePivot('self', true)->first()->is($b));
    }

    public function test_self_flag_is_per_user(): void
    {
        $u1 = $this->user();
        $u2 = $this->user();
        $shared = Player::factory()->create();
        $u1->players()->attach($shared->id, ['self' => true]);
        $u2->players()->attach($shared->id, ['self' => false]);

        // u2 markiert einen eigenen Spieler als self -> u1 bleibt unberührt.
        $own = Player::factory()->create();
        $u2->players()->attach($own->id, ['self' => false]);
        $this->actingAs($u2)->putJson(route('players.update', $own), [
            'name' => $own->name, 'lastname' => $own->lastname, 'self' => '1',
        ])->assertOk();

        $this->assertSame(1, $this->selfCount($u1)); // u1 unverändert
        $this->assertSame(1, $this->selfCount($u2));
    }
}
