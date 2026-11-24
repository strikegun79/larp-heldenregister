<?php

namespace App\Services\NextcloudTalk;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Issue #31: Schlanker Client für die OCS-API von Nextcloud (Talk = App „spreed“).
 * Phase 1: Verbindungstest. Räume, Threads, Teilnehmer und Konten folgen in Phase 2/3.
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

    private function ocs(string $path): string
    {
        return $this->url.'/ocs/v2.php/'.ltrim($path, '/').'?format=json';
    }

    /** Verständliche Fehlermeldung für eine fehlgeschlagene Antwort, sonst null. */
    private function errorMessage(Response $response): ?string
    {
        if ($response->successful()) {
            return null;
        }

        return match ($response->status()) {
            401 => 'Anmeldung abgelehnt: Benutzer oder App-Passwort ist falsch.',
            403 => 'Zugriff verweigert: Das Konto hat nicht die nötigen Rechte.',
            404 => 'Nicht gefunden: Ist die Nextcloud-Adresse richtig?',
            default => 'Nextcloud antwortet mit Fehler '.$response->status().'.',
        };
    }
}
