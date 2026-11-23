<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Issue #19 (T2): Profilbereich „Teamer“ mit Steuernummer und Bankdaten
 * (verschlüsselt, nur für Teamer/Lehrmeister/Projektleitung).
 */
class TeamerBillingProfileTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'DE89 3704 0044 0532 0130 00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create([
            'street' => 'Waldweg', 'house_number' => '1', 'zip' => '35390', 'city' => 'Gießen',
        ]);
        $user->roles()->attach($roleId);

        return $user;
    }

    /** @return array<string, string> */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'teamer_tax_number' => '020/250/63440',
            'teamer_account_holder' => 'Erika Muster',
            'teamer_iban' => self::IBAN,
            'teamer_bank_name' => 'Volksbank',
        ], $extra);
    }

    public function test_section_only_for_teamer_roles(): void
    {
        foreach ([50, 45, 30] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))->get(route('profile.edit'))
                ->assertOk()->assertSee('id="teamer-billing-title"', false);
        }

        $this->actingAs($this->userWithRole(60))->get(route('profile.edit'))
            ->assertOk()->assertDontSee('id="teamer-billing-title"', false);

        $this->actingAs($this->userWithRole(60))
            ->patch(route('profile.teamer.update'), $this->payload())
            ->assertForbidden();
    }

    public function test_teamer_saves_data_encrypted_and_iban_normalized(): void
    {
        $user = $this->userWithRole(50);

        $this->actingAs($user)->patch(route('profile.teamer.update'), $this->payload())
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('DE89370400440532013000', $user->teamer_iban);
        $this->assertSame('020/250/63440', $user->teamer_tax_number);
        $this->assertSame([], $user->missingTeamerBillingFields());

        // In der Datenbank nur verschlüsselt
        $raw = DB::table('users')->where('id', $user->id)->first();
        $this->assertStringNotContainsString('DE89', $raw->teamer_iban);
        $this->assertStringNotContainsString('Volksbank', $raw->teamer_bank_name);

        // Nicht in Serialisierungen
        $this->assertArrayNotHasKey('teamer_iban', $user->toArray());

        // Formular zeigt die IBAN formatiert
        $this->actingAs($user)->get(route('profile.edit'))->assertSee(self::IBAN);
    }

    public function test_invalid_iban_and_tax_number_are_rejected(): void
    {
        $user = $this->userWithRole(50);

        $this->actingAs($user)
            ->patch(route('profile.teamer.update'), $this->payload([
                'teamer_iban' => 'DE89 3704 0044 0532 0130 01',
                'teamer_tax_number' => 'abc',
            ]))
            ->assertSessionHasErrorsIn('teamerBilling', ['teamer_iban', 'teamer_tax_number']);

        $this->assertNull($user->fresh()->teamer_iban);
    }

    public function test_saving_teamer_data_keeps_notification_settings(): void
    {
        $user = $this->userWithRole(50);
        $user->forceFill(['notify_event_reminder' => false, 'teamer_notifications' => true])->save();

        $this->actingAs($user)->patch(route('profile.teamer.update'), $this->payload())->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->notify_event_reminder);
        $this->assertTrue($user->teamer_notifications);
    }

    public function test_missing_fields_are_listed(): void
    {
        $user = $this->userWithRole(50);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertSee('Für die Abrechnung fehlt noch: Steuernummer, Kontoinhaber, IBAN, Name der Bank.');
    }

    public function test_export_contains_and_anonymize_clears_teamer_data(): void
    {
        $user = $this->userWithRole(50);
        $user->forceFill(['teamer_iban' => 'DE89370400440532013000', 'teamer_bank_name' => 'Volksbank'])->save();

        $this->actingAs($user)->get(route('profile.export-data'))
            ->assertOk()
            ->assertSee('DE89370400440532013000');

        $user->anonymize();
        $user = User::withTrashed()->find($user->id);
        $this->assertNull($user->teamer_iban);
        $this->assertNull($user->teamer_bank_name);
    }
}
