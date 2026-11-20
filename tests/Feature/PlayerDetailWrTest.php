<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spieler-Detail im Waldritter-Design (Seite und Modal): Avatar-Upload unter dem Avatar,
 * eindeutige IDs, aktiver Held ohne Button im Link, Fotoerlaubnis als Text.
 */
class PlayerDetailWrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function ownerWithPlayer(): array
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);
        $player = Player::factory()->create(['name' => 'Lena', 'lastname' => 'Beispiel']);
        $user->players()->attach($player->id, ['self' => false]);

        return [$user, $player];
    }

    private function modal(User $user, Player $player)
    {
        return $this->actingAs($user)->get(route('players.show', $player), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_avatar_upload_ids_are_unique_and_inside_general_section(): void
    {
        [$user, $player] = $this->ownerWithPlayer();

        $html = $this->modal($user, $player)->assertOk()->assertSee('class="wr-v1"', false)->getContent();

        $this->assertSame(1, substr_count($html, 'id="avatar-file-input-m"'));
        $this->assertSame(1, substr_count($html, 'id="avatar-file-input-d"'));
        $this->assertStringContainsString('Spielerdaten bearbeiten', $html);
    }

    public function test_heroes_offer_active_choice_and_hero_book_link_separately(): void
    {
        [$user, $player] = $this->ownerWithPlayer();
        $active = Hero::factory()->for($player)->create(['character_name' => 'Ilva']);
        $other = Hero::factory()->for($player)->create(['character_name' => 'Bran']);
        $player->update(['active_hero_id' => $active->id]);

        $html = $this->modal($user, $player)
            ->assertOk()
            ->assertSee('aktiver Held')
            ->assertSee('Als aktiven Helden wählen')
            ->assertSee('Heldenbuch öffnen')
            ->assertSee(route('heroes.show', $other), false)
            ->getContent();

        // Keine Tabellenzeile mit data-navigate mehr, in der ein Button steckt.
        $this->assertStringNotContainsString('data-navigate', $html);
    }

    public function test_photo_consent_is_shown_as_text_with_confirm(): void
    {
        [$user, $player] = $this->ownerWithPlayer();
        $adventure = Adventure::factory()->create(['name' => 'Sommercamp']);
        Booking::factory()->for($adventure)->create(['player_id' => $player->id, 'status' => 'bestaetigt', 'fotoerlaubnis' => true]);

        $this->modal($user, $player)
            ->assertOk()
            ->assertSee('Fotoerlaubnis (1)')
            ->assertSee('Fotoerlaubnis erteilt')
            ->assertSee('Widerrufen')
            ->assertSee('Art. 7 DSGVO')
            ->assertSee('data-tab="p-buchungen"', false);
    }

    public function test_full_page_has_heading_and_back_link(): void
    {
        [$user, $player] = $this->ownerWithPlayer();

        $this->actingAs($user)->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('Zurück zu deinen Spielern')
            ->assertSee('<h1 class="wr-heading', false)
            ->assertSee('Lena Beispiel');
    }
}
