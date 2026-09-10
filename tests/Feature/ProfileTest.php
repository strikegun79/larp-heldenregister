<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\Player;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'lastname' => 'Mustermann',
                'email' => 'test@example.com',
                'phone' => $user->phone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('Mustermann', $user->lastname);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_lastname_and_phone_can_be_updated(): void
    {
        $user = User::factory()->create(['lastname' => null, 'phone' => null]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'lastname' => 'Mustermann',
                'phone' => '+49 123 456789',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Mustermann', $user->lastname);
        $this->assertSame('+49 123 456789', $user->phone);
    }

    public function test_phone_is_required(): void
    {
        $user = User::factory()->create(['phone' => '123']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => '',
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_lastname_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'lastname' => '',
                'email' => $user->email,
                'phone' => $user->phone,
            ])
            ->assertSessionHasErrors('lastname');
    }

    public function test_phone_max_length_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => str_repeat('0', 51),
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        // User nutzt SoftDeletes (Legacy: portal_user.deleted), daher wird der
        // Datensatz als gelöscht markiert statt physisch entfernt.
        $this->assertSoftDeleted($user);
    }

    public function test_profile_page_shows_assigned_roles(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'participant')->first());

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Deine Rollen')
            ->assertSee('Teilnehmer');
    }

    public function test_profile_page_shows_fallback_when_no_roles_assigned(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Keine Rolle zugewiesen.');
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_daten_export_erfordert_authentifizierung(): void
    {
        $this->get(route('profile.export-data'))
            ->assertRedirect(route('login'));
    }

    public function test_daten_export_liefert_json_download(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('profile.export-data'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json; charset=utf-8');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.json', $response->headers->get('Content-Disposition'));
    }

    public function test_daten_export_enthaelt_konto_und_spieler(): void
    {
        $user = User::factory()->create(['name' => 'Max', 'lastname' => 'Mustermann']);
        $player = Player::factory()->create(['name' => 'Kind', 'lastname' => 'Muster']);
        $user->players()->attach($player);
        Hero::factory()->create(['player_id' => $player->id, 'character_name' => 'Aldric']);

        $response = $this->actingAs($user)
            ->get(route('profile.export-data'));

        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('export_erstellt_am', $json);
        $this->assertSame('Max', $json['konto']['vorname']);
        $this->assertSame('Mustermann', $json['konto']['nachname']);
        $this->assertCount(1, $json['spieler']);
        $this->assertSame('Kind', $json['spieler'][0]['vorname']);
        $this->assertCount(1, $json['spieler'][0]['helden']);
        $this->assertSame('Aldric', $json['spieler'][0]['helden'][0]['charaktername']);
        $this->assertArrayHasKey('ep_gesamt', $json['spieler'][0]['helden'][0]);
        $this->assertArrayHasKey('ep_verlauf', $json['spieler'][0]['helden'][0]);
    }

    public function test_daten_export_enthaelt_keine_unterschriften(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('profile.export-data'));

        $this->assertStringNotContainsString('signature', $response->getContent());
        $this->assertStringNotContainsString('unterschrift', strtolower($response->getContent()));
    }
}
