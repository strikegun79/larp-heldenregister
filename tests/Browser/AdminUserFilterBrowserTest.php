<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Nutzerverwaltung: Suche, Rollenfilter und „Gelöschte Nutzer anzeigen“ filtern automatisch,
 * ohne die Seite neu zu laden (data-auto-filter).
 */
class AdminUserFilterBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_suche_rolle_und_geloeschte_filtern_automatisch(): void
    {
        $admin = User::factory()->create(['name' => 'Dusk', 'lastname' => 'Admin', 'email' => 'dusk.admin@waldritter.de', 'activated' => true]);
        $admin->roles()->attach(10);
        User::factory()->create(['name' => 'Waltraud', 'activated' => true])->roles()->attach(50);
        User::factory()->create(['name' => 'Bernhard', 'activated' => true])->roles()->attach(20);
        $gone = User::factory()->create(['name' => 'Verschwunden', 'activated' => true]);
        $gone->roles()->attach(50);
        $gone->delete();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->assertSeeIn('#user-results', 'Bernhard')
                ->assertDontSeeIn('#user-results', 'Verschwunden')
                // Markierung: bleibt nur erhalten, wenn die Seite nicht neu geladen wird.
                ->script('window.__ohneNeuladen = true;');

            $browser->type('#user-search', 'walt')
                ->waitUntilMissingText('Bernhard', 5)
                ->assertSeeIn('#user-results', 'Waltraud')
                ->assertQueryStringHas('q', 'walt')
                ->assertFocused('#user-search');

            $browser->clear('#user-search')
                ->keys('#user-search', '{backspace}')
                ->select('#user-role', '50')
                ->waitUntil("location.search.includes('role=50')", 5)
                ->assertSeeIn('#user-results', 'Waltraud')
                ->assertDontSeeIn('#user-results', 'Bernhard')
                ->check('#user-deleted')
                ->waitForTextIn('#user-results', 'Verschwunden', 5)
                ->assertSeeIn('#user-results', 'Wiederherstellen')
                ->assertQueryStringHas('deleted', '1');

            $this->assertTrue($browser->script('return window.__ohneNeuladen === true;')[0]);
        });
    }
}
