<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spielerformular im Waldritter-Design: „Wen legst du an?“ oben (Feld self als Auswahl),
 * Einwilligung der Erziehungsberechtigten nur bei „Mein Kind“, Speichern unverändert.
 */
class PlayerFormWrTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lena',
            'lastname' => 'Beispiel',
            'dayofbirth' => '2015-04-01',
            'address_same_as_guardian' => '1',
        ], $overrides);
    }

    public function test_create_form_asks_who_first_without_preselection(): void
    {
        $html = $this->actingAs($this->user())
            ->get(route('players.create'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('class="wr-v1"', false)
            ->assertSeeInOrder(['Wen legst du an?', 'Person', 'Anschrift', 'Gesundheitsdaten'])
            ->assertSee('Mich selbst')
            ->assertSee('Mein Kind / eine betreute Person')
            ->getContent();

        $this->assertStringNotContainsString('checked', substr($html, strpos($html, 'id="self_yes"') - 120, 400));
    }

    public function test_child_requires_parental_consent(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('players.store'), $this->payload(['self' => '0']))
            ->assertSessionHasErrors('parental_consent');

        $this->actingAs($user)
            ->post(route('players.store'), $this->payload(['self' => '0', 'parental_consent' => '1']))
            ->assertSessionHasNoErrors();

        $player = Player::firstWhere('name', 'Lena');
        $this->assertNotNull($player->parental_consent_at);
        $this->assertFalse($user->players()->whereKey($player->id)->wherePivot('self', true)->exists());
    }

    public function test_self_needs_no_parental_consent_and_is_marked_self(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('players.store'), $this->payload(['self' => '1']))
            ->assertSessionHasNoErrors();

        $player = Player::firstWhere('name', 'Lena');
        $this->assertTrue($user->players()->whereKey($player->id)->wherePivot('self', true)->exists());
    }

    public function test_edit_form_preselects_current_choice(): void
    {
        $user = $this->user();
        $player = Player::factory()->create();
        $user->players()->attach($player->id, ['self' => true]);

        $html = $this->actingAs($user)
            ->get(route('players.edit', $player), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('form="player-edit-form"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/id="self_yes"[^>]*checked/s', $html);
    }
}
