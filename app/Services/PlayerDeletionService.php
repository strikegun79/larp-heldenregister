<?php

namespace App\Services;

use App\Models\Player;
use App\Services\NextcloudTalk\PlayerTalkService;

/**
 * Issue #42: Spieler löschen (Nutzer und Verwaltung).
 * Soft-Delete; die Helden werden über den Hero-Scope ausgeblendet.
 * Nach Player::ANONYMIZE_AFTER_DAYS anonymisiert `dsgvo:prune` die Klardaten.
 */
class PlayerDeletionService
{
    public static function delete(Player $player): void
    {
        // Talk-Zugang beenden: ein angelegtes Nextcloud-Konto wird deaktiviert (ohne extra Mail).
        if ($player->talk_approved_at !== null || filled($player->nextcloud_user_id)) {
            PlayerTalkService::revoke($player, notify: false);
        }

        $player->delete();
    }
}
