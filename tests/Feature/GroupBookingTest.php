<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Group;
use App\Models\Hero;
use App\Models\Player;
use App\Models\User;
use App\Notifications\BookingReceived;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * GRP-06: Gruppen-basierte Event-Buchung.
 */
class GroupBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
    }

    // ----------------------------------------------------------------
    // Hilfsmethoden
    // ----------------------------------------------------------------

    /** Normaler Nutzer mit adventure.book, aber kein book-any-player. */
    private function bookingUser(): User
    {
        $user = User::factory()->create([
            'activated' => true,
            'name' => 'Hans', 'lastname' => 'Muster', 'phone' => '0123456',
            'street' => 'Hauptstr.', 'house_number' => '1', 'zip' => '12345', 'city' => 'Wald',
        ]);
        $user->roles()->attach(60); // event_booking

        return $user;
    }

    /** Bürokrat mit book-any-player. */
    private function registrar(): User
    {
        $user = User::factory()->create(['activated' => true,
            'name' => 'Berta', 'lastname' => 'Schreiber', 'phone' => '0987654',
            'street' => 'Amtsstr.', 'house_number' => '2', 'zip' => '99999', 'city' => 'Burg',
        ]);
        $user->roles()->attach(20); // registrar

        return $user;
    }

    /** Erstellt einen Spieler mit Held, verknüpft ihn mit dem User und fügt ihn der Gruppe hinzu. */
    private function createMember(User $user, Group $group): Player
    {
        $player = Player::factory()->create();
        $user->players()->attach($player->id);
        $hero = Hero::factory()->create(['player_id' => $player->id]);
        $player->update(['active_hero_id' => $hero->id]);
        $group->heroes()->attach($hero->id);

        return $player;
    }

    /** Adventure mit offenem Anmeldestatus. */
    private function openAdventure(int $maxPlayer = 10): Adventure
    {
        return Adventure::factory()->create(['max_player' => $maxPlayer]);
    }

    // ----------------------------------------------------------------
    // Auth-Schutz
    // ----------------------------------------------------------------

    public function test_create_ohne_login_wird_umgeleitet(): void
    {
        $adventure = $this->openAdventure();

        $this->getJson(route('adventures.group-bookings.create', $adventure))
            ->assertUnauthorized();
    }

    public function test_store_ohne_login_wird_umgeleitet(): void
    {
        $adventure = $this->openAdventure();

        $this->postJson(route('adventures.group-bookings.store', $adventure), [])
            ->assertUnauthorized();
    }

    public function test_teilnehmer_ohne_buchungsrecht_kann_nicht_zugreifen(): void
    {
        $user = User::factory()->create(['activated' => true]);
        $user->roles()->attach(70); // participant – hat kein adventure.book
        $adventure = $this->openAdventure();

        $this->actingAs($user)
            ->getJson(route('adventures.group-bookings.create', $adventure))
            ->assertForbidden();
    }

    // ----------------------------------------------------------------
    // Create: Anzeige der Gruppen-Mitglieder
    // ----------------------------------------------------------------

    public function test_create_zeigt_eigene_gruppe_mit_mitgliedern(): void
    {
        $user = $this->bookingUser();
        $group = Group::factory()->create(['name' => 'Waldritter']);
        $this->createMember($user, $group);
        $adventure = $this->openAdventure();

        $this->actingAs($user)
            ->get(route('adventures.group-bookings.create', $adventure))
            ->assertOk()
            ->assertViewIs('bookings._create_group')
            ->assertViewHas('groups', fn ($groups) => $groups->contains('name', 'Waldritter'));
    }

    public function test_create_zeigt_keine_gruppen_ohne_mitgliedschaft(): void
    {
        $user = $this->bookingUser();
        Group::factory()->create(['name' => 'FremdeGruppe']); // user ist kein Mitglied
        $adventure = $this->openAdventure();

        $response = $this->actingAs($user)
            ->get(route('adventures.group-bookings.create', $adventure));

        $response->assertOk();
        $this->assertTrue($response->viewData('groups')->isEmpty());
    }

    public function test_create_blendet_bereits_angemeldete_mitglieder_aus(): void
    {
        $user = $this->bookingUser();
        $group = Group::factory()->create();
        $player = $this->createMember($user, $group);
        $adventure = $this->openAdventure();

        // Spieler bereits angemeldet
        Booking::factory()->create(['player_id' => $player->id, 'adventure_id' => $adventure->id]);

        $response = $this->actingAs($user)
            ->get(route('adventures.group-bookings.create', $adventure));

        $response->assertOk();
        // Gruppe hat keine buchbaren Mitglieder mehr → leer gefiltert
        $this->assertTrue($response->viewData('groups')->isEmpty());
    }

    public function test_registrar_sieht_alle_gruppen(): void
    {
        $registrar = $this->registrar();
        $group = Group::factory()->create(['name' => 'BeliebigGruppe']);
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);
        $group->heroes()->attach($hero->id);
        $adventure = $this->openAdventure();

        $this->actingAs($registrar)
            ->get(route('adventures.group-bookings.create', $adventure))
            ->assertOk()
            ->assertViewHas('groups', fn ($groups) => $groups->contains('name', 'BeliebigGruppe'));
    }

    // ----------------------------------------------------------------
    // Store: Einzelbuchungen erstellen
    // ----------------------------------------------------------------

    public function test_store_erstellt_buchungen_fuer_ausgewaehlte_mitglieder(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $group = Group::factory()->create();
        $player1 = $this->createMember($user, $group);
        $player2 = $this->createMember($user, $group);
        $adventure = $this->openAdventure();

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player1->id, $player2->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertOk()->assertJsonFragment(['refresh_modal' => true]);

        $this->assertDatabaseHas('bookings', ['player_id' => $player1->id, 'adventure_id' => $adventure->id]);
        $this->assertDatabaseHas('bookings', ['player_id' => $player2->id, 'adventure_id' => $adventure->id]);
    }

    public function test_store_nur_ausgewaehlte_mitglieder_werden_gebucht(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $group = Group::factory()->create();
        $player1 = $this->createMember($user, $group);
        $player2 = $this->createMember($user, $group);
        $adventure = $this->openAdventure();

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player1->id], // nur player1
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertOk();

        $this->assertDatabaseHas('bookings', ['player_id' => $player1->id, 'adventure_id' => $adventure->id]);
        $this->assertDatabaseMissing('bookings', ['player_id' => $player2->id, 'adventure_id' => $adventure->id]);
    }

    // ----------------------------------------------------------------
    // Kapazität und Warteliste
    // ----------------------------------------------------------------

    public function test_erste_buchung_normal_zweite_auf_warteliste_wenn_voll(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $group = Group::factory()->create();
        $player1 = $this->createMember($user, $group);
        $player2 = $this->createMember($user, $group);
        // Adventure hat nur 1 Platz
        $adventure = $this->openAdventure(maxPlayer: 1);

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player1->id, $player2->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertOk();

        $booking1 = Booking::where(['player_id' => $player1->id, 'adventure_id' => $adventure->id])->first();
        $booking2 = Booking::where(['player_id' => $player2->id, 'adventure_id' => $adventure->id])->first();

        $this->assertNotNull($booking1);
        $this->assertNotNull($booking2);
        // Genau eine Buchung muss auf der Warteliste sein
        $waitlisted = collect([$booking1->waitlisted, $booking2->waitlisted]);
        $this->assertTrue($waitlisted->contains(false));
        $this->assertTrue($waitlisted->contains(true));
    }

    // ----------------------------------------------------------------
    // Bereits angemeldete Spieler überspringen
    // ----------------------------------------------------------------

    public function test_bereits_angemeldeter_spieler_wird_uebersprungen(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $group = Group::factory()->create();
        $player1 = $this->createMember($user, $group);
        $player2 = $this->createMember($user, $group);
        $adventure = $this->openAdventure();

        // player1 vorab anmelden
        Booking::factory()->create(['player_id' => $player1->id, 'adventure_id' => $adventure->id]);

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player1->id, $player2->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertOk();

        // player1 darf keine zweite Buchung haben
        $this->assertCount(1, Booking::where(['player_id' => $player1->id, 'adventure_id' => $adventure->id])->get());
        // player2 ist neu angemeldet
        $this->assertDatabaseHas('bookings', ['player_id' => $player2->id, 'adventure_id' => $adventure->id]);
    }

    public function test_alle_spieler_bereits_angemeldet_gibt_422(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $group = Group::factory()->create();
        $player = $this->createMember($user, $group);
        $adventure = $this->openAdventure();
        Booking::factory()->create(['player_id' => $player->id, 'adventure_id' => $adventure->id]);

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertStatus(422);
    }

    // ----------------------------------------------------------------
    // Berechtigungsprüfung: keine fremden Spieler
    // ----------------------------------------------------------------

    public function test_normaler_nutzer_kann_nicht_fremde_spieler_buchen(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $fremderPlayer = Player::factory()->create(); // nicht dem user zugeordnet
        $adventure = $this->openAdventure();

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$fremderPlayer->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertStatus(422); // alle übersprungen → 0 erstellt → 422

        $this->assertDatabaseMissing('bookings', ['player_id' => $fremderPlayer->id]);
    }

    public function test_registrar_kann_beliebige_spieler_buchen(): void
    {
        Notification::fake();
        $registrar = $this->registrar();
        $fremderPlayer = Player::factory()->create();
        $adventure = $this->openAdventure();

        $this->actingAs($registrar)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$fremderPlayer->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertOk();

        $this->assertDatabaseHas('bookings', ['player_id' => $fremderPlayer->id, 'adventure_id' => $adventure->id]);
    }

    // ----------------------------------------------------------------
    // Geschlossene Anmeldung
    // ----------------------------------------------------------------

    public function test_store_bei_geschlossener_anmeldung_gibt_422(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $player = Player::factory()->create();
        $user->players()->attach($player->id);
        $adventure = Adventure::factory()->create(['event_status_id' => EventStatus::CANCELLED, 'max_player' => 10]);

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertStatus(422);
    }

    // ----------------------------------------------------------------
    // Benachrichtigungen
    // ----------------------------------------------------------------

    public function test_benachrichtigung_wird_versendet(): void
    {
        Notification::fake();
        $user = $this->bookingUser();
        $group = Group::factory()->create();
        $player = $this->createMember($user, $group);
        $player->update(['email' => 'held@example.com']);
        $adventure = $this->openAdventure();

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player->id],
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertOk();

        Notification::assertSentOnDemand(BookingReceived::class);
    }

    // ----------------------------------------------------------------
    // Validierung
    // ----------------------------------------------------------------

    public function test_store_ohne_player_ids_gibt_validierungsfehler(): void
    {
        $user = $this->bookingUser();
        $adventure = $this->openAdventure();

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'event_role_id' => 1,
            'agb' => '1',
            'kontakt_telefon' => '01234',
        ])->assertStatus(422)->assertJsonValidationErrors(['player_ids']);
    }

    public function test_store_ohne_agb_gibt_validierungsfehler(): void
    {
        $user = $this->bookingUser();
        $player = Player::factory()->create();
        $user->players()->attach($player->id);
        $adventure = $this->openAdventure();

        $this->actingAs($user)->postJson(route('adventures.group-bookings.store', $adventure), [
            'player_ids' => [$player->id],
            'event_role_id' => 1,
            'kontakt_telefon' => '01234',
            // agb fehlt
        ])->assertStatus(422)->assertJsonValidationErrors(['agb']);
    }
}
