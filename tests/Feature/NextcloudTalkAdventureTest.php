<?php

namespace Tests\Feature;

use App\Jobs\SetupAdventureTalkJob;
use App\Jobs\SyncAdventureTalkTeamersJob;
use App\Models\Adventure;
use App\Models\Setting;
use App\Models\TeamerSignup;
use App\Models\User;
use App\Services\NextcloudTalk\AdventureTalkService;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Issue #31 (Phase 2): Event-Reiter „Talk“ – Plot-Thread, Teamer-Raum und Teamer-Abgleich.
 * Ausschließlich simulierte Nextcloud (Http::fake), kein echter Aufruf.
 */
class NextcloudTalkAdventureTest extends TestCase
{
    use RefreshDatabase;

    /** Simulierter Zustand der Nextcloud. */
    private array $roomMembers = [];

    /** E-Mail → Nextcloud-Benutzernamen (mehrere = mehrdeutig). */
    private array $directory = [];

    private int $plotStatus = 201;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
        Http::preventStrayRequests();

        config([
            'nextcloud.url' => 'https://cloud.example.test',
            'nextcloud.user' => 'heldenregister',
            'nextcloud.app_password' => 'geheim',
        ]);
        Setting::set('talk_enabled', '1');
        Setting::set('talk_plot_room_token', 'plot42ab');

