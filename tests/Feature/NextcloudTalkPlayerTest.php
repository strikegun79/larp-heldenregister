<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use App\Services\NextcloudTalk\TalkTexts;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Issue #31 (Phase 3): Spieler-Reiter „Talk“ – Freigabe durch den Bürokraten (ab 14),
 * Erlaubnis der Eltern bei U18, Nextcloud-Konto verknüpfen bzw. anlegen.
 * Ausschließlich simulierte Nextcloud (Http::fake), kein echter Aufruf.
 */
class NextcloudTalkPlayerTest extends TestCase
{
    use RefreshDatabase;

    /** Simulierte Nextcloud: Benutzername → ['email' => …, 'groups' => [], 'enabled' => bool]. */
    private array $accounts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Http::preventStrayRequests();

        config([
            'nextcloud.url' => 'https://cloud.example.test',
            'nextcloud.user' => 'heldenregister',
            'nextcloud.app_password' => 'geheim',
        ]);
        Setting::set('talk_enabled', '1');
        Setting::set('talk_u18_group', 'talk-u18');
        Setting::set('talk_app_url', 'https://cloud.example.test/apps/spreed');

        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $method = $request->method();
            $prefix = '/ocs/v2.php/cloud/users';

            if ($method === 'GET' && $path === $prefix.'/details') {
                $users = array_filter($this->accounts, fn ($a) => $a['email'] === ($query['search'] ?? ''));

                return Http::response(['ocs' => ['data' => ['users' => array_map(
                    fn ($id) => ['id' => $id, 'email' => $users[$id]['email']], array_combine(array_keys($users), array_keys($users))
                )]]]);
            }
            if ($method === 'POST' && $path === $prefix) {
                $this->accounts[$request['userid']] = ['email' => $request['email'], 'groups' => [], 'enabled' => true];

                return Http::response(['ocs' => ['data' => ['id' => $request['userid']]]]);
            }
            if (preg_match('#^'.$prefix.'/([^/]+)(/(groups|enable|disable))?$#', $path, $m)) {
                $id = rawurldecode($m[1]);
                if (! isset($this->accounts[$id])) {
                    return Http::response(['ocs' => []], 404);
                }

                return match ($m[3] ?? '') {
                    'groups' => tap(Http::response(['ocs' => ['data' => []]]), fn () => $this->accounts[$id]['groups'][] = $request['groupid']),
                    'enable' => tap(Http::response(['ocs' => ['data' => []]]), fn () => $this->accounts[$id]['enabled'] = true),
                    'disable' => tap(Http::response(['ocs' => ['data' => []]]), fn () => $this->accounts[$id]['enabled'] = false),
                    default => Http::response(['ocs' => ['data' => ['id' => $id]]]),
                };
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

    private function registrar(): User
    {
        return $this->userWithRole(20);
    }

    /** Spieler mit Alter; optional mit Elternteil (self = false). */
    private function player(int $age, ?User $guardian = null, array $attributes = []): Player
    {
        $player = Player::factory()->create(array_merge(
            ['name' => 'Mia', 'lastname' => 'Müller', 'dayofbirth' => now()->subYears($age)->subDays(10)->toDateString()],
            $attributes,
        ));
        $guardian?->players()->attach($player->id, ['self' => false]);

        return $player;
    }

    // ── Freigabe durch den Bürokraten ────────────────────────────────

    public function test_nur_buerokrat_und_admin_sehen_die_freigaben(): void
    {
        $this->get(route('admin.talk.approvals.index'))->assertRedirect(route('login'));

        foreach ([30, 40, 50, 70] as $roleId) {
            $this->actingAs($this->userWithRole($roleId))->get(route('admin.talk.approvals.index'))->assertForbidden();
        }

        $this->player(15);
        $this->actingAs($this->registrar())
            ->get(route('admin.talk.approvals.index'))
            ->assertOk()
            ->assertSee('Mia Müller')
            ->assertSee('Freigeben');

        $this->actingAs($this->registrar())
            ->get(route('admin.index'))
            ->assertSee(route('admin.talk.approvals.index'), false);
    }

    public function test_freigabe_erst_ab_14_und_nur_mit_geburtsdatum(): void
    {
        $young = $this->player(13);
        $unknown = $this->player(20, null, ['dayofbirth' => null]);

        $this->actingAs($this->registrar())
            ->post(route('admin.talk.approvals.approve', $young))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'erst ab 14 Jahren'));
        $this->actingAs($this->registrar())
            ->post(route('admin.talk.approvals.approve', $unknown))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Kein Geburtsdatum'));

