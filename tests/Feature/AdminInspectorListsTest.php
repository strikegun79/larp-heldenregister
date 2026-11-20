<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventRole;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\HeroClassSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PerlColorSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TaskTypeDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Admin-Listen (Stammdaten) öffnen Zeilen im Inspektor statt im Modal; Löschen steht
 * nur im Inspektor (?inspector=1) im Gefahrenbereich, mit Sperrgrund wie bisher.
 */
class AdminInspectorListsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class, EpTransactionTypeSeeder::class,
            HeroClassSeeder::class, PerlColorSeeder::class, TaskTypeDefinitionSeeder::class,
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10);

        return $user;
    }

    public static function lists(): array
    {
        return [
            'Helden-Klassen' => ['admin.hero-classes.index'],
            'Perlenfarben' => ['admin.perl-colors.index'],
            'EP-Buchungsarten' => ['admin.ep-transaction-types.index'],
            'Orte' => ['admin.locations.index'],
            'Kategorien' => ['admin.event-categories.index'],
            'Auftraggeber' => ['admin.event-clients.index'],
            'Teilnahme-Rollen' => ['admin.event-roles.index'],
            'Event-Status' => ['admin.event-statuses.index'],
            'Taskmanager' => ['admin.task-manager.index'],
        ];
    }

    #[DataProvider('lists')]
    public function test_rows_open_in_inspector_without_edit_links(string $route): void
    {
        $html = $this->actingAs($this->admin())->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString('?inspector=1" tabindex="0"', $html);
        $this->assertStringNotContainsString('>Bearbeiten</a>', $html);
    }

    public function test_delete_zone_only_in_inspector(): void
    {
        $location = Location::first();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.locations.edit', $location).'?inspector=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Ort löschen')
            ->assertSee('data-inspector-close-on-success', false);

        $this->actingAs($admin)
            ->get(route('admin.locations.edit', $location), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('Ort löschen');
    }

    public function test_blocked_delete_shows_reason_instead_of_button(): void
    {
        $role = EventRole::first();
        Booking::factory()->create(['event_role_id' => $role->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.event-roles.edit', $role).'?inspector=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Nicht löschbar: 1 Anmeldung(en) nutzen diese Rolle.')
            // Update und Destroy teilen sich die URL – daher auf das Lösch-Formular prüfen.
            ->assertDontSee('data-inspector-close-on-success', false);
    }
}