        $this->fakeNextcloud();
    }

    private function fakeNextcloud(): void
    {
        Http::fake(function (Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();
            $base = '/ocs/v2.php/apps/spreed/api/';

            if ($method === 'POST' && $path === $base.'v1/chat/plot42ab') {
                return $this->plotStatus === 201
                    ? Http::response(['ocs' => ['data' => ['id' => 500, 'threadId' => 500, 'isThread' => true]]], 201)
                    : Http::response(['ocs' => []], $this->plotStatus);
            }
            if ($method === 'PUT' && $path === $base.'v1/chat/plot42ab/threads/500') {
                return Http::response(['ocs' => ['data' => []]]);
            }
            if ($method === 'POST' && $path === $base.'v4/room') {
                $this->roomMembers = ['heldenregister' => 1];

                return Http::response(['ocs' => ['data' => ['token' => 'team77xy']]], 201);
            }
            if ($method === 'PUT' && $path === $base.'v4/room/team77xy') {
                return Http::response(['ocs' => ['data' => []]]);
            }
            if ($method === 'GET' && $path === $base.'v4/room/team77xy/participants') {
                $list = [];
                foreach ($this->roomMembers as $actorId => $attendeeId) {
                    $list[] = ['attendeeId' => $attendeeId, 'actorType' => 'users', 'actorId' => $actorId];
                }

                return Http::response(['ocs' => ['data' => $list]]);
            }
            if ($method === 'POST' && $path === $base.'v4/room/team77xy/participants') {
                $this->roomMembers[$request['newParticipant']] = count($this->roomMembers) + 10;

                return Http::response(['ocs' => ['data' => []]]);
            }
            if ($method === 'DELETE' && $path === $base.'v4/room/team77xy/attendees') {
                $this->roomMembers = array_filter($this->roomMembers, fn ($id) => $id !== (int) $request['attendeeId']);

                return Http::response(['ocs' => ['data' => []]]);
            }
            if ($method === 'GET' && $path === '/ocs/v2.php/cloud/users/details') {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $users = [];
                foreach ($this->directory[$query['search'] ?? ''] ?? [] as $id) {
                    $users[$id] = ['id' => $id, 'email' => $query['search']];
                }

                return Http::response(['ocs' => ['data' => ['users' => $users]]]);
            }

            return Http::response(['ocs' => []], 404);
        });
    }

    private function userWithRole(int $roleId, array $attributes = []): User
    {
        $user = User::factory()->create(['email_verified_at' => now()] + $attributes);
        $user->roles()->attach($roleId);

        return $user;
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Sommerlager',
            'kuerzel' => 'SoLa',
            'start_at' => '2026-08-01 10:00',
            'end_at' => '2026-08-02 10:00',
            'event_status_id' => 30,
            'event_client_id' => 1,
            'event_category_id' => 0,
            'max_player' => 10,
            'fee' => 12,
        ], $extra);
    }

    /** Event mit eingerichtetem Teamer-Raum. */
    private function adventureWithRoom(): Adventure
    {
        $adventure = Adventure::factory()->create(['name' => 'Herbstlager']);
        $adventure->talk_teamer_room = true;
        $adventure->talk_teamer_room_token = 'team77xy';
        $adventure->talk_teamer_room_name = 'Teamer: Herbstlager';
        $adventure->save();
        $this->roomMembers = ['heldenregister' => 1];

        return $adventure;
    }

    private function requests(string $method, string $pathEnd): int
    {
        return Http::recorded(fn (Request $r) => $r->method() === $method
            && str_ends_with((string) parse_url($r->url(), PHP_URL_PATH), $pathEnd))->count();
    }

    // ── Event anlegen ─────────────────────────────────────────────────

    public function test_anlegen_mit_beiden_optionen_erstellt_thread_und_raum(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('adventures.store'), $this->payload(['talk_plot_thread' => '1', 'talk_teamer_room' => '1']))
            ->assertRedirect();

        $adventure = Adventure::where('name', 'Sommerlager')->firstOrFail();
        $this->assertSame(500, $adventure->talk_plot_thread_id);
        $this->assertSame('Sommerlager', $adventure->talk_plot_thread_title);
        $this->assertSame('team77xy', $adventure->talk_teamer_room_token);
        $this->assertSame('Teamer: Sommerlager', $adventure->talk_teamer_room_name);
        $this->assertNull($adventure->talk_error);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/chat/plot42ab?format=json')
            && $r['threadTitle'] === 'Sommerlager'
            && str_contains($r['message'], '01.08.2026 – 02.08.2026'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v4/room?format=json')
            && $r['roomType'] === 2 && $r['roomName'] === 'Teamer: Sommerlager');
    }

    public function test_anlegen_ohne_haekchen_sendet_nichts(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->post(route('adventures.store'), $this->payload())
            ->assertRedirect();

        Http::assertNothingSent();
        $this->assertFalse(Adventure::where('name', 'Sommerlager')->firstOrFail()->talk_teamer_room);
    }

    public function test_ausgeschaltete_integration_zeigt_keinen_abschnitt_und_sendet_nichts(): void
    {
        Setting::set('talk_enabled', '0');
        $admin = $this->userWithRole(10);

        $this->actingAs($admin)->get(route('adventures.create'))->assertOk()->assertDontSee('Plot-Thread erstellen');

        $this->actingAs($admin)
            ->post(route('adventures.store'), $this->payload(['talk_plot_thread' => '1', 'talk_teamer_room' => '1']))
            ->assertRedirect();

        Http::assertNothingSent();
        $adventure = Adventure::where('name', 'Sommerlager')->firstOrFail();
        $this->assertFalse($adventure->talk_plot_thread);

        $this->actingAs($admin)
            ->get(route('adventures.manage', $adventure))
            ->assertOk()
            ->assertSee('Die Talk-Integration ist nicht aktiv.');
    }

    public function test_anlegen_formular_zeigt_talk_abschnitt(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.create'))
            ->assertOk()
            ->assertSee('Plot-Thread erstellen')
            ->assertSee('Teamer-Raum erstellen');
    }

    // ── Keine Duplikate, Umbenennen, Fehler ──────────────────────────

    public function test_erneutes_einrichten_erzeugt_keine_duplikate(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'Winterlager']);
        $adventure->talk_plot_thread = true;
        $adventure->talk_teamer_room = true;
        $adventure->save();

        AdventureTalkService::make()->setup($adventure);
        AdventureTalkService::make()->setup($adventure->fresh());

        $this->assertSame(1, $this->requests('POST', '/v1/chat/plot42ab'));
        $this->assertSame(1, $this->requests('POST', '/v4/room'));
        $this->assertSame(0, $this->requests('PUT', '/threads/500'));
    }

    public function test_neuer_eventname_benennt_thread_und_raum_um(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'Winterlager']);
        $adventure->talk_plot_thread = true;
        $adventure->talk_teamer_room = true;
        $adventure->save();
        AdventureTalkService::make()->setup($adventure);

        $this->actingAs($this->userWithRole(10))
            ->putJson(route('adventures.update', $adventure), $this->payload(['name' => 'Frostlager']))
            ->assertOk();

        $adventure->refresh();
        $this->assertSame('Frostlager', $adventure->talk_plot_thread_title);
        $this->assertSame('Teamer: Frostlager', $adventure->talk_teamer_room_name);
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_contains($r->url(), '/threads/500') && $r['threadTitle'] === 'Frostlager');
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_contains($r->url(), '/v4/room/team77xy') && $r['roomName'] === 'Teamer: Frostlager');
    }

    public function test_speichern_ohne_namensaenderung_plant_keinen_job(): void
    {
        $adventure = Adventure::factory()->create($this->payload(['name' => 'Winterlager']));
        $adventure->talk_plot_thread = true;
        $adventure->save();
        Bus::fake();

        $this->actingAs($this->userWithRole(10))
            ->putJson(route('adventures.update', $adventure), $this->payload(['name' => 'Winterlager', 'max_player' => 12]))
            ->assertOk();

        Bus::assertNotDispatched(SetupAdventureTalkJob::class);
    }

    public function test_fehler_beim_plot_thread_wird_angezeigt_und_raum_trotzdem_angelegt(): void
    {
        $this->plotStatus = 404;
        $adventure = Adventure::factory()->create(['name' => 'Winterlager']);
        $adventure->talk_plot_thread = true;
        $adventure->talk_teamer_room = true;
        $adventure->save();

        AdventureTalkService::make()->setup($adventure);

        $adventure->refresh();
        $this->assertNull($adventure->talk_plot_thread_id);
        $this->assertSame('team77xy', $adventure->talk_teamer_room_token);
        $this->assertStringContainsString('Ist das technische Konto Mitglied im Chat?', $adventure->talk_error);

        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.manage', $adventure))
            ->assertSee('Talk konnte nicht vollständig eingerichtet werden')
            ->assertSee('Erneut versuchen');

        // Erneuter Versuch klappt und löscht die Fehlermeldung.
        $this->plotStatus = 201;
        $this->actingAs($this->userWithRole(10))
            ->postJson(route('adventures.talk.retry', $adventure))
            ->assertOk();

        $adventure->refresh();
        $this->assertSame(500, $adventure->talk_plot_thread_id);
        $this->assertNull($adventure->talk_error);
    }

    public function test_fehlender_plot_chat_in_der_verwaltung(): void
    {
        Setting::set('talk_plot_room_token', null);
        $adventure = Adventure::factory()->create();
        $adventure->talk_plot_thread = true;
        $adventure->save();

        AdventureTalkService::make()->setup($adventure);

        $this->assertStringContainsString('kein Plot-Team-Chat hinterlegt', $adventure->fresh()->talk_error);
        Http::assertNothingSent();
    }

    // ── Teamer-Abgleich ───────────────────────────────────────────────

    public function test_bestaetigter_teamer_wird_per_email_gefunden_und_hinzugefuegt(): void
    {
        $adventure = $this->adventureWithRoom();
        $teamer = $this->userWithRole(50, ['email' => 'mara@example.test']);
        $this->directory['mara@example.test'] = ['mara.w'];
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id, 'user_id' => $teamer->id]);

        $this->actingAs($this->userWithRole(10))
            ->patchJson(route('adventures.teamer.approve', [$adventure, $signup]))
            ->assertOk();

        $this->assertSame('mara.w', $teamer->fresh()->nextcloud_user_id);
        $this->assertArrayHasKey('mara.w', $this->roomMembers);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_contains($r->url(), '/participants')
            && $r['newParticipant'] === 'mara.w' && $r['source'] === 'users');

        // Bestätigung zurücknehmen → Teamer verlässt den Raum, das technische Konto bleibt.
        $this->actingAs($this->userWithRole(10))
            ->patchJson(route('adventures.teamer.approve', [$adventure, $signup]))
            ->assertOk();

        $this->assertArrayNotHasKey('mara.w', $this->roomMembers);
        $this->assertArrayHasKey('heldenregister', $this->roomMembers);
    }

    public function test_abgelehnte_und_stornierte_teamer_werden_entfernt(): void
    {
        $adventure = $this->adventureWithRoom();
        $a = $this->userWithRole(50, ['nextcloud_user_id' => 'anna']);
        $b = $this->userWithRole(50, ['nextcloud_user_id' => 'bert']);
        $signupA = TeamerSignup::factory()->create(['adventure_id' => $adventure->id, 'user_id' => $a->id, 'approved_at' => now()]);
        $signupB = TeamerSignup::factory()->create(['adventure_id' => $adventure->id, 'user_id' => $b->id, 'approved_at' => now()]);
        $this->roomMembers += ['anna' => 11, 'bert' => 12, 'plotfee' => 13];

        $admin = $this->userWithRole(10);
        $this->actingAs($admin)->patchJson(route('adventures.teamer.reject', [$adventure, $signupA]))->assertOk();
        $this->assertArrayNotHasKey('anna', $this->roomMembers);

        $this->actingAs($admin)->deleteJson(route('adventures.teamer.destroy', [$adventure, $signupB]))->assertOk();
        $this->assertArrayNotHasKey('bert', $this->roomMembers);

        // Von Hand hinzugefügte Mitglieder bleiben.
        $this->assertArrayHasKey('plotfee', $this->roomMembers);
    }

    public function test_teamer_ohne_oder_mit_mehrdeutigem_konto_wird_nicht_hinzugefuegt(): void
    {
        $adventure = $this->adventureWithRoom();
        $ohne = $this->userWithRole(50, ['name' => 'Ohnekonto', 'email' => 'ohne@example.test']);
        $doppelt = $this->userWithRole(50, ['email' => 'doppelt@example.test']);
        $this->directory['doppelt@example.test'] = ['d1', 'd2'];
        TeamerSignup::factory()->create(['adventure_id' => $adventure->id, 'user_id' => $ohne->id, 'approved_at' => now()]);
        TeamerSignup::factory()->create(['adventure_id' => $adventure->id, 'user_id' => $doppelt->id, 'approved_at' => now()]);

        $this->actingAs($this->userWithRole(10))
            ->postJson(route('adventures.talk.sync', $adventure))
            ->assertOk();

        $this->assertSame(0, $this->requests('POST', '/participants'));
        $this->assertNull($ohne->fresh()->nextcloud_user_id);
        $this->assertNull($doppelt->fresh()->nextcloud_user_id);

        $this->actingAs($this->userWithRole(10))
            ->get(route('adventures.manage', $adventure))
            ->assertSee('Ohnekonto')
            ->assertSee('Kein Nextcloud-Konto gefunden');
    }

    public function test_ohne_teamer_raum_wird_nicht_abgeglichen(): void
    {
        Bus::fake();
        $adventure = Adventure::factory()->create();
        $signup = TeamerSignup::factory()->create(['adventure_id' => $adventure->id]);

        $this->actingAs($this->userWithRole(10))
            ->patchJson(route('adventures.teamer.approve', [$adventure, $signup]))
            ->assertOk();

        Bus::assertNotDispatched(SyncAdventureTalkTeamersJob::class);

        $this->actingAs($this->userWithRole(10))
            ->postJson(route('adventures.talk.sync', $adventure))
            ->assertStatus(422);
    }

    // ── Reiter „Talk“ ─────────────────────────────────────────────────

    public function test_reiter_richtet_nachtraeglich_ein_und_zeigt_links(): void
    {
        $adventure = Adventure::factory()->create(['name' => 'Winterlager']);
        $admin = $this->userWithRole(10);

        $this->actingAs($admin)
            ->get(route('adventures.manage', $adventure))
            ->assertOk()
            ->assertSee('id="talk-plot-thread-desk"', false)
            ->assertSee('id="talk-teamer-room-mob"', false);

        $this->actingAs($admin)
            ->putJson(route('adventures.talk.update', $adventure), ['talk_plot_thread' => '1'])
            ->assertOk()
            ->assertJson(['refresh_modal' => true]);

        $adventure->refresh();
        $this->assertTrue($adventure->talk_plot_thread);
        $this->assertFalse($adventure->talk_teamer_room);

        $this->actingAs($admin)
            ->get(route('adventures.manage', $adventure))
            ->assertSee('https://cloud.example.test/index.php/call/plot42ab?threadId=500', false)
            ->assertDontSee('id="talk-plot-thread-desk"', false)
            ->assertSee('id="talk-teamer-room-desk"', false);
    }

    public function test_nur_eventverwalter_duerfen_talk_aktionen(): void
    {
        $adventure = Adventure::factory()->create();

        foreach ([50, 70] as $roleId) {
            $user = $this->userWithRole($roleId);
            $this->actingAs($user)->putJson(route('adventures.talk.update', $adventure), ['talk_plot_thread' => '1'])->assertForbidden();
            $this->actingAs($user)->postJson(route('adventures.talk.sync', $adventure))->assertForbidden();
            $this->actingAs($user)->postJson(route('adventures.talk.retry', $adventure))->assertForbidden();
        }

        // Bürokrat (events.edit) darf.
        $this->actingAs($this->userWithRole(20))
            ->putJson(route('adventures.talk.update', $adventure), ['talk_teamer_room' => '1'])
            ->assertOk();
        $this->assertSame('team77xy', $adventure->fresh()->talk_teamer_room_token);
    }

    // ── Nutzerprofil ──────────────────────────────────────────────────

    public function test_admin_traegt_nextcloud_benutzernamen_ein(): void
    {
        $user = $this->userWithRole(50);
        $admin = $this->userWithRole(10);
        $data = ['name' => $user->name, 'email' => $user->email];

        $this->actingAs($admin)
            ->patch(route('admin.users.profile.update', $user), $data + ['nextcloud_user_id' => ' mara.w '])
            ->assertRedirect();
        $this->assertSame('mara.w', $user->fresh()->nextcloud_user_id);

        $this->actingAs($admin)
            ->patch(route('admin.users.profile.update', $user), $data + ['nextcloud_user_id' => 'böse<script>'])
            ->assertSessionHasErrors('nextcloud_user_id');

        $this->actingAs($admin)
            ->patch(route('admin.users.profile.update', $user), $data + ['nextcloud_user_id' => ''])
            ->assertRedirect();
        $this->assertNull($user->fresh()->nextcloud_user_id);
    }
}
