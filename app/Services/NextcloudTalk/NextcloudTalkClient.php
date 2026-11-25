<?php

namespace App\Services\NextcloudTalk;

use App\Models\Adventure;
use App\Models\Player;
use App\Models\Setting;
use App\Models\TalkLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Issue #31: Schlanker Client für die OCS-API von Nextcloud (Talk = App „spreed“).
 * Phase 1: Verbindungstest. Phase 2: Threads, Räume und Teilnehmer. Phase 3: Konten.
 *
 * Zugangsdaten (App-Passwort) erscheinen nie in Rückgaben oder Meldungen.
 */
class NextcloudTalkClient
{
    public function __construct(
        private readonly string $url,
        private readonly ?string $user,
        private readonly ?string $appPassword,
        private readonly int $timeout = 10,
    ) {}

    /** Talk-Protokoll: Bezug der folgenden Anfragen (Veranstaltung bzw. Spieler). */
    private ?int $logAdventureId = null;

    private ?int $logPlayerId = null;

    /** Folgende Anfragen im Talk-Protokoll dieser Veranstaltung bzw. diesem Spieler zuordnen. */
    public function forContext(?Adventure $adventure = null, ?Player $player = null): static
    {
        $this->logAdventureId = $adventure?->id;
        $this->logPlayerId = $player?->id;

        return $this;
    }

    /** Client aus config/nextcloud.php (Werte aus der .env). */
    public static function fromConfig(): self
    {
        return new self(
            (string) config('nextcloud.url'),
            config('nextcloud.user'),
            config('nextcloud.app_password'),
            (int) config('nextcloud.timeout', 10),
        );
    }

    /** Sind Adresse, Benutzer und App-Passwort hinterlegt? */
    public function isConfigured(): bool
    {
        return $this->url !== '' && filled($this->user) && filled($this->appPassword);
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * Verbindung prüfen: Anmeldung, Nextcloud-Version und ob Talk installiert ist.
     *
     * @return array{ok: bool, message: string, user?: string, version?: string, talk_version?: string|null}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Nicht konfiguriert: NEXTCLOUD_URL, NEXTCLOUD_USER und NEXTCLOUD_APP_PASSWORD in der .env setzen.'];
        }

        try {
            $user = $this->request()->get($this->ocs('cloud/user'));
            if ($error = $this->errorMessage($user)) {
                return ['ok' => false, 'message' => $error];
            }

            $capabilities = $this->request()->get($this->ocs('cloud/capabilities'));
            if ($error = $this->errorMessage($capabilities)) {
                return ['ok' => false, 'message' => $error];
            }
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Nextcloud ist nicht erreichbar (Adresse prüfen oder Zeitüberschreitung).'];
        }

        $data = $capabilities->json('ocs.data', []);
        $version = (string) data_get($data, 'version.string', '');
        $talk = data_get($data, 'capabilities.spreed');
        $talkVersion = is_array($talk) ? (string) data_get($talk, 'version', '') : null;
        $login = (string) $user->json('ocs.data.id', $this->user);

        if (! is_array($talk)) {
            return [
                'ok' => false,
                'message' => 'Verbunden, aber Nextcloud Talk ist nicht installiert oder für dieses Konto nicht verfügbar.',
                'user' => $login,
                'version' => $version,
                'talk_version' => null,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Verbindung erfolgreich.',
            'user' => $login,
            'version' => $version,
            'talk_version' => $talkVersion !== '' ? $talkVersion : 'unbekannt',
        ];
    }

    /**
     * Ist die Integration nutzbar? Zugangsdaten in der .env und Hauptschalter in der Verwaltung.
     */
    public function isActive(): bool
    {
        return $this->isConfigured() && Setting::get('talk_enabled') === '1';
    }

    /**
     * Link zu einem Talk-Raum, optional direkt in einen Thread.
     * Mit „/index.php/“, damit der Link auch ohne Pretty URLs (mod_rewrite) funktioniert –
     * so erzeugt ihn auch Nextcloud selbst (linkToRouteAbsolute('spreed.Page.showCall')).
     */
    public function roomUrl(string $token, ?int $threadId = null): string
    {
        return $this->url.'/index.php/call/'.$token.($threadId ? '?threadId='.$threadId : '');
    }

