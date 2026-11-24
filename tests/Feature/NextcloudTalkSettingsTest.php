<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\NextcloudTalk\NextcloudTalkClient;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Issue #31 (Phase 1): Nextcloud-Talk-Anbindung – Konfiguration, Verbindungstest, Einstellungen.
 * Ausschließlich simulierte API-Antworten (Http::fake), kein echter Nextcloud-Aufruf.
 */
class NextcloudTalkSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'geheimes-app-passwort-123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Http::preventStrayRequests();
    }

    private function configure(): void
    {
        config([
            'nextcloud.url' => 'https://cloud.example.test',
            'nextcloud.user' => 'heldenregister',
            'nextcloud.app_password' => self::SECRET,
        ]);
    }

    private function userWithRole(int $roleId): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach($roleId);

        return $user;
    }

    private function fakeNextcloud(array $spreed = ['version' => '21.1.0']): void
    {
        Http::fake([
            'cloud.example.test/ocs/v2.php/cloud/user*' => Http::response(['ocs' => ['data' => ['id' => 'heldenregister']]]),
            'cloud.example.test/ocs/v2.php/cloud/capabilities*' => Http::response(['ocs' => ['data' => [
                'version' => ['string' => '31.0.5'],
                'capabilities' => $spreed ? ['spreed' => $spreed] : [],
            ]]]),
        ]);
    }

    // ── Client ────────────────────────────────────────────────────────

    public function test_ohne_zugangsdaten_ist_die_anbindung_aus(): void
    {
        config(['nextcloud.url' => '', 'nextcloud.user' => null, 'nextcloud.app_password' => null]);
        $client = NextcloudTalkClient::fromConfig();

        $this->assertFalse($client->isConfigured());
        $this->assertFalse($client->testConnection()['ok']);
        Http::assertNothingSent();
    }

    public function test_verbindungstest_liefert_versionen_und_sendet_ocs_kopfzeilen(): void
    {
        $this->configure();
        $this->fakeNextcloud();

        $result = NextcloudTalkClient::fromConfig()->testConnection();

        $this->assertTrue($result['ok']);
        $this->assertSame('heldenregister', $result['user']);
        $this->assertSame('31.0.5', $result['version']);
        $this->assertSame('21.1.0', $result['talk_version']);

        Http::assertSent(fn (Request $request) => $request->hasHeader('OCS-APIRequest', 'true')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('heldenregister:'.self::SECRET)));
    }

    public function test_verbindungstest_meldet_fehlendes_talk(): void
    {
        $this->configure();
        $this->fakeNextcloud([]);

        $result = NextcloudTalkClient::fromConfig()->testConnection();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Talk ist nicht installiert', $result['message']);
    }

    public function test_verbindungstest_meldet_falsche_zugangsdaten_und_zeitueberschreitung(): void
    {
        $this->configure();
        Http::fake(['*' => Http::response(['ocs' => []], 401)]);
        $this->assertStringContainsString('Anmeldung abgelehnt', NextcloudTalkClient::fromConfig()->testConnection()['message']);

        Http::fake(fn () => throw new ConnectionException('timeout'));
        $result = NextcloudTalkClient::fromConfig()->testConnection();
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('nicht erreichbar', $result['message']);
    }

    public function test_raum_token_wird_aus_link_oder_eingabe_gelesen(): void
    {
        $this->assertSame('abc123xy', NextcloudTalkClient::roomTokenFrom('https://cloud.example.test/call/abc123xy'));
        $this->assertSame('abc123xy', NextcloudTalkClient::roomTokenFrom('https://cloud.example.test/index.php/call/abc123xy#message_5'));
        $this->assertSame('abc123xy', NextcloudTalkClient::roomTokenFrom(' abc123xy '));
        $this->assertNull(NextcloudTalkClient::roomTokenFrom('https://cloud.example.test/apps/files'));
        $this->assertNull(NextcloudTalkClient::roomTokenFrom(''));
    }

    // ── Verwaltung ────────────────────────────────────────────────────

    public function test_nur_admin_hat_zugriff(): void
    {
        $this->get(route('admin.talk.index'))->assertRedirect(route('login'));

        foreach ([20, 30, 40] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))->get(route('admin.talk.index'))->assertForbidden();
        }

        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.talk.index'))
            ->assertOk()
            ->assertSee('Nicht konfiguriert');

        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.index'))
            ->assertSee(route('admin.talk.index'), false);
    }

    public function test_status_zeigt_adresse_aber_nie_das_app_passwort(): void
    {
        $this->configure();
        $this->fakeNextcloud();
        $admin = $this->userWithRole(10);

        $this->actingAs($admin)
            ->get(route('admin.talk.index'))
            ->assertOk()
            ->assertSee('Konfiguriert')
            ->assertSee('https://cloud.example.test')
            ->assertDontSee(self::SECRET);

        $this->actingAs($admin)
            ->post(route('admin.talk.test'))
            ->assertRedirect(route('admin.talk.index'));

        $this->actingAs($admin)
            ->get(route('admin.talk.index'))
            ->assertSee('Verbindung erfolgreich.')
            ->assertSee('Talk-Version: 21.1.0')
            ->assertDontSee(self::SECRET);
    }

    public function test_einstellungen_werden_gespeichert_und_raum_link_auf_token_reduziert(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->put(route('admin.talk.update'), [
                'talk_enabled' => '1',
                'talk_plot_room' => 'https://cloud.example.test/call/plot42ab',
                'talk_u18_group' => 'talk-u18',
                'talk_app_url' => 'https://cloud.example.test/apps/spreed',
                'talk_help_url' => 'https://hilfe.example.test/talk',
            ])
            ->assertRedirect(route('admin.talk.index'));

        $this->assertSame('1', Setting::get('talk_enabled'));
        $this->assertSame('plot42ab', Setting::get('talk_plot_room_token'));
        $this->assertSame('talk-u18', Setting::get('talk_u18_group'));
        $this->assertSame('https://cloud.example.test/apps/spreed', Setting::get('talk_app_url'));
    }

    public function test_ungueltige_einstellungen_werden_abgelehnt(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->put(route('admin.talk.update'), [
                'talk_plot_room' => 'https://cloud.example.test/apps/files',
                'talk_u18_group' => 'böse<script>',
                'talk_help_url' => 'http://unsicher.example.test',
            ])
            ->assertSessionHasErrors(['talk_u18_group', 'talk_help_url']);

        $this->actingAs($this->userWithRole(10))
            ->put(route('admin.talk.update'), ['talk_plot_room' => 'https://cloud.example.test/apps/files'])
            ->assertSessionHasErrors('talk_plot_room');

        $this->assertNull(Setting::get('talk_plot_room_token'));
    }

    public function test_hilfe_link_ist_vorbelegt(): void
    {
        $this->actingAs($this->userWithRole(10))
            ->get(route('admin.talk.index'))
            ->assertSee('https://hilfe.waldritter-giessen.de/70_nextcloud-talk/index.html');
    }
}
