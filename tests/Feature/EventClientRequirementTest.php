<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\EventClientRequirement;
use App\Models\EventRole;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Issue #14: Zusätzliche Anforderungen (Einverständniserklärungen) je
 * Auftraggeber als Pflichtfelder bei der Anmeldung.
 */
class EventClientRequirementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
        Storage::fake('local');
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    private function openAdventure(int $clientId = 1): Adventure
    {
        return Adventure::factory()->create([
            'event_status_id' => EventStatus::REGISTRATION_OPEN,
            'event_client_id' => $clientId,
            'max_player' => 30,
        ]);
    }

    private function requirement(array $attributes = []): EventClientRequirement
    {
        return EventClientRequirement::create($attributes + [
            'event_client_id' => 1,
            'title' => 'Einverständniserklärung Testverein',
            'description' => '<p><strong>Betreten des Waldes</strong> auf eigenes Risiko.</p>',
            'pdf_path' => UploadedFile::fake()->create('erklaerung.pdf', 10, 'application/pdf')
                ->store('event-client-requirements', 'local'),
            'pdf_name' => 'erklaerung.pdf',
            'active' => true,
        ]);
    }

    /** Buchender Nutzer mit betreutem Spieler. */
    private function bookerWithPlayer(): array
    {
        $user = $this->userWithRole(60);
        $player = Player::factory()->create();
        $user->players()->attach($player->id);

        return [$user, $player];
    }

    private function payload(int $playerId, array $extra = []): array
    {
        return $extra + [
            'player_id' => $playerId,
            'event_role_id' => EventRole::forParticipant()->orderBy('id')->first()->id,
            'agb' => '1',
            'kontakt_telefon' => '+49 123 456789',
        ];
    }

    // ── Anmeldung ───────────────────────────────────────────────────────────

    public function test_booking_requires_active_requirement(): void
    {
        $requirement = $this->requirement();
        [$user, $player] = $this->bookerWithPlayer();

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store', $this->openAdventure()), $this->payload($player->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors("requirements.{$requirement->id}");

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_records_consent(): void
    {
        $requirement = $this->requirement();
        [$user, $player] = $this->bookerWithPlayer();
        $adventure = $this->openAdventure();

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store', $adventure), $this->payload($player->id, [
                'requirements' => [$requirement->id => '1'],
            ]))
            ->assertOk();

        $booking = $adventure->bookings()->firstOrFail();
        $this->assertDatabaseHas('booking_requirement_consents', [
            'booking_id' => $booking->id,
            'event_client_requirement_id' => $requirement->id,
        ]);
        $this->assertNotNull($booking->requirementConsents->first()->pivot->accepted_at);
    }

    public function test_requirements_of_other_clients_and_inactive_ones_are_not_required(): void
    {
        $this->requirement(['event_client_id' => 2]);
        $this->requirement(['active' => false]);
        [$user, $player] = $this->bookerWithPlayer();

        $this->actingAs($user)
            ->postJson(route('adventures.bookings.store', $this->openAdventure(1)), $this->payload($player->id))
            ->assertOk();

        $this->assertDatabaseCount('booking_requirement_consents', 0);
    }

    public function test_guest_booking_requires_requirement(): void
    {
        $requirement = $this->requirement();
        $adventure = $this->openAdventure();
        $guest = [
            'guest_name' => 'Gerd', 'guest_lastname' => 'Gast', 'event_role_id' => 1,
            'agb' => '1', 'kontakt_telefon' => '+49 123 456789',
        ];

        $this->actingAs($this->userWithRole(60))
            ->postJson(route('adventures.bookings.store-guest', $adventure), $guest)
            ->assertStatus(422)
            ->assertJsonValidationErrors("requirements.{$requirement->id}");

        $this->actingAs($this->userWithRole(60))
            ->postJson(route('adventures.bookings.store-guest', $adventure), $guest + [
                'requirements' => [$requirement->id => '1'],
            ])
            ->assertOk();

        $this->assertDatabaseCount('booking_requirement_consents', 1);
    }

    public function test_booking_form_shows_requirement_checkbox(): void
    {
        $requirement = $this->requirement();
        [$user] = $this->bookerWithPlayer();

        $this->actingAs($user)
            ->get(route('adventures.bookings.create', $this->openAdventure()))
            ->assertOk()
            ->assertSee("requirements[{$requirement->id}]", false)
            ->assertSee('Einverständniserklärung Testverein')
            // Formatierte Beschreibung für das Popup und PDF-Symbol hinter dem Label.
            ->assertSee('<strong>Betreten des Waldes</strong>', false)
            ->assertSee('file pdf outline icon', false)
            ->assertSee(route('requirements.pdf', $requirement), false);
    }

    public function test_booking_form_without_pdf_shows_no_pdf_icon(): void
    {
        $requirement = $this->requirement(['pdf_path' => null, 'pdf_name' => null]);
        [$user] = $this->bookerWithPlayer();

        $this->actingAs($user)
            ->get(route('adventures.bookings.create', $this->openAdventure()))
            ->assertOk()
            ->assertSee("requirements[{$requirement->id}]", false)
            ->assertDontSee('file pdf outline icon', false)
            ->assertDontSee(route('requirements.pdf', $requirement), false);
    }

    // ── PDF-Download ────────────────────────────────────────────────────────

    public function test_pdf_download_requires_login(): void
    {
        $requirement = $this->requirement();

        $this->get(route('requirements.pdf', $requirement))->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole(60))
            ->get(route('requirements.pdf', $requirement))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    // ── Verwaltung ──────────────────────────────────────────────────────────

    public function test_admin_can_create_requirement_with_pdf(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->postJson(route('admin.event-clients.requirements.store', 1), [
                'title' => 'Einverständnis Jufö',
                'description' => '<p>Hinweise zum Wald</p>',
                'pdf' => UploadedFile::fake()->create('jufoe.pdf', 50, 'application/pdf'),
                'active' => '1',
            ])
            ->assertOk();

        $requirement = EventClientRequirement::firstOrFail();
        $this->assertSame('jufoe.pdf', $requirement->pdf_name);
        $this->assertTrue($requirement->active);
        Storage::disk('local')->assertExists($requirement->pdf_path);
    }

    public function test_admin_can_create_requirement_without_pdf(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->postJson(route('admin.event-clients.requirements.store', 1), [
                'title' => 'Ohne PDF',
                'description' => '<p>Text <strong>fett</strong></p>',
                'active' => '1',
            ])
            ->assertOk();

        $this->assertNull(EventClientRequirement::firstOrFail()->pdf_path);
    }

    public function test_description_is_required(): void
    {
        foreach (['', '<p><br></p>'] as $empty) {
            $this->actingAs($this->userWithRole(10))
                ->postJson(route('admin.event-clients.requirements.store', 1), ['title' => 'Leer', 'description' => $empty])
                ->assertStatus(422)
                ->assertJsonValidationErrors('description');
        }
    }

    public function test_description_is_sanitized(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->postJson(route('admin.event-clients.requirements.store', 1), [
                'title' => 'XSS',
                'description' => '<p onclick="alert(1)"><strong>Wald</strong></p><script>alert(1)</script>',
            ])
            ->assertOk();

        $description = EventClientRequirement::firstOrFail()->description;
        $this->assertStringContainsString('<strong>Wald</strong>', $description);
        $this->assertStringNotContainsString('script', $description);
        $this->assertStringNotContainsString('onclick', $description);
    }

    public function test_only_pdf_files_are_allowed(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->postJson(route('admin.event-clients.requirements.store', 1), [
                'title' => 'Bild',
                'description' => '<p>Text</p>',
                'pdf' => UploadedFile::fake()->create('bild.png', 10, 'image/png'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pdf');
    }

    public function test_admin_can_remove_pdf(): void
    {
        $requirement = $this->requirement();
        $path = $requirement->pdf_path;

        $this->actingAs($this->userWithRole(10))
            ->putJson(route('admin.event-client-requirements.update', $requirement), [
                'title' => $requirement->title,
                'description' => $requirement->description,
                'remove_pdf' => '1',
                'active' => '1',
            ])
            ->assertOk();

        $this->assertNull($requirement->fresh()->pdf_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_regular_user_cannot_manage_requirements(): void
    {
        $this->actingAs($this->userWithRole(60))
            ->postJson(route('admin.event-clients.requirements.store', 1), ['title' => 'X'])
            ->assertForbidden();
    }

    public function test_requirement_with_consents_is_only_deactivated(): void
    {
        $requirement = $this->requirement();
        [$user, $player] = $this->bookerWithPlayer();
        $this->actingAs($user)->postJson(
            route('adventures.bookings.store', $this->openAdventure()),
            $this->payload($player->id, ['requirements' => [$requirement->id => '1']])
        )->assertOk();

        $this->actingAs($this->userWithRole(10))
            ->deleteJson(route('admin.event-client-requirements.destroy', $requirement))
            ->assertOk();

        $this->assertDatabaseHas('event_client_requirements', ['id' => $requirement->id, 'active' => false]);
        Storage::disk('local')->assertExists($requirement->pdf_path);
    }

    public function test_unused_requirement_is_deleted(): void
    {
        $requirement = $this->requirement();

        $this->actingAs($this->userWithRole(10))
            ->deleteJson(route('admin.event-client-requirements.destroy', $requirement))
            ->assertOk();

        $this->assertDatabaseMissing('event_client_requirements', ['id' => $requirement->id]);
        Storage::disk('local')->assertMissing($requirement->pdf_path);
    }
}
