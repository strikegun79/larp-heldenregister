<?php

namespace App\Services\NextcloudTalk;

use App\Jobs\PlayerTalkAccountJob;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;

/**
 * Issue #31 (Phase 3): Nextcloud-Konto eines Spielers einrichten bzw. deaktivieren.
 * Ein verknüpftes Konto wird weiterverwendet, sonst per E-Mail gesucht, sonst neu angelegt
 * (Nextcloud verschickt die Willkommensmail). U18-Konten kommen in die U18-Gruppe.
 * Fehler landen in players.talk_error (Anzeige), nie als Ausnahme nach außen.
 */
class PlayerTalkService
{
    public function __construct(private readonly NextcloudTalkClient $client) {}

    public static function make(): self
    {
        return new self(NextcloudTalkClient::fromConfig());
    }

    /** Einrichtung einplanen, sobald Freigabe und (bei U18) Elternerlaubnis vorliegen. */
    public static function queueProvision(Player $player): void
    {
        if ($player->talkReadyForAccount() && NextcloudTalkClient::fromConfig()->isActive()) {
            PlayerTalkAccountJob::dispatch($player->id, PlayerTalkAccountJob::PROVISION);
        }
    }

    /** Deaktivierung einplanen (nur für vom Heldenregister angelegte Konten). */
    public static function queueDisable(Player $player): void
    {
        if ($player->nextcloud_user_id && $player->nextcloud_account_created && NextcloudTalkClient::fromConfig()->isActive()) {
            PlayerTalkAccountJob::dispatch($player->id, PlayerTalkAccountJob::DISABLE);
        }
    }

    /**
     * Freigabe bzw. Erlaubnis zurücknehmen: Talk-Status zurücksetzen, Konto deaktivieren.
     * Die Kontoverknüpfung bleibt, damit eine erneute Freigabe dasselbe Konto nutzt.
     */
    public static function revoke(Player $player): void
    {
        $player->talk_approved_at = null;
        $player->talk_approved_by = null;
        $player->talk_consent_at = null;
        $player->talk_consent_by = null;
        $player->talk_consent_version = null;
        $player->talk_account_ready_at = null;
        $player->talk_error = null;
        $player->save();

        self::queueDisable($player);
    }

    public function provision(Player $player): void
    {
        if (! $this->client->isActive() || ! $player->talkReadyForAccount()) {
            return;
        }

        try {
            $group = Setting::get('talk_u18_group');
            if ($player->isTalkMinor() && blank($group)) {
                throw new NextcloudTalkException('In der Verwaltung ist keine Nextcloud-Gruppe für U18-Konten hinterlegt.');
            }

            if (blank($player->nextcloud_user_id)) {
                $this->linkOrCreate($player);
            } elseif ($player->nextcloud_account_created) {
                // Nach einer Rücknahme deaktiviertes Konto wieder einschalten.
                $this->client->enableUser($player->nextcloud_user_id);
            }

            if ($player->isTalkMinor()) {
                $this->client->addUserToGroup((string) $player->nextcloud_user_id, (string) $group);
            }

            $player->talk_account_ready_at = now();
            $player->talk_error = null;
        } catch (NextcloudTalkException $e) {
            $player->talk_error = mb_substr($e->getMessage(), 0, 500);
        }

        $player->save();
    }

    public function disable(Player $player): void
    {
        if (! $this->client->isActive() || ! $player->nextcloud_user_id || ! $player->nextcloud_account_created) {
            return;
        }
        // Inzwischen erneut freigegeben → nicht deaktivieren.
        if ($player->talk_approved_at !== null) {
            return;
        }

        try {
            $this->client->disableUser($player->nextcloud_user_id);
        } catch (NextcloudTalkException $e) {
            $player->talk_error = mb_substr('Deaktivieren: '.$e->getMessage(), 0, 500);
            $player->save();
        }
    }

    /** E-Mail für das Konto: die des Spielers, sonst des zustimmenden Elternteils bzw. des eigenen Nutzerkontos. */
    public static function accountEmail(Player $player): ?string
    {
        if (filled($player->email)) {
            return (string) $player->email;
        }

        if ($player->talk_consent_by && ($guardian = User::find($player->talk_consent_by)) && filled($guardian->email)) {
            return (string) $guardian->email;
        }

        $self = $player->users()->wherePivot('self', true)->first();

        return $self && filled($self->email) ? (string) $self->email : null;
    }

    private function linkOrCreate(Player $player): void
    {
        $email = self::accountEmail($player);

        $found = $email ? $this->client->findUserIdByEmail($email) : null;
        if ($found !== null) {
            $player->nextcloud_user_id = $found;
            $player->nextcloud_account_created = false;
            $player->save();

            return;
        }

        if ($email === null) {
            throw new NextcloudTalkException('Für das Konto fehlt eine E-Mail-Adresse (beim Spieler oder bei den Eltern).');
        }

        $userId = $this->uniqueUserId($player);
        $this->client->createUser($userId, $player->full_name, $email);

        $player->nextcloud_user_id = $userId;
        $player->nextcloud_account_created = true;
        $player->save();
    }

    /** vorname.nachname, bei Kollision mit angehängter Zahl (vorname.nachname2 …). */
    private function uniqueUserId(Player $player): string
    {
        $base = trim(Player::sanitizeMxidLocalpart((string) $player->name).'.'.Player::sanitizeMxidLocalpart((string) $player->lastname), '.');
        $base = mb_substr($base !== '' ? $base : 'spieler'.$player->id, 0, 56);

        $candidate = $base;
        for ($i = 2; $this->client->userExists($candidate); $i++) {
            if ($i > 50) {
                throw new NextcloudTalkException('Kein freier Nextcloud-Benutzername für „'.$base.'“ gefunden.');
            }
            $candidate = $base.$i;
        }

        return $candidate;
    }
}
