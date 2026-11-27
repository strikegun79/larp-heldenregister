<?php

namespace App\Services\NextcloudTalk;

use App\Jobs\PlayerTalkAccountJob;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\TalkAccessApproved;
use App\Notifications\TalkAccessEnded;
use App\Notifications\TalkAccountReady;
use Illuminate\Support\Facades\Notification;

/**
 * Issue #31 (Phase 3): Nextcloud-Konto eines Spielers einrichten bzw. deaktivieren.
 * Ein verknüpftes Konto wird weiterverwendet, sonst per E-Mail gesucht, sonst neu angelegt
 * (Nextcloud verschickt die Willkommensmail). U18-Konten kommen in die U18-Gruppe.
 * Benachrichtigungen (Freigabe, Konto fertig, Ende) gehen an die verknüpften Nutzer.
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
     * Freigabe bzw. Erlaubnis zurücknehmen: Talk-Status zurücksetzen, Konto deaktivieren und die
     * Nutzer (Eltern bzw. Spieler selbst) benachrichtigen. Die Kontoverknüpfung bleibt, damit eine
     * erneute Freigabe dasselbe Konto nutzt.
     *
     * @param  TalkAccessEnded::REVOKED|TalkAccessEnded::WITHDRAWN  $reason
     */
    public static function revoke(Player $player, string $reason = TalkAccessEnded::REVOKED, bool $notify = true): void
    {
        $wasApproved = $player->talk_approved_at !== null;

        $player->talk_approved_at = null;
        $player->talk_approved_by = null;
        $player->talk_consent_at = null;
        $player->talk_consent_by = null;
        $player->talk_consent_version = null;
        $player->talk_account_ready_at = null;
        $player->talk_error = null;
        $player->save();

        self::queueDisable($player);

        if ($wasApproved && $notify) {
            Notification::send($player->users, new TalkAccessEnded($player, $reason));
        }
    }

    /** Nach der Freigabe: Nutzer benachrichtigen (bei U18 mit Bitte um Erlaubnis). */
    public static function notifyApproved(Player $player): void
    {
        Notification::send($player->users, new TalkAccessApproved($player));
    }

    /**
     * Wartet der Spieler auf sein Konto (z. B. weil die E-Mail fehlte)? Dann erneut einplanen –
     * etwa nach dem Speichern des Spielerprofils.
     */
    public static function retryIfWaiting(Player $player): void
    {
        if ($player->talkReadyForAccount() && blank($player->nextcloud_user_id)) {
            self::queueProvision($player);
        }
    }

    /** Neues Konto unter dem nächsten freien Namen einplanen (vorhandener Name gehört jemand anderem). */
    public static function queueProvisionWithNewName(Player $player): void
    {
        if ($player->talkReadyForAccount() && NextcloudTalkClient::fromConfig()->isActive()) {
            PlayerTalkAccountJob::dispatch($player->id, PlayerTalkAccountJob::PROVISION_NEW_NAME);
        }
    }

    /** Text bei vergebenem Benutzernamen – nennt die Auswege. */
    public const USERNAME_TAKEN_HINT = 'Gehört das Konto derselben Person: in Nextcloud in die TALK-Gruppe aufnehmen und „Erneut versuchen“ '
        .'oder hier „Mit vorhandenem Konto verknüpfen“. Sonst „Neues Konto mit anderem Namen anlegen“.';

    public function provision(Player $player, bool $newName = false): void
    {
        $this->client->forContext(player: $player);
        if (! $this->client->isActive() || ! $player->talkReadyForAccount()) {
            return;
        }

        try {
            $group = self::targetGroup($player);

            // In Nextcloud gelöschtes Konto: Verknüpfung lösen und neu einrichten.
            if (filled($player->nextcloud_user_id) && ! $this->client->userExists($player->nextcloud_user_id)) {
                $this->forgetDeletedAccount($player);
            }

            if (blank($player->nextcloud_user_id)) {
                $this->linkOrCreate($player, $group, $newName);
            } elseif ($player->nextcloud_account_created) {
                // Nach einer Rücknahme deaktiviertes Konto wieder einschalten.
                $this->client->enableUser($player->nextcloud_user_id);
            }

            $this->applyGroups($player, $group);

            $newlyReady = $player->talk_account_ready_at === null;
            $player->talk_account_ready_at = now();
            $player->talk_error = null;
            $player->save();

            if ($newlyReady) {
                Notification::send($player->users, new TalkAccountReady($player, self::accountEmail($player)));
            }

            return;
        } catch (NextcloudUserExistsException $e) {
            $player->talk_error = mb_substr($e->getMessage().' '.self::USERNAME_TAKEN_HINT, 0, 500);
        } catch (NextcloudTalkException $e) {
            $player->talk_error = mb_substr($e->getMessage(), 0, 500);
        }

        $player->save();
    }

    /**
     * Spieler bewusst mit einem vorhandenen Nextcloud-Konto verknüpfen (Eingabe in den Talk-Freigaben).
     * Das Konto muss für das technische Konto sichtbar sein (in einer TALK-Gruppe) und darf keinem
     * anderen Spieler gehören.
     */
    public function linkExisting(Player $player, string $userId): void
    {
        $userId = trim($userId);
        $this->client->forContext(player: $player);

        $other = Player::withTrashed()->where('nextcloud_user_id', $userId)->whereKeyNot($player->id)->first();
        if ($other) {
            throw new NextcloudTalkException('Das Nextcloud-Konto „'.$userId.'“ ist bereits mit '.$other->full_name.' verknüpft.');
        }
        if (! $this->client->userExists($userId)) {
            throw new NextcloudTalkException('Das Nextcloud-Konto „'.$userId.'“ gibt es nicht oder es ist in keiner TALK-Gruppe '
                .'(das technische Konto sieht nur Konten in den Talk-Gruppen). In Nextcloud zuerst in die passende Gruppe aufnehmen.');
        }

        $this->client->note('Mit vorhandenem Konto verknüpft: '.$userId);
        $player->nextcloud_user_id = $userId;
        $player->nextcloud_account_created = false;
        $player->nextcloud_group = null;
        $player->talk_error = null;
        $player->save();
    }

    /**
     * Gruppe nach Alter: Minderjährige in die U18-Gruppe, Volljährige in die Gruppe für
     * volljährige Talk-Nutzer. Nur Gruppen mit „TALK-“ am Anfang sind erlaubt.
     */
    public static function targetGroup(Player $player): string
    {
        $minor = $player->isTalkMinor();
        $group = trim((string) Setting::get($minor ? 'talk_u18_group' : 'talk_user_group'));

        if ($group === '') {
            throw new NextcloudTalkException($minor
                ? 'In der Verwaltung ist keine Nextcloud-Gruppe für U18-Konten hinterlegt.'
                : 'In der Verwaltung ist keine Nextcloud-Gruppe für volljährige Talk-Nutzer hinterlegt.');
        }
        if (! NextcloudTalkClient::isAllowedGroup($group)) {
            throw new NextcloudTalkException('Die Gruppe „'.$group.'“ ist nicht erlaubt (nur Gruppen, die mit „'.NextcloudTalkClient::GROUP_PREFIX.'“ beginnen).');
        }

        return $group;
    }

    /**
     * Konto in die Gruppe nach Alter aufnehmen; Volljährige zusätzlich aus der U18-Gruppe nehmen
     * (Wechsel am 18. Geburtstag). Merkt sich die vergebene Gruppe am Spieler.
     */
    private function applyGroups(Player $player, string $group): void
    {
        $userId = (string) $player->nextcloud_user_id;
        $this->client->addUserToGroup($userId, $group);

        $u18 = trim((string) Setting::get('talk_u18_group'));
        if (! $player->isTalkMinor() && $u18 !== '' && $u18 !== $group
            && in_array($u18, $this->client->userGroups($userId), true)) {
            $this->client->removeUserFromGroup($userId, $u18);
        }

        $player->nextcloud_group = $group;
    }

    /**
     * Täglicher Abgleich: Konten, deren Gruppe nicht (mehr) zum Alter passt – z. B. am
     * 18. Geburtstag von der U18- in die Volljährigen-Gruppe. Liefert die Zahl der Wechsel.
     */
    public function syncAgeGroups(): int
    {
        if (! $this->client->isActive()) {
            return 0;
        }

        $changed = 0;
        Player::whereNotNull('talk_account_ready_at')
            ->whereNotNull('nextcloud_user_id')
            ->chunkById(100, function ($players) use (&$changed) {
                foreach ($players as $player) {
                    try {
                        $group = self::targetGroup($player);
                    } catch (NextcloudTalkException) {
                        continue;
                    }
                    if ($player->nextcloud_group === $group) {
                        continue;
                    }

                    $this->client->forContext(player: $player);
                    try {
                        // Konto in Nextcloud gelöscht → nicht täglich denselben Fehler melden, sondern lösen.
                        if (! $this->client->userExists((string) $player->nextcloud_user_id)) {
                            $this->forgetDeletedAccount($player);
                            $player->talk_error = 'Das Nextcloud-Konto wurde gelöscht. Über „Erneut versuchen“ wird es neu eingerichtet.';
                            $player->save();

                            continue;
                        }
                        $this->applyGroups($player, $group);
                        $player->talk_error = null;
                        $changed++;
                    } catch (NextcloudTalkException $e) {
                        $player->talk_error = mb_substr($e->getMessage(), 0, 500);
                    }
                    $player->save();
                }
            });

        return $changed;
    }

    /**
     * Verknüpfung mit dem Nextcloud-Konto lösen (nur im Heldenregister, in Nextcloud wird nichts
     * gelöscht). Danach wird beim nächsten Einrichten erneut gesucht bzw. ein Konto angelegt.
     */
    public static function unlink(Player $player): void
    {
        $player->nextcloud_user_id = null;
        $player->nextcloud_account_created = false;
        $player->nextcloud_group = null;
        $player->talk_account_ready_at = null;
        $player->talk_error = null;
        $player->save();
    }

    private function forgetDeletedAccount(Player $player): void
    {
        $this->client->note('Konto existiert nicht mehr: '.$player->nextcloud_user_id.' – Verknüpfung gelöst');
        self::unlink($player);
    }

    public function disable(Player $player): void
    {
        $this->client->forContext(player: $player);
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

    /**
     * E-Mail für das Konto: beim Spieler „ich selbst“ die des Nutzerkontos, bei verwalteten
     * Spielern (z. B. Kindern) nur die optionale E-Mail im Spielerprofil – nie die der Eltern.
     */
    public static function accountEmail(Player $player): ?string
    {
        $self = self::selfUser($player);
        $email = $self ? $self->email : $player->email;

        return filled($email) ? (string) $email : null;
    }

    /** Fehlt die E-Mail für das Talk-Konto? (Hinweis im Reiter „Talk“) */
    public static function missingAccountEmail(Player $player): bool
    {
        return self::accountEmail($player) === null;
    }

    /** Nutzer, der mit dem Spieler als „ich selbst“ verknüpft ist. */
    public static function selfUser(Player $player): ?User
    {
        return $player->users()->wherePivot('self', true)->first();
    }

    private function linkOrCreate(Player $player, string $group, bool $newName = false): void
    {
        $email = self::accountEmail($player);

        // Bei „anderem Namen“ nicht per E-Mail verknüpfen – es soll ausdrücklich ein neues Konto werden.
        $found = $email && ! $newName ? $this->client->findUserIdByEmail($email) : null;
        if ($found !== null) {
            $player->nextcloud_user_id = $found;
            $player->nextcloud_account_created = false;
            $player->save();

            return;
        }

        if ($email === null) {
            throw new NextcloudTalkException(self::selfUser($player)
                ? 'Das Nutzerkonto des Spielers hat keine E-Mail-Adresse.'
                : 'Beim Spieler ist keine E-Mail-Adresse hinterlegt (Spielerprofil → „E-Mail (optional)“).');
        }

        $userId = $this->uniqueUserId($player);
        if ($newName) {
            // Nicht sichtbare, aber vergebene Namen überspringen: name2, name3 …
            $base = preg_replace('/\d+$/', '', $userId);
            for ($i = 2; ; $i++) {
                try {
                    $this->client->createUser($userId, $player->full_name, $email, $group);
                    break;
                } catch (NextcloudUserExistsException $e) {
                    if ($i > 50) {
                        throw $e;
                    }
                    $userId = $base.$i;
                }
            }
        } else {
            $this->client->createUser($userId, $player->full_name, $email, $group);
        }

        $player->nextcloud_user_id = $userId;
        $player->nextcloud_account_created = true;
        $player->save();
    }

    /** vorname.nachname, bei Kollision mit angehängter Zahl (vorname.nachname2 …). */
    private function uniqueUserId(Player $player): string
    {
        $base = trim(Player::sanitizeUsernamePart((string) $player->name).'.'.Player::sanitizeUsernamePart((string) $player->lastname), '.');
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