    // ── Phase 2: Threads, Räume, Teilnehmer ─────────────────────────

    /**
     * Thread in einem bestehenden Raum starten (Talk ≥ 22, Fähigkeit „threads“).
     * Die erste Nachricht trägt den Thread-Titel; zurück kommt die Thread-ID.
     */
    public function createThread(string $roomToken, string $title, string $message): int
    {
        $response = $this->send('post', 'apps/spreed/api/v1/chat/'.rawurlencode($roomToken), [
            'message' => $message,
            'threadTitle' => $title,
        ], [404 => 'Der Plot-Team-Chat wurde nicht gefunden. Ist das technische Konto Mitglied im Chat?']);

        $threadId = (int) ($response->json('ocs.data.threadId') ?: $response->json('ocs.data.id'));
        if ($threadId <= 0) {
            throw new NextcloudTalkException('Talk hat keinen Thread angelegt (unterstützt die Talk-Version Threads?).');
        }

        return $threadId;
    }

    public function renameThread(string $roomToken, int $threadId, string $title): void
    {
        $this->send('put', 'apps/spreed/api/v1/chat/'.rawurlencode($roomToken).'/threads/'.$threadId, [
            'threadTitle' => $title,
        ]);
    }

    /** Gruppen-Unterhaltung (roomType 2, nicht öffentlich) anlegen; zurück kommt der Raum-Token. */
    public function createGroupRoom(string $name): string
    {
        $response = $this->send('post', 'apps/spreed/api/v4/room', [
            'roomType' => 2,
            'roomName' => $name,
        ]);

        $token = (string) $response->json('ocs.data.token', '');
        if ($token === '') {
            throw new NextcloudTalkException('Talk hat keinen Raum-Token zurückgegeben.');
        }

        return $token;
    }

    public function renameRoom(string $token, string $name): void
    {
        $this->send('put', 'apps/spreed/api/v4/room/'.rawurlencode($token), ['roomName' => $name]);
    }

    public function addUser(string $token, string $userId): void
    {
        $this->send('post', 'apps/spreed/api/v4/room/'.rawurlencode($token).'/participants', [
            'newParticipant' => $userId,
            'source' => 'users',
        ], [404 => 'Raum oder Nextcloud-Konto „'.$userId.'“ wurde nicht gefunden.']);
    }

    /**
     * Teilnehmer eines Raums.
     *
     * @return list<array{attendeeId: int, actorType: string, actorId: string}>
     */
    public function participants(string $token): array
    {
        $response = $this->send('get', 'apps/spreed/api/v4/room/'.rawurlencode($token).'/participants');

        return collect((array) $response->json('ocs.data', []))
            ->filter(fn ($p) => is_array($p) && isset($p['attendeeId']))
            ->map(fn (array $p) => [
                'attendeeId' => (int) $p['attendeeId'],
                'actorType' => (string) ($p['actorType'] ?? ''),
                'actorId' => (string) ($p['actorId'] ?? ''),
            ])
            ->values()
            ->all();
    }

    public function removeAttendee(string $token, int $attendeeId): void
    {
        $this->send('delete', 'apps/spreed/api/v4/room/'.rawurlencode($token).'/attendees', [
            'attendeeId' => $attendeeId,
        ]);
    }

    /**
     * Nextcloud-Benutzername zu einer E-Mail-Adresse (Provisioning-API, benötigt Admin-Rechte).
     * Nur ein eindeutiger, exakter Treffer zählt – sonst null.
     */
    public function findUserIdByEmail(string $email): ?string
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $response = $this->send('get', 'cloud/users/details', ['search' => $email, 'limit' => 25]);

        $matches = collect((array) $response->json('ocs.data.users', []))
            ->filter(fn ($u) => is_array($u) && mb_strtolower((string) ($u['email'] ?? '')) === $email)
            ->map(fn (array $u, $key) => (string) ($u['id'] ?? $key))
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    // ── Phase 3: Konten (Provisioning-API, benötigt Admin-Rechte) ───

