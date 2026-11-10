<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\IdCardCode;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EpTransactionTypeSeeder::class]);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10); // Admin
        return $user;
    }

    private function burokrat(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat / registrar
        return $user;
    }

    private function teilnehmer(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70); // Teilnehmer
        return $user;
    }

    private function heroWithCode(string $code = 'ABCDEF'): Hero
    {
        $player = Player::factory()->create();
        return Hero::factory()->create(['player_id' => $player->id, 'public_code' => $code]);
    }

    private function heroWithoutCode(): Hero
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);
        // creating-Hook generiert immer einen Code; direkt per Query nullen.
        Hero::whereKey($hero->id)->update(['public_code' => null]);
        return $hero->fresh();
    }

    // --- Zugriffskontrolle ---

    public function test_admin_kann_id_cards_index_aufrufen(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.id-cards.index'))
            ->assertOk();
    }

    public function test_burokrat_kann_id_cards_index_aufrufen(): void
    {
        $this->actingAs($this->burokrat())
            ->get(route('admin.id-cards.index'))
            ->assertOk();
    }

    public function test_teilnehmer_hat_keinen_zugriff(): void
    {
        $this->actingAs($this->teilnehmer())
            ->get(route('admin.id-cards.index'))
            ->assertForbidden();
    }

    public function test_gast_wird_weitergeleitet(): void
    {
        $this->get(route('admin.id-cards.index'))
            ->assertRedirect(route('login'));
    }

    // --- Code generieren ---

    public function test_generate_erstellt_codes_und_gibt_pdf_zurueck(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.id-cards.generate'), ['count' => 3]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseCount('id_card_codes', 3);
        $this->assertDatabaseMissing('id_card_codes', ['hero_id' => null === false]);

        $codes = IdCardCode::all();
        foreach ($codes as $code) {
            $this->assertNull($code->hero_id, 'Neu generierter Code darf keinem Helden zugewiesen sein');
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/', $code->code);
        }
    }

    public function test_generate_validiert_count_maximum(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.id-cards.generate'), ['count' => 201])
            ->assertSessionHasErrors('count');
    }

    public function test_generate_validiert_count_minimum(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.id-cards.generate'), ['count' => 0])
            ->assertSessionHasErrors('count');
    }

    // --- Code zuweisen ---

    public function test_assign_setzt_public_code_am_helden(): void
    {
        $hero = $this->heroWithoutCode();

        $this->actingAs($this->adminUser())
            ->patch(route('heroes.assign-code', $hero), ['code' => 'ABCDEF'])
            ->assertRedirect(route('heroes.show', $hero));

        $this->assertDatabaseHas('heroes', ['id' => $hero->id, 'public_code' => 'ABCDEF']);
    }

    public function test_assign_legt_pool_eintrag_an(): void
    {
        $hero = $this->heroWithoutCode();

        $this->actingAs($this->adminUser())
            ->patch(route('heroes.assign-code', $hero), ['code' => 'HJKMNP']);

        $this->assertDatabaseHas('id_card_codes', [
            'code'    => 'HJKMNP',
            'hero_id' => $hero->id,
        ]);
        $entry = IdCardCode::where('code', 'HJKMNP')->first();
        $this->assertNotNull($entry->assigned_at);
    }

    public function test_assign_akzeptiert_bestehenden_pool_code(): void
    {
        $hero = $this->heroWithoutCode();
        $admin = $this->adminUser();
        IdCardCode::create(['code' => 'QRSTUV', 'created_by' => $admin->id]);

        $this->actingAs($admin)
            ->patch(route('heroes.assign-code', $hero), ['code' => 'QRSTUV'])
            ->assertRedirect(route('heroes.show', $hero));

        $this->assertDatabaseHas('id_card_codes', ['code' => 'QRSTUV', 'hero_id' => $hero->id]);
    }

    public function test_assign_blockiert_code_der_anderem_helden_zugewiesen_ist(): void
    {
        $hero1 = $this->heroWithCode('WXYZ23');
        $hero2 = $this->heroWithoutCode();

        $this->actingAs($this->adminUser())
            ->patch(route('heroes.assign-code', $hero2), ['code' => 'WXYZ23'])
            ->assertSessionHasErrors('code');

        // Code von hero2 darf nicht auf 'WXYZ23' geändert worden sein
        $this->assertNotSame('WXYZ23', $hero2->fresh()->public_code);
    }

    public function test_assign_validiert_ungueltige_zeichen(): void
    {
        $hero = $this->heroWithoutCode();

        // Enthält '0' und 'I' – beide nicht im Base31-Alphabet
        $this->actingAs($this->adminUser())
            ->patch(route('heroes.assign-code', $hero), ['code' => 'AB0I23'])
            ->assertSessionHasErrors('code');
    }

    public function test_assign_validiert_zu_kurzen_code(): void
    {
        $hero = $this->heroWithoutCode();

        $this->actingAs($this->adminUser())
            ->patch(route('heroes.assign-code', $hero), ['code' => 'ABCD'])
            ->assertSessionHasErrors('code');
    }

    public function test_assign_ajax_gibt_json_mit_refresh_modal_zurueck(): void
    {
        $hero = $this->heroWithoutCode();

        $this->actingAs($this->adminUser())
            ->patchJson(route('heroes.assign-code', $hero), ['code' => 'ABCDEF'])
            ->assertOk()
            ->assertJsonFragment(['refresh_modal' => true]);

        $this->assertDatabaseHas('heroes', ['id' => $hero->id, 'public_code' => 'ABCDEF']);
    }

    public function test_assign_ajax_gibt_422_bei_bereits_vergebenem_code(): void
    {
        $hero1 = $this->heroWithCode('WXYZ23');
        $hero2 = $this->heroWithoutCode();

        $this->actingAs($this->adminUser())
            ->patchJson(route('heroes.assign-code', $hero2), ['code' => 'WXYZ23'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['code']]);

        $this->assertNotSame('WXYZ23', $hero2->fresh()->public_code);
    }

    public function test_teilnehmer_kann_keinen_code_zuweisen(): void
    {
        $hero = $this->heroWithoutCode();

        $this->actingAs($this->teilnehmer())
            ->patch(route('heroes.assign-code', $hero), ['code' => 'ABCDEF'])
            ->assertForbidden();
    }

    // --- Ausweis nachdrucken ---

    public function test_reprint_gibt_pdf_zurueck(): void
    {
        $hero = $this->heroWithCode('ABCDEF');

        $this->actingAs($this->adminUser())
            ->get(route('admin.id-cards.reprint', $hero))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_reprint_404_wenn_kein_code(): void
    {
        $hero = $this->heroWithoutCode();

        $this->actingAs($this->adminUser())
            ->get(route('admin.id-cards.reprint', $hero))
            ->assertNotFound();
    }

    // --- Index zeigt Pool-Daten ---

    public function test_index_zeigt_unzugewiesene_und_zugewiesene_codes(): void
    {
        $admin = $this->adminUser();
        $hero = $this->heroWithCode('ABCDEF');

        IdCardCode::create(['code' => 'UNSET2', 'created_by' => $admin->id]);
        IdCardCode::create(['code' => 'ABCDEF', 'hero_id' => $hero->id, 'assigned_at' => now(), 'created_by' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('admin.id-cards.index'))
            ->assertOk()
            ->assertSee('UNSET2')
            ->assertSee('ABCDEF');
    }

    // --- Nicht zugewiesene Siegel löschen ---

    public function test_destroy_loescht_nicht_zugewiesenes_siegel(): void
    {
        $admin = $this->adminUser();
        IdCardCode::create(['code' => 'DLTME2', 'created_by' => $admin->id]);

        $this->actingAs($admin)
            ->delete(route('admin.id-cards.destroy', 'DLTME2'))
            ->assertRedirect(route('admin.id-cards.index'));

        $this->assertDatabaseMissing('id_card_codes', ['code' => 'DLTME2']);
    }

    public function test_destroy_verweigert_zugewiesenes_siegel(): void
    {
        $admin = $this->adminUser();
        $hero  = $this->heroWithCode('NODLT3');
        IdCardCode::create(['code' => 'NODLT3', 'hero_id' => $hero->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)
            ->delete(route('admin.id-cards.destroy', 'NODLT3'))
            ->assertForbidden();

        $this->assertDatabaseHas('id_card_codes', ['code' => 'NODLT3']);
    }

    public function test_teilnehmer_kann_nicht_loeschen(): void
    {
        $admin = $this->adminUser();
        IdCardCode::create(['code' => 'NODLT4', 'created_by' => $admin->id]);

        $this->actingAs($this->teilnehmer())
            ->delete(route('admin.id-cards.destroy', 'NODLT4'))
            ->assertForbidden();

        $this->assertDatabaseHas('id_card_codes', ['code' => 'NODLT4']);
    }
}
