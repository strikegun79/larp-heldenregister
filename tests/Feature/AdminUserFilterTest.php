<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nutzerverwaltung (/admin/users): Filter nach Rolle, automatische Suche,
 * gelöschte Konten nur auf Wunsch.
 */
class AdminUserFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = $this->person('Adalbert', [10]);
    }

    /** @param  list<int>  $roleIds */
    private function person(string $name, array $roleIds = [], array $attributes = []): User
    {
        $user = User::factory()->create(['name' => $name, 'email_verified_at' => now()] + $attributes);
        $user->roles()->attach($roleIds);

        return $user;
    }

    /** @return list<string> Namen der gelisteten Nutzer */
    private function listed(array $query = []): array
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.users.index', $query))
            ->assertOk()
            ->viewData('users')
            ->pluck('name')
            ->all();
    }

    public function test_formular_hat_rollenfilter_checkbox_und_auto_filter(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-auto-filter="#user-results"', false)
            ->assertSee('id="user-results"', false)
            ->assertSee('<option value="">Alle Rollen</option>', false)
            ->assertSee('<option value="20" >Bürokrat</option>', false)
            ->assertSee('<option value="none" >Ohne Rolle</option>', false)
            ->assertDontSee('<option value="0"', false)
            ->assertSee('Gelöschte Nutzer anzeigen')
            ->assertSee('1 Nutzer gefunden');
    }

    public function test_filter_nach_rolle_und_ohne_rolle(): void
    {
        $this->person('Berta', [20]);
        $this->person('Theo', [50]);
        $this->person('Tilda', [50, 20]);
        $this->person('Rollenlos');
        $this->person('Altkonto', [0]); // Rolle 0 „keine“ aus dem Altsystem

        $this->assertEqualsCanonicalizing(['Theo', 'Tilda'], $this->listed(['role' => 50]));
        $this->assertEqualsCanonicalizing(['Berta', 'Tilda'], $this->listed(['role' => 20]));
        $this->assertEqualsCanonicalizing(['Rollenlos', 'Altkonto'], $this->listed(['role' => 'none']));
        $this->assertCount(6, $this->listed());

        // Unbekannte Rolle und Rolle 0 werden ignoriert.
        $this->assertCount(6, $this->listed(['role' => 'quatsch']));
        $this->assertCount(6, $this->listed(['role' => '0']));
    }

    public function test_suche_und_rolle_kombiniert(): void
    {
        $this->person('Theo', [50], ['lastname' => 'Waldmann']);
        $this->person('Thea', [20], ['lastname' => 'Waldmann']);
        $this->person('Otto', [50], ['email' => 'otto@waldmann.test']);

        $this->assertEqualsCanonicalizing(['Theo', 'Otto'], $this->listed(['q' => 'waldmann', 'role' => 50]));
        $this->assertEqualsCanonicalizing(['Theo', 'Thea', 'Otto'], $this->listed(['q' => 'waldmann']));
    }

    public function test_geloeschte_nutzer_nur_mit_haekchen(): void
    {
        $this->person('Aktiv', [50]);
        $this->person('Weg', [50])->delete();

        $this->assertSame(['Aktiv'], $this->listed(['role' => 50]));
        $this->assertEqualsCanonicalizing(['Aktiv', 'Weg'], $this->listed(['role' => 50, 'deleted' => 1]));

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['deleted' => 1]))
            ->assertSee('id="user-deleted" name="deleted" value="1"', false)
            ->assertSee('Wiederherstellen');
    }

    public function test_filter_bleiben_beim_blaettern_erhalten(): void
    {
        foreach (range(1, 30) as $i) {
            $this->person(sprintf('Teamer %02d', $i), [50]);
        }

        $html = $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 50, 'q' => 'Teamer']))
            ->assertOk()
            ->assertSee('Filter zurücksetzen')
            ->getContent();

        $this->assertStringContainsString('role=50', $html);
        $this->assertStringContainsString('q=Teamer', $html);
        $this->assertStringContainsString('page=2', $html);
    }

    public function test_leeres_ergebnis_nennt_die_filter(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['q' => 'Niemand']))
            ->assertOk()
            ->assertSee('Keine Nutzer für diese Filter gefunden.')
            ->assertSee('0 Nutzer gefunden');
    }
}
