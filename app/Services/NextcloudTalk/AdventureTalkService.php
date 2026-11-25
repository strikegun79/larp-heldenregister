<?php

namespace App\Services\NextcloudTalk;

use App\Jobs\SetupAdventureTalkJob;
use App\Jobs\SyncAdventureTalkTeamersJob;
use App\Models\Adventure;
use App\Models\Setting;
use App\Models\User;

/**
 * Issue #31 (Phase 2): Talk-Struktur eines Events – Plot-Thread im Plot-Team-Chat und privater
 * Teamer-Raum. Gespeicherte IDs verhindern Duplikate; Namensänderungen werden übernommen.
 * Fehler landen in adventures.talk_error (Anzeige im Reiter „Talk“), nie als Ausnahme nach außen.
 */
class AdventureTalkService
{
    public function __construct(private readonly NextcloudTalkClient $client) {}

    public static function make(): self
    {
        return new self(NextcloudTalkClient::fromConfig());
    }

    /** Einrichtung einplanen, wenn Talk aktiv ist und das Event etwas davon wünscht. */
    public static function queueSetup(Adventure $adventure): void
    {
        if (($adventure->talk_plot_thread || $adventure->talk_teamer_room) && NextcloudTalkClient::fromConfig()->isActive()) {
            SetupAdventureTalkJob::dispatch($adventure->id);
        }
    }

    /**
     * Teamer-Abgleich einplanen, wenn es einen Teamer-Raum gibt.
     *
     * @param  list<int>  $removedUserIds
     */
    public static function queueSync(Adventure $adventure, array $removedUserIds = []): void
    {
        if ($adventure->talk_teamer_room_token && NextcloudTalkClient::fromConfig()->isActive()) {
            SyncAdventureTalkTeamersJob::dispatch($adventure->id, $removedUserIds);
        }
    }

    /** Titel des Plot-Threads und Name des Teamer-Raums. */
    /** Thread-Titel mit „Plot:“ davor, damit er in der Thread-Liste als Teil des Plots erkennbar ist. */
    public static function threadTitle(Adventure $adventure): string
    {
        return mb_substr('Plot: '.$adventure->name, 0, 200);
    }

    /** Weichen Thread- oder Raumname in Talk vom gewünschten Namen ab (z. B. nach neuem Namensschema)? */
    public static function namesOutdated(Adventure $adventure): bool
    {
        return ($adventure->talk_plot_thread_id && $adventure->talk_plot_thread_title !== self::threadTitle($adventure))
            || ($adventure->talk_teamer_room_token && $adventure->talk_teamer_room_name !== self::roomName($adventure));
    }

    public static function roomName(Adventure $adventure): string
    {
        return mb_substr('Teamer: '.$adventure->name, 0, 255);
    }

    /**
     * Gewünschte Struktur einrichten bzw. umbenennen. Nach dem Anlegen des Teamer-Raums werden
     * die bestätigten Teamer direkt hinzugefügt.
     */
    public function setup(Adventure $adventure): void
    {
        $this->client->forContext(adventure: $adventure);
        if (! $this->client->isActive()) {
            return;
        }

        $errors = [];

        if ($adventure->talk_plot_thread) {
            try {
                $this->setupPlotThread($adventure);
            } catch (NextcloudTalkException $e) {
                $errors[] = 'Plot-Thread: '.$e->getMessage();
            }
        }

        if ($adventure->talk_teamer_room) {
            try {
                if ($this->setupTeamerRoom($adventure)) {
                    $errors = array_merge($errors, $this->syncErrors($adventure, []));
                }
            } catch (NextcloudTalkException $e) {
                $errors[] = 'Teamer-Raum: '.$e->getMessage();
            }
        }

        $this->saveError($adventure, $errors);
    }

    /**
     * Teamer-Raum abgleichen: bestätigte Teamer hinzufügen, Teamer mit zurückgenommener oder
     * abgelehnter Anmeldung entfernen. Andere Raummitglieder (von Hand hinzugefügt) bleiben.
     *
     * @param  list<int>  $removedUserIds  Nutzer, deren Anmeldung gelöscht wurde
     */
    public function syncTeamers(Adventure $adventure, array $removedUserIds = []): void
    {
        $this->client->forContext(adventure: $adventure);
        if (! $this->client->isActive() || ! $adventure->talk_teamer_room_token) {
            return;
        }

        $this->saveError($adventure, $this->syncErrors($adventure, $removedUserIds));
    }