    /** Gibt es ein Nextcloud-Konto mit diesem Benutzernamen? */
    public function userExists(string $userId): bool
    {
        $response = $this->send('get', 'cloud/users/'.rawurlencode($userId), [], [], [404]);

        return $response->status() !== 404;
    }

    /**
     * Konto ohne Passwort anlegen – Nextcloud verschickt dann selbst die Willkommensmail
     * mit dem Link zum Setzen des Passworts.
     */
    public function createUser(string $userId, string $displayName, string $email): void
    {
        $this->send('post', 'cloud/users', [
            'userid' => $userId,
            'password' => '',
            'displayName' => mb_substr($displayName, 0, 64),
            'email' => $email,
            'language' => 'de',
        ], [400 => 'Nextcloud hat das Konto „'.$userId.'“ nicht angelegt (Benutzername oder E-Mail ungültig bzw. vergeben).']);
    }

    public function addUserToGroup(string $userId, string $group): void
    {
        $this->send('post', 'cloud/users/'.rawurlencode($userId).'/groups', ['groupid' => $group], [
            400 => 'Die Nextcloud-Gruppe „'.$group.'“ gibt es nicht.',
            404 => 'Das Nextcloud-Konto „'.$userId.'“ wurde nicht gefunden.',
        ]);
    }

    public function enableUser(string $userId): void
    {
        $this->send('put', 'cloud/users/'.rawurlencode($userId).'/enable');
    }

    public function disableUser(string $userId): void
    {
        $this->send('put', 'cloud/users/'.rawurlencode($userId).'/disable', [], [], [404]);
    }

    /** Benutzername des technischen Kontos (wird beim Abgleich nie entfernt). */
    public function technicalUser(): string
    {
        return (string) $this->user;
    }

    /**
     * OCS-Aufruf mit verständlicher Fehlermeldung; wirft NextcloudTalkException.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $messages  Eigene Meldungen je HTTP-Status
     * @param  list<int>  $allowed  Status, die nicht als Fehler gelten (z. B. 404 bei Existenzprüfung)
     */
    private function send(string $method, string $path, array $data = [], array $messages = [], array $allowed = []): Response
    {
        if (! $this->isConfigured()) {
            throw new NextcloudTalkException('Nextcloud ist nicht konfiguriert.');
        }

        $action = self::describe($method, $path, $data);

        try {
            $request = $this->request();
            $response = $method === 'get'
                ? $request->get($this->ocs($path), $data)
                : $request->asJson()->{$method}($this->ocs($path), $data);
        } catch (ConnectionException) {
            $message = $action.': Nextcloud ist nicht erreichbar (Adresse prüfen oder Zeitüberschreitung).';
            $this->log($method, $path, $action, null, false, $message);

            throw new NextcloudTalkException($message);
        }

        if ($response->successful() || in_array($response->status(), $allowed, true)) {
            // Erfolgreiche Lesezugriffe nicht protokollieren (nur Änderungen und Fehler).
            if ($method !== 'get') {
                $this->log($method, $path, $action, $response->status(), true, null);
            }

            return $response;
        }

        $message = $action.': '.($messages[$response->status()] ?? $this->errorMessage($response, $path));
        $this->log($method, $path, $action, $response->status(), false, $message);

        throw new NextcloudTalkException($message);
    }

    /**
     * Raum-Token aus einem Talk-Link (…/call/{token}) oder direkt eingegebenem Token lesen.
     */
    public static function roomTokenFrom(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        if (preg_match('#/call/([A-Za-z0-9]+)#', $input, $m)) {
            return $m[1];
        }

        return preg_match('/^[A-Za-z0-9]{4,64}$/', $input) ? $input : null;
    }

    /** Vorbereitete Anfrage mit OCS-Kopfzeilen, JSON und Basic-Auth (App-Passwort). */
    private function request(): PendingRequest
    {
        return Http::withBasicAuth((string) $this->user, (string) $this->appPassword)
            ->withHeaders(['OCS-APIRequest' => 'true'])
            ->acceptJson()
            ->timeout($this->timeout);
    }

