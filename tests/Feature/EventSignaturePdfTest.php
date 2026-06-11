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
 * ADV-17: Unterschrift bei Teilnahme erfassen und Teilnehmer-PDF exportieren.
 */
class EventSignaturePdfTest extends TestCase
{
    use RefreshDatabase;

    // Minimal gültiges 1x1-PNG als Data-URL.
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    private function bookingFor(Adventure $adventure): Booking
    {
        return Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create()->id,
        ]);
    }

    public function test_signature_pad_opens_for_authorized_role(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(30)) // Projektleitung
            ->get(route('adventures.bookings.signature.edit', [$adventure, $booking]))
            ->assertOk()
            ->assertSee('signature-pad', false);
    }

    public function test_authorized_role_can_save_signature(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(20)) // Bürokrat
            ->putJson(route('adventures.bookings.signature.update', [$adventure, $booking]), [
                'signature' => self::PNG,
            ])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $this->assertNotNull($booking->fresh()->signature);
    }

    public function test_signature_must_be_png_data_url(): void
    {
        $adventure = Adventure::factory()->registrationClosed()->create();
        $booking = $this->bookingFor($adventure);

        $this->actingAs($this->userWithRole(20))
            ->putJson(route('adventures.bookings.signature.update', [$adventure, $booking]), [
                'signature' => 'nicht-ein-bild',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('signature');
    }

    public function test_unauthorized_role_cannot_take_signatures(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);

        // Spielleiter hat manage-attendance, aber nicht take-signatures.
        $this->actingAs($this->userWithRole(40))
            ->get(route('adventures.bookings.signature.edit', [$adventure, $booking]))
            ->assertForbidden();
    }

    public function test_signature_must_belong_to_adventure(): void
    {
        $adventure = Adventure::factory()->create();
        $booking = $this->bookingFor($adventure);
        $other = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(20))
            ->putJson(route('adventures.bookings.signature.update', [$other, $booking]), [
                'signature' => self::PNG,
            ])
            ->assertNotFound();
    }

    public function test_participants_pdf_downloads(): void
    {
        $adventure = Adventure::factory()->create();
        Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create(['gender' => 'männlich'])->id,
        ]);
        Booking::factory()->for($adventure)->create([
            'player_id' => Player::factory()->create(['gender' => 'weiblich'])->id,
        ]);

        $response = $this->actingAs($this->userWithRole(30)) // Projektleitung
            ->get(route('adventures.participants-pdf', $adventure));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('teilnehmerliste-'.$adventure->id.'.pdf', $response->headers->get('content-disposition'));
    }

    public function test_participants_pdf_requires_authorization(): void
    {
        $adventure = Adventure::factory()->create();

        $this->actingAs($this->userWithRole(40)) // Spielleiter: keine take-signatures
            ->get(route('adventures.participants-pdf', $adventure))
            ->assertForbidden();
    }
}
