<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\Player;
use App\Models\Setting;
use App\Models\TalkLog;
use App\Models\User;
use App\Services\NextcloudTalk\NextcloudTalkClient;
use App\Services\NextcloudTalk\NextcloudTalkException;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Talk-Protokoll: Änderungen und Fehler der Anfragen an Nextcloud, Meldungen mit Aktion
 * und Hinweis auf fehlende Rechte. Ausschließlich simulierte Nextcloud (Http::fake).
 */
class TalkLogTest extends TestCase
{
    use RefreshDatabase;

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
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($roleId);

        return $user;
    }

    public function test_aktionen_in_klartext(): void
    {
        $this->assertSame('Konto anlegen: mara.muster', NextcloudTalkClient::describe('post', 'cloud/users', ['userid' => 'mara.muster']));
        $this->assertSame('Zur Gruppe hinzufügen: talk-u18', NextcloudTalkClient::describe('post', 'cloud/users/mara/groups', ['groupid' => 'talk-u18']));
        $this->assertSame('Konto deaktivieren: mara', NextcloudTalkClient::describe('put', 'cloud/users/mara/disable'));
        $this->assertSame('Teamer hinzufügen: max', NextcloudTalkClient::describe('post', 'apps/spreed/api/v4/room/abc/participants', ['newParticipant' => 'max']));
        $this->assertSame('Teamer-Raum anlegen: Sommerlager – Teamer', NextcloudTalkClient::describe('post', 'apps/spreed/api/v4/room', ['roomName' => 'Sommerlager – Teamer']));
        $this->assertSame('Plot-Thread anlegen: Sommerlager', NextcloudTalkClient::describe('post', 'apps/spreed/api/v1/chat/plot', ['threadTitle' => 'Sommerlager']));
    }

    public function test_403_bei_konto_anlegen_mit_aktion_und_admin_hinweis_wird_protokolliert(): void
    {
        Http::fake(['*' => Http::response(['ocs' => ['meta' => ['statuscode' => 403]]], 403)]);
        $player = Player::factory()->create();

        try {
            NextcloudTalkClient::fromConfig()->forContext(player: $player)->createUser('mara.muster', 'Mara Muster', 'mara@example.test');
            $this->fail('Keine Ausnahme');
        } catch (NextcloudTalkException $e) {
            $this->assertSame(
                'Konto anlegen: mara.muster: Zugriff verweigert: Das technische Konto braucht Admin-Rechte in Nextcloud (Gruppe „admin“).',
                $e->getMessage()
            );
        }

        $log = TalkLog::sole();
        $this->assertFalse($log->success);
        $this->assertSame(403, $log->status);
        $this->assertSame($player->id, $log->player_id);
        $this->assertSame('Konto anlegen: mara.muster', $log->action);
        $this->assertStringNotContainsString('geheim', (string) $log->message.$log->endpoint.$log->action);
    }

    public function test_403_im_talk_raum_mit_moderator_hinweis(): void
    {
        Http::fake(['*' => Http::response([], 403)]);
        $adventure = Adventure::factory()->create();

        try {
            NextcloudTalkClient::fromConfig()->forContext(adventure: $adventure)->addUser('abc123', 'max');
            $this->fail('Keine Ausnahme');
        } catch (NextcloudTalkException $e) {
            $this->assertStringContainsString('Teamer hinzufügen: max', $e->getMessage());
            $this->assertStringContainsString('Moderator', $e->getMessage());
        }

        $this->assertSame($adventure->id, TalkLog::sole()->adventure_id);
    }

    public function test_erfolgreiche_aenderung_protokolliert_lesen_nicht(): void
    {
        Http::fake(['*' => Http::response(['ocs' => ['data' => ['users' => []]]])]);
        $client = NextcloudTalkClient::fromConfig();

        $client->findUserIdByEmail('max@example.test');
        $this->assertSame(0, TalkLog::count());

        $client->enableUser('max');
        $log = TalkLog::sole();
        $this->assertTrue($log->success);
        $this->assertSame('Konto aktivieren: max', $log->action);
        $this->assertSame('PUT', $log->method);
    }

    public function test_protokollseite_mit_filter_und_rechten(): void
    {
        $player = Player::factory()->create(['name' => 'Mara', 'lastname' => 'Muster']);
        TalkLog::create(['player_id' => $player->id, 'action' => 'Konto anlegen: mara', 'method' => 'POST', 'endpoint' => 'cloud/users', 'status' => 403, 'success' => false, 'message' => 'Konto anlegen: mara: Zugriff verweigert']);
        TalkLog::create(['action' => 'Konto aktivieren: max', 'method' => 'PUT', 'endpoint' => 'cloud/users/max/enable', 'status' => 200, 'success' => true]);

        $registrar = $this->userWithRole(20);

        $this->actingAs($registrar)
            ->get(route('admin.talk.logs.index'))
            ->assertOk()
            ->assertSee('Konto anlegen: mara')
            ->assertSee('Konto aktivieren: max')
            ->assertSee('Mara Muster');

        $this->actingAs($registrar)
            ->get(route('admin.talk.logs.index', ['errors' => 1]))
            ->assertOk()
            ->assertSee('Konto anlegen: mara')
            ->assertDontSee('Konto aktivieren: max');

        $this->actingAs($registrar)
            ->get(route('admin.talk.logs.index', ['player' => $player->id]))
            ->assertOk()
            ->assertDontSee('Konto aktivieren: max');

        // Link in den Talk-Freigaben beim Spieler.
        $this->actingAs($registrar)
            ->get(route('admin.talk.approvals.index'))
            ->assertOk()
            ->assertSee(route('admin.talk.logs.index', ['player' => $player->id]), false);

        $this->actingAs($this->userWithRole(70))
            ->get(route('admin.talk.logs.index'))
            ->assertForbidden();
    }

    public function test_reiter_talk_zeigt_protokoll_der_veranstaltung(): void
    {
        $adventure = Adventure::factory()->create();
        TalkLog::create(['adventure_id' => $adventure->id, 'action' => 'Teamer-Raum anlegen: Test', 'method' => 'POST', 'endpoint' => 'apps/spreed/api/v4/room', 'status' => 403, 'success' => false, 'message' => 'Teamer-Raum anlegen: Test: Zugriff verweigert']);
        TalkLog::create(['adventure_id' => Adventure::factory()->create()->id, 'action' => 'Fremde Veranstaltung', 'method' => 'POST', 'endpoint' => 'x', 'success' => true]);

        $this->actingAs($this->userWithRole(10));
        $html = view('adventures._talk_tab', [
            'adventure' => $adventure->load('teamerSignups'),
            'ctx' => 'd',
            'talkActive' => true,
        ])->render();

        $this->assertStringContainsString('Protokoll (1 Eintrag, davon 1 Fehler)', $html);
        $this->assertStringContainsString('Teamer-Raum anlegen: Test: Zugriff verweigert', $html);
        $this->assertStringNotContainsString('Fremde Veranstaltung', $html);
    }

    public function test_dsgvo_prune_loescht_nach_90_tagen(): void
    {
        $old = TalkLog::create(['action' => 'alt', 'method' => 'PUT', 'endpoint' => 'x', 'success' => true]);
        $old->forceFill(['created_at' => now()->subDays(91)])->save();
        $new = TalkLog::create(['action' => 'neu', 'method' => 'PUT', 'endpoint' => 'x', 'success' => true]);

        $this->artisan('dsgvo:prune')->assertSuccessful();

        $this->assertDatabaseMissing('talk_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('talk_logs', ['id' => $new->id]);
    }
}