        $this->assertNull($young->fresh()->talk_approved_at);
        $this->assertNull($unknown->fresh()->talk_approved_at);
        Http::assertNothingSent();

        $this->actingAs($this->registrar())
            ->get(route('admin.talk.approvals.index'))
            ->assertSee('Talk ist erst ab 14 Jahren möglich.');
    }

    public function test_freigabe_bei_u18_wartet_auf_die_eltern(): void
    {
        $guardian = $this->userWithRole(70);
        $player = $this->player(15, $guardian);
        $registrar = $this->registrar();

        $this->actingAs($registrar)
            ->post(route('admin.talk.approvals.approve', $player))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'Eltern'));

        $player->refresh();
        $this->assertNotNull($player->talk_approved_at);
        $this->assertSame($registrar->id, $player->talk_approved_by);
        $this->assertSame('consent', $player->talkStatus());
        Http::assertNothingSent();
        $this->assertDatabaseHas('audit_logs', ['action' => 'talk.approved']);
    }

    // ── Reiter „Talk“ und Elternerlaubnis ────────────────────────────

    public function test_reiter_erscheint_erst_nach_der_freigabe(): void
    {
        $guardian = $this->userWithRole(70);
        $player = $this->player(15, $guardian);

        $this->actingAs($guardian)->get(route('players.show', $player))->assertOk()->assertDontSee('data-tab="p-talk"', false);

        $player->talk_approved_at = now();
        $player->save();

        $this->actingAs($guardian)
            ->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('data-tab="p-talk"', false)
            ->assertSee('Teilnahmebedingungen')
            ->assertSee('Gib keine Adresse, Telefonnummer oder Passwörter im Chat weiter.')
            ->assertSee('id="talk-accept-d"', false)
            ->assertSee('id="talk-accept-m"', false)
            ->assertSee('https://hilfe.waldritter-giessen.de/70_nextcloud-talk/index.html');
    }

    public function test_eltern_erlauben_und_konto_wird_angelegt_mit_u18_gruppe(): void
    {
        $guardian = $this->userWithRole(70, ['name' => 'Petra', 'lastname' => 'Müller', 'email' => 'petra@example.test']);
        $player = $this->player(15, $guardian, ['email' => null]);
        $player->talk_approved_at = now();
        $player->save();

        $this->actingAs($guardian)
            ->post(route('players.talk.consent', $player), ['accept' => '1'])
            ->assertSessionHas('status');

        $player->refresh();
        $this->assertSame($guardian->id, $player->talk_consent_by);
        $this->assertSame(TalkTexts::version(), $player->talk_consent_version);
        $this->assertSame('mia.mueller', $player->nextcloud_user_id);
        $this->assertTrue($player->nextcloud_account_created);
        $this->assertSame('ready', $player->talkStatus());

        // Konto ohne Passwort (Willkommensmail) mit E-Mail des Elternteils, in der U18-Gruppe.
        $this->assertSame('petra@example.test', $this->accounts['mia.mueller']['email']);
        $this->assertSame(['talk-u18'], $this->accounts['mia.mueller']['groups']);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with((string) parse_url($r->url(), PHP_URL_PATH), '/cloud/users')
            && $r['password'] === '' && $r['displayName'] === 'Mia Müller');

        $this->actingAs($guardian)
            ->get(route('players.show', $player))
            ->assertSee('Nextcloud-Benutzername: <strong>mia.mueller</strong>', false)
            ->assertSee('https://cloud.example.test/apps/spreed', false)
            ->assertSee('Erlaubt von Petra Müller');
    }

    public function test_erlaubnis_braucht_das_haekchen_und_die_freigabe(): void
    {
        $guardian = $this->userWithRole(70);
        $player = $this->player(15, $guardian);

        $this->actingAs($guardian)
            ->postJson(route('players.talk.consent', $player), ['accept' => '1'])
            ->assertStatus(422);

        $player->talk_approved_at = now();
        $player->save();

        $this->actingAs($guardian)
            ->postJson(route('players.talk.consent', $player), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('accept');

        $this->assertNull($player->fresh()->talk_consent_at);
        Http::assertNothingSent();
    }

    public function test_nur_eltern_duerfen_erlauben(): void
    {
        $player = $this->player(15);
        $player->talk_approved_at = now();
        $player->save();

        // Fremder Nutzer, Bürokrat und der Spieler selbst (self = true) dürfen nicht.
        $self = $this->userWithRole(70);
        $self->players()->attach($player->id, ['self' => true]);

        foreach ([$this->userWithRole(70), $this->registrar(), $self] as $user) {
            $this->actingAs($user)->post(route('players.talk.consent', $player), ['accept' => '1'])->assertForbidden();
        }

        $this->assertNull($player->fresh()->talk_consent_at);
    }

    public function test_vorhandenes_konto_wird_per_email_verknuepft(): void
    {
        $this->accounts['mia.alt'] = ['email' => 'mia@example.test', 'groups' => [], 'enabled' => true];
        $guardian = $this->userWithRole(70);
        $player = $this->player(16, $guardian, ['email' => 'mia@example.test']);
        $player->talk_approved_at = now();
        $player->save();

        $this->actingAs($guardian)->post(route('players.talk.consent', $player), ['accept' => '1']);

        $player->refresh();
        $this->assertSame('mia.alt', $player->nextcloud_user_id);
        $this->assertFalse($player->nextcloud_account_created);
        $this->assertSame(['talk-u18'], $this->accounts['mia.alt']['groups']);
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with((string) parse_url($r->url(), PHP_URL_PATH), '/cloud/users'));
    }

    public function test_vergebener_benutzername_bekommt_eine_zahl(): void
    {
        $this->accounts['mia.mueller'] = ['email' => 'andere@example.test', 'groups' => [], 'enabled' => true];
        $this->accounts['mia.mueller2'] = ['email' => 'dritte@example.test', 'groups' => [], 'enabled' => true];
        $player = $this->player(20, null, ['email' => 'mia@example.test']);

        $this->actingAs($this->registrar())->post(route('admin.talk.approvals.approve', $player));

        $this->assertSame('mia.mueller3', $player->fresh()->nextcloud_user_id);
    }

    public function test_erwachsene_brauchen_keine_elternerlaubnis_und_keine_u18_gruppe(): void
    {
        $player = $this->player(25, null, ['email' => 'erwachsen@example.test']);

        $this->actingAs($this->registrar())
            ->post(route('admin.talk.approvals.approve', $player))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'Konto wird eingerichtet'));

        $player->refresh();
        $this->assertSame('ready', $player->talkStatus());
        $this->assertSame([], $this->accounts['mia.mueller']['groups']);
    }

    public function test_fehlende_u18_gruppe_oder_email_wird_als_fehler_angezeigt(): void
    {
        Setting::set('talk_u18_group', null);
        $guardian = $this->userWithRole(70);
        $player = $this->player(15, $guardian);
        $player->talk_approved_at = now();
        $player->save();

        $this->actingAs($guardian)->post(route('players.talk.consent', $player), ['accept' => '1']);

        $player->refresh();
        $this->assertSame('error', $player->talkStatus());
        $this->assertStringContainsString('keine Nextcloud-Gruppe für U18-Konten', $player->talk_error);
        Http::assertNothingSent();

        $this->actingAs($this->registrar())
            ->get(route('admin.talk.approvals.index'))
            ->assertSee('keine Nextcloud-Gruppe für U18-Konten')
            ->assertSee('Erneut versuchen');

        // Eltern sehen nur einen allgemeinen Hinweis.
        $this->actingAs($guardian)
            ->get(route('players.show', $player))
            ->assertSee('Der Verein kümmert sich darum.')
            ->assertDontSee('keine Nextcloud-Gruppe');

        // Nach dem Eintragen der Gruppe klappt der erneute Versuch.
        Setting::set('talk_u18_group', 'talk-u18');
        $this->actingAs($this->registrar())->post(route('admin.talk.approvals.retry', $player));
        $this->assertSame('ready', $player->fresh()->talkStatus());
    }

    // ── Rücknahme und Widerruf ────────────────────────────────────────

    public function test_ruecknahme_deaktiviert_angelegtes_konto_und_neue_freigabe_aktiviert_es(): void
    {
        $player = $this->player(25, null, ['email' => 'erwachsen@example.test']);
        $registrar = $this->registrar();
        $this->actingAs($registrar)->post(route('admin.talk.approvals.approve', $player));

        $this->actingAs($registrar)->delete(route('admin.talk.approvals.revoke', $player))->assertSessionHas('status');

        $player->refresh();
        $this->assertNull($player->talk_approved_at);
        $this->assertSame('none', $player->talkStatus());
        $this->assertSame('mia.mueller', $player->nextcloud_user_id);
        $this->assertFalse($this->accounts['mia.mueller']['enabled']);

        $this->actingAs($registrar)->post(route('admin.talk.approvals.approve', $player));
        $this->assertTrue($this->accounts['mia.mueller']['enabled']);
        $this->assertSame(1, Http::recorded(fn (Request $r) => $r->method() === 'POST' && str_ends_with((string) parse_url($r->url(), PHP_URL_PATH), '/cloud/users'))->count());
    }

    public function test_widerruf_der_eltern_und_verknuepfte_fremdkonten_bleiben_aktiv(): void
    {
        $this->accounts['mia.alt'] = ['email' => 'mia@example.test', 'groups' => [], 'enabled' => true];
        $guardian = $this->userWithRole(70);
        $player = $this->player(15, $guardian, ['email' => 'mia@example.test']);
        $player->talk_approved_at = now();
        $player->save();
        $this->actingAs($guardian)->post(route('players.talk.consent', $player), ['accept' => '1']);

        $this->actingAs($guardian)->delete(route('players.talk.withdraw', $player))->assertSessionHas('status');

        $player->refresh();
        $this->assertNull($player->talk_consent_at);
        $this->assertNull($player->talk_approved_at);
        // Verknüpftes (nicht vom Heldenregister angelegtes) Konto wird nicht deaktiviert.
        $this->assertTrue($this->accounts['mia.alt']['enabled']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'talk.consent_withdrawn']);
    }

    public function test_ausgeschaltete_integration_speichert_freigabe_aber_sendet_nichts(): void
    {
        Setting::set('talk_enabled', '0');
        $player = $this->player(25);

        $this->actingAs($this->registrar())
            ->get(route('admin.talk.approvals.index'))
            ->assertSee('Die Talk-Integration ist nicht aktiv.');

        $this->actingAs($this->registrar())->post(route('admin.talk.approvals.approve', $player));

        $this->assertNotNull($player->fresh()->talk_approved_at);
        $this->assertSame('pending', $player->fresh()->talkStatus());
        Http::assertNothingSent();
    }

    // ── Texte in der Verwaltung ───────────────────────────────────────

    public function test_admin_passt_die_texte_an_und_die_fassung_aendert_sich(): void
    {
        $admin = $this->userWithRole(10);
        $before = TalkTexts::version();

        $this->actingAs($admin)
            ->get(route('admin.talk.index'))
            ->assertSee('Texte für die Elternerlaubnis')
            ->assertSee('Wir gehen freundlich und respektvoll miteinander um');

        $this->actingAs($admin)
            ->put(route('admin.talk.texts.update'), [
                'talk_terms_text' => 'Eigene Bedingungen',
                'talk_safety_text' => '',
                'talk_rules_text' => TalkTexts::DEFAULTS['talk_rules_text'],
            ])
            ->assertRedirect(route('admin.talk.index'));

        $this->assertSame('Eigene Bedingungen', TalkTexts::all()['talk_terms_text']);
        $this->assertSame(TalkTexts::DEFAULTS['talk_safety_text'], TalkTexts::all()['talk_safety_text']);
        $this->assertNull(Setting::get('talk_rules_text'));
        $this->assertNotSame($before, TalkTexts::version());

        $this->actingAs($this->registrar())->put(route('admin.talk.texts.update'), [])->assertForbidden();
    }
}
