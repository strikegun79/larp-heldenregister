<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Issue #42: Spieler löschen – Helden ausblenden, Talk-Konto deaktivieren,
 * nach 30 Tagen anonymisieren, danach keine Wiederherstellung mehr.
 */
class PlayerDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    /** @return array{0: User, 1: Player, 2: Hero} */
    private function familyWithHero(array $heroAttributes = []): array
    {
        $parent = User::factory()->create();
        $player = Player::factory()->create(['name' => 'Mara', 'lastname' => 'Muster', 'city' => 'Gießen']);
        $parent->players()->attach($player);
        $hero = Hero::factory()->create(array_merge([
            'player_id' => $player->id,
            'character_name' => 'Ylvaine Sturmherz',
            'public_visible' => true,
            'public_searchable' => true,
            'public_suggested' => true,
        ], $heroAttributes));

        return [$parent, $player, $hero];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(10);

        return $admin;
    }

    public function test_loeschformular_nennt_helden_und_frist(): void
    {
        [$parent, $player] = $this->familyWithHero();

        $this->actingAs($parent)
            ->get(route('players.edit', $player))
            ->assertOk()
            ->assertSee('Der Spieler und seine Helden werden aus dem Heldenregister entfernt.')
            ->assertSee('Innerhalb von 30 Tagen')
            ->assertSee('Dabei wird 1 Held ebenfalls entfernt.');
    }

    public function test_nutzer_loescht_spieler_helden_verschwinden_ueberall(): void
    {
        [$parent, $player, $hero] = $this->familyWithHero();

        $this->actingAs($parent)
            ->delete(route('players.destroy', $player))
            ->assertRedirect(route('players.index'));

        $this->assertSoftDeleted('players', ['id' => $player->id]);
        $this->assertNull(Hero::find($hero->id));
        $this->assertDatabaseHas('heroes', ['id' => $hero->id]);

        // Öffentlich: Profil, Suche, Vorschläge.
        $this->get(route('public.hero', $hero->public_code))->assertNotFound();
        $this->get(route('public.hero.search.go', ['code' => 'Ylvaine']))
            ->assertOk()->assertDontSee('Ylvaine Sturmherz');
        $this->get(route('public.hero.search'))->assertOk()->assertDontSee('Ylvaine Sturmherz');

        // Eigene Liste.
        $this->actingAs($parent)->get(route('players.index'))->assertOk()->assertDontSee('Mara');
    }

    public function test_wiederherstellen_bringt_helden_zurueck(): void
    {
        [, $player, $hero] = $this->familyWithHero();
        $player->delete();

        $this->actingAs($this->admin())
            ->patch(route('admin.players.restore', $player->id))
            ->assertRedirect(route('admin.players.index'));

        $this->assertNotSoftDeleted('players', ['id' => $player->id]);
        $this->assertNotNull(Hero::find($hero->id));
        $this->get(route('public.hero', $hero->public_code))->assertOk();
    }

    public function test_nach_30_tagen_anonymisiert_ohne_wiederherstellung(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('players/p.jpg', 'x');
        [, $player, $hero] = $this->familyWithHero();
        $player->update(['image' => 'players/p.jpg', 'allergien' => 'Nüsse']);
        $player->delete();
        Player::withTrashed()->whereKey($player->id)->update(['deleted_at' => now()->subDays(31)]);

        $recent = Player::factory()->create(['name' => 'Frisch']);
        $recent->delete();

        $this->artisan('dsgvo:prune')->assertSuccessful();

        $anon = Player::withTrashed()->find($player->id);
        $this->assertSame('Gelöscht', $anon->name);
        $this->assertNull($anon->lastname);
        $this->assertNull($anon->city);
        $this->assertNull($anon->allergien);
        $this->assertNull($anon->image);
        $this->assertNotNull($anon->anonymized_at);
        Storage::disk('public')->assertMissing('players/p.jpg');
        // Helden bleiben (ausgeblendet) bis zur endgültigen Löschung nach 3 Jahren.
        $this->assertDatabaseHas('heroes', ['id' => $hero->id]);

        // Innerhalb der Frist unverändert.
        $this->assertSame('Frisch', Player::withTrashed()->find($recent->id)->name);
        $this->assertNull(Player::withTrashed()->find($recent->id)->anonymized_at);

        // Keine Wiederherstellung mehr.
        $admin = $this->admin();
        $this->actingAs($admin)
            ->patch(route('admin.players.restore', $player->id))
            ->assertSessionHas('error');
        $this->assertSoftDeleted('players', ['id' => $player->id]);

        $this->actingAs($admin)
            ->get(route('admin.players.index', ['trashed' => 'with']))
            ->assertOk();
    }

    public function test_loeschen_deaktiviert_talk_konto_ohne_extra_mail(): void
    {
        config([
            'nextcloud.url' => 'https://cloud.example.test',
            'nextcloud.user' => 'heldenregister',
            'nextcloud.app_password' => 'geheim',
        ]);
        Setting::set('talk_enabled', '1');
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['ocs' => ['data' => []]])]);

        [$parent, $player] = $this->familyWithHero();
        $player->forceFill([
            'talk_approved_at' => now(),
            'talk_account_ready_at' => now(),
            'nextcloud_user_id' => 'mara.muster',
            'nextcloud_account_created' => true,
        ])->save();

        $this->actingAs($parent)->delete(route('players.destroy', $player));

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && str_contains($r->url(), '/cloud/users/mara.muster/disable'));
        Notification::assertNothingSent();
        $this->assertNull(Player::withTrashed()->find($player->id)->talk_approved_at);
    }
}