    /**
     * Abgleich durchführen; zurück kommen die Fehlermeldungen.
     *
     * @param  list<int>  $removedUserIds
     * @return list<string>
     */
    private function syncErrors(Adventure $adventure, array $removedUserIds): array
    {
        $token = (string) $adventure->talk_teamer_room_token;
        $errors = [];

        try {
            $members = collect($this->client->participants($token))
                ->where('actorType', 'users')
                ->keyBy('actorId');

            $signups = $adventure->teamerSignups()->with('user')->get();
            $approvedUserIds = $signups->whereNotNull('approved_at')->pluck('user_id')->all();

            $toRemove = $signups->whereNull('approved_at')->pluck('user')->filter();
            if ($removedUserIds) {
                $toRemove = $toRemove->merge(
                    User::whereIn('id', array_diff($removedUserIds, $approvedUserIds))->get()
                );
            }

            foreach ($signups->whereNotNull('approved_at') as $signup) {
                if (! $signup->user) {
                    continue;
                }
                try {
                    $ncId = $this->nextcloudUserFor($signup->user);
                    if ($ncId !== null && ! $members->has($ncId)) {
                        $this->client->addUser($token, $ncId);
                    }
                } catch (NextcloudTalkException $e) {
                    $errors[] = $signup->user->name.': '.$e->getMessage();
                }
            }

            foreach ($toRemove->unique('id') as $user) {
                $ncId = $user->nextcloud_user_id;
                if ($ncId === null || $ncId === $this->client->technicalUser() || ! $members->has($ncId)) {
                    continue;
                }
                try {
                    $this->client->removeAttendee($token, $members[$ncId]['attendeeId']);
                } catch (NextcloudTalkException $e) {
                    $errors[] = $user->name.': '.$e->getMessage();
                }
            }
        } catch (NextcloudTalkException $e) {
            $errors[] = $e->getMessage();
        }

        return array_map(fn ($e) => 'Teamer-Abgleich: '.$e, $errors);
    }

    /** @param  list<string>  $errors */
    private function saveError(Adventure $adventure, array $errors): void
    {
        $adventure->talk_error = $errors ? mb_substr(implode(' ', $errors), 0, 500) : null;
        $adventure->save();
    }

    /**
     * Nextcloud-Konto eines Nutzers: gespeichert oder per E-Mail gesucht (eindeutiger Treffer
     * wird gespeichert). null = kein Konto gefunden.
     */
    public function nextcloudUserFor(User $user): ?string
    {
        if (filled($user->nextcloud_user_id)) {
            return $user->nextcloud_user_id;
        }

        $found = $this->client->findUserIdByEmail((string) $user->email);
        if ($found !== null) {
            $user->nextcloud_user_id = $found;
            $user->save();
        }

        return $found;
    }

    private function setupPlotThread(Adventure $adventure): void
    {
        $plotToken = Setting::get('talk_plot_room_token');
        if (! $plotToken) {
            throw new NextcloudTalkException('In der Verwaltung ist kein Plot-Team-Chat hinterlegt.');
        }

        $title = self::threadTitle($adventure);

        if (! $adventure->talk_plot_thread_id) {
            $adventure->talk_plot_thread_id = $this->client->createThread($plotToken, $title, $this->threadMessage($adventure));
            $adventure->talk_plot_thread_title = $title;
            $adventure->save();
        } elseif ($adventure->talk_plot_thread_title !== $title) {
            $this->client->renameThread($plotToken, (int) $adventure->talk_plot_thread_id, $title);
            $adventure->talk_plot_thread_title = $title;
            $adventure->save();
        }
    }

    /** @return bool true, wenn der Raum neu angelegt wurde */
    private function setupTeamerRoom(Adventure $adventure): bool
    {
        $name = self::roomName($adventure);

        if (! $adventure->talk_teamer_room_token) {
            $adventure->talk_teamer_room_token = $this->client->createGroupRoom($name);
            $adventure->talk_teamer_room_name = $name;
            $adventure->save();

            return true;
        }

        if ($adventure->talk_teamer_room_name !== $name) {
            $this->client->renameRoom($adventure->talk_teamer_room_token, $name);
            $adventure->talk_teamer_room_name = $name;
            $adventure->save();
        }

        return false;
    }

    private function threadMessage(Adventure $adventure): string
    {
        $start = $adventure->start_at->format('d.m.Y');
        $end = $adventure->end_at->format('d.m.Y');
        $date = $end !== $start ? $start.' – '.$end : $start;

        return 'Plot-Thread für „'.$adventure->name.'“ ('.$date.'). Angelegt vom Heldenregister.';
    }
}
