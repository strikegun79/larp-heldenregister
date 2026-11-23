<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * QA-03: E2E-Tests für das AJAX-Nachladen (Inspektor) und data-confirm.
 * Testet den kompletten Frontend-Pfad:
 *   [data-inspector-url] klicken → Inspektor via fetch() laden → Formular absenden →
 *   JSON-Antwort → Toast anzeigen.
 * Die Admin-Listen öffnen Datensätze seit dem Waldritter-Umbau im Inspektor statt im Modal.
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
     * Klick auf eine Zeile mit [data-inspector-url] öffnet den Inspektor.
     * Verifies: fetch() liefert Partial, #booking-inspector wird sichtbar, Formular ist drin.
     */
    public function test_inspector_trigger_oeffnet_ajax_inspektor(): void
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
                // Zeile anklicken (enthält data-inspector-url="…/admin/users/{id}/edit?inspector=1")
                ->click("tr[data-inspector-url*='/admin/users/{$target->id}/edit']")
                // Inspektor bekommt .is-open, sobald er eingeblendet ist
                ->waitFor('#booking-inspector.is-open', 8)
                // Inhalt muss per AJAX geladen sein (Formular im Content-Bereich)
                ->waitFor('#booking-inspector-content #user-edit-form', 8)
                // Titel aus dem Partial muss im Kopf erscheinen
                ->assertSeeIn('#booking-inspector-title', 'Zielperson');
        });
    }

    /**
     * AJAX-Formular im Inspektor: absenden → Server antwortet mit JSON → Toast erscheint.
     * Verifies: JS-Submit-Handler (data-inspector-form), fetch() mit Accept:application/json, showToast().
     */
    public function test_ajax_inspektor_formular_submit_zeigt_toast(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create([
            'name' => 'Editierbar', 'lastname' => 'Dusk',
            'email' => 'edit@waldritter.de', 'activated' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $target) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->click("tr[data-inspector-url*='/admin/users/{$target->id}/edit']")
                ->waitFor('#booking-inspector.is-open', 8)
                // "Speichern" liegt nach der Anpassung in der Aktionsleiste des Inspektors
                ->waitFor('#booking-inspector-content .wr-inspector-actions .primary.button', 8)
                ->click('#booking-inspector-content .wr-inspector-actions .primary.button')
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

        $this->browse(function (Browser $browser) use ($admin, $target) {
            $browser->loginAs($admin)
                // Der Löschen-Button sitzt auf der Profilseite des Benutzers (nicht mehr in der Liste)
                ->visit("/admin/users/{$target->id}/profile")
                ->assertSee('NichtLoeschen')
                // Löschen-Button klicken (hat data-confirm auf dem übergeordneten <form>)
                ->click("form[data-confirm*='NichtLoeschen'] [type=submit]")
                // Bestätigungs-Modal muss erscheinen
                ->waitFor('#confirm-modal.active', 5)
                ->assertSeeIn('#confirm-modal', 'löschen')
                // "Abbrechen" klicken — kein Submit
                ->click('#confirm-modal .deny.button')
                ->waitUntilMissing('#confirm-modal.active', 5)
                // Seite bleibt stehen, Benutzer ist nicht gelöscht
                ->assertSee('Konto löschen');
        });

        $this->assertNotSoftDeleted($target);
    }
}
