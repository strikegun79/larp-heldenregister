<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * QA-03: E2E-Tests für das AJAX-Modal-System und data-confirm.
 * Testet den kompletten Frontend-Pfad:
 *   [data-modal-url] klicken → Modal via fetch() laden → Formular absenden →
 *   JSON-Antwort → Toast anzeigen.
 *
 * Voraussetzung: `php artisan serve --env=dusk.local --port=8000` läuft.
 * Lokal starten: php artisan dusk --filter=ModalAjaxTest
 */
class ModalAjaxTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create([
            'name' => 'Dusk',
            'lastname' => 'Admin',
            'email' => 'dusk.admin@waldritter.de',
            'activated' => true,
        ]);
        $admin->roles()->attach(10); // admin

        return $admin;
    }

    /**
     * Klick auf eine Zeile mit [data-modal-url] öffnet das AJAX-Modal.
     * Verifies: fetch() liefert Partial, #app-modal wird sichtbar, Formular ist drin.
     */
    public function test_modal_trigger_oeffnet_ajax_modal(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create([
            'name' => 'Zielperson', 'lastname' => 'Dusk',
            'email' => 'ziel@waldritter.de', 'activated' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $target) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->assertSee('Zielperson')
                // Zeile anklicken (enthält data-modal-url="…/admin/users/{id}/edit")
                ->click("tr[data-modal-url*='/admin/users/{$target->id}/edit']")
                // Fomantic-UI fügt .active hinzu, wenn Modal sichtbar
                ->waitFor('#app-modal.active', 8)
                // Inhalt muss per AJAX geladen sein (Formular im Content-Bereich)
                ->assertPresent('#app-modal-content #user-edit-form')
                // Titel aus dem Partial muss im Header erscheinen
                ->assertSeeIn('#app-modal-header', 'Zielperson');
        });
    }

    /**
     * AJAX-Formular im Modal: absenden → Server antwortet mit JSON → Toast erscheint.
     * Verifies: JS-Submit-Handler, fetch() mit Accept:application/json, showToast().
     */
    public function test_ajax_modal_formular_submit_zeigt_toast(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create([
            'name' => 'Editierbar', 'lastname' => 'Dusk',
            'email' => 'edit@waldritter.de', 'activated' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $target) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->click("tr[data-modal-url*='/admin/users/{$target->id}/edit']")
                ->waitFor('#app-modal.active', 8)
                ->waitFor('#app-modal-actions .primary.button', 5)
                // "Speichern"-Button klicken (liegt in #app-modal-actions nach Extraktion)
                ->click('#app-modal-actions .primary.button')
                // Fomantic-UI-Toast erscheint im DOM
                ->waitFor('.ui.toast', 8)
                ->assertSeeIn('.ui.toast', 'aktualisiert');
        });
    }

    /**
     * data-confirm-Form: Submit öffnet Bestätigungs-Modal; "Abbrechen" verhindert Aktion.
     * Verifies: Capture-Phase-Submit-Handler, #confirm-modal, .deny.button.
     */
    public function test_data_confirm_zeigt_dialog_und_abbrechen_verhindert_loeschung(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create([
            'name' => 'NichtLoeschen', 'lastname' => 'Dusk',
            'email' => 'nodelete@waldritter.de', 'activated' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->assertSee('NichtLoeschen')
                // Löschen-Button klicken (hat data-confirm auf dem übergeordneten <form>)
                ->click("form[data-confirm*='NichtLoeschen'] [type=submit]")
                // Bestätigungs-Modal muss erscheinen
                ->waitFor('#confirm-modal.active', 5)
                ->assertSeeIn('#confirm-modal', 'löschen')
                // "Abbrechen" klicken — kein Submit
                ->click('#confirm-modal .deny.button')
                ->waitUntilMissing('#confirm-modal.active', 5)
                // Benutzer muss noch in der Liste sichtbar sein
                ->assertSee('NichtLoeschen');
        });
    }
}