    /** Aktion einer Anfrage in Klartext (für Fehlermeldungen und das Talk-Protokoll). */
    public static function describe(string $method, string $path, array $data = []): string
    {
        $method = strtolower($method);
        $segments = explode('/', trim($path, '/'));
        $last = rawurldecode((string) end($segments));

        return match (true) {
            $path === 'cloud/users/details' => 'Konto per E-Mail suchen',
            $path === 'cloud/users' && $method === 'post' => 'Konto anlegen: '.($data['userid'] ?? ''),
            str_ends_with($path, '/groups') => 'Zur Gruppe hinzufügen: '.($data['groupid'] ?? ''),
            str_ends_with($path, '/enable') => 'Konto aktivieren: '.rawurldecode($segments[2] ?? ''),
            str_ends_with($path, '/disable') => 'Konto deaktivieren: '.rawurldecode($segments[2] ?? ''),
            str_starts_with($path, 'cloud/users/') => 'Benutzername prüfen: '.$last,
            str_contains($path, '/threads/') => 'Plot-Thread umbenennen',
            str_contains($path, '/chat/') => isset($data['threadTitle']) ? 'Plot-Thread anlegen: '.$data['threadTitle'] : 'Nachricht senden',
            str_ends_with($path, '/participants') && $method === 'post' => 'Teamer hinzufügen: '.($data['newParticipant'] ?? ''),
            str_ends_with($path, '/participants') => 'Teilnehmer abrufen',
            str_ends_with($path, '/attendees') => 'Teamer entfernen',
            $path === 'apps/spreed/api/v4/room' => 'Teamer-Raum anlegen: '.($data['roomName'] ?? ''),
            str_starts_with($path, 'apps/spreed/api/v4/room/') && $method === 'put' => 'Teamer-Raum umbenennen: '.($data['roomName'] ?? ''),
            default => strtoupper($method).' '.$path,
        };
    }

    /** Eintrag im Talk-Protokoll – darf die eigentliche Anfrage nie stören. */
    private function log(string $method, string $path, string $action, ?int $status, bool $success, ?string $message): void
    {
        try {
            TalkLog::create([
                'adventure_id' => $this->logAdventureId,
                'player_id' => $this->logPlayerId,
                'user_id' => auth()->id(),
                'action' => mb_substr(trim($action, ' :'), 0, 190),
                'method' => strtoupper($method),
                'endpoint' => mb_substr($path, 0, 255),
                'status' => $status,
                'success' => $success,
                'message' => $message !== null ? mb_substr($message, 0, 1000) : null,
            ]);
        } catch (Throwable) {
            // Protokoll ist Zusatz; Fehler beim Schreiben ignorieren.
        }
    }

    private function ocs(string $path): string
    {
        return $this->url.'/ocs/v2.php/'.ltrim($path, '/').'?format=json';
    }

    /**
     * Verständliche Fehlermeldung für eine fehlgeschlagene Antwort, sonst null.
     * Bei 403 mit Hinweis, welches Recht dem technischen Konto fehlt.
     */
    private function errorMessage(Response $response, string $path = ''): ?string
    {
        if ($response->successful()) {
            return null;
        }

        return match ($response->status()) {
            401 => 'Anmeldung abgelehnt: Benutzer oder App-Passwort ist falsch.',
            403 => match (true) {
                str_starts_with($path, 'cloud/users') => 'Zugriff verweigert: Das technische Konto braucht Admin-Rechte in Nextcloud (Gruppe „admin“).',
                str_starts_with($path, 'apps/spreed') => 'Zugriff verweigert: Das technische Konto muss Moderator in diesem Talk-Raum sein.',
                default => 'Zugriff verweigert: Das Konto hat nicht die nötigen Rechte.',
            },
            404 => 'Nicht gefunden: Ist die Nextcloud-Adresse richtig?',
            default => 'Nextcloud antwortet mit Fehler '.$response->status().'.',
        };
    }
}
