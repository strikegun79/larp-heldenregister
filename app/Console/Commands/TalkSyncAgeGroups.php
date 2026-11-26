<?php

namespace App\Console\Commands;

use App\Services\NextcloudTalk\PlayerTalkService;
use Illuminate\Console\Command;

/**
 * Talk: Konten täglich der Gruppe nach Alter zuordnen – am 18. Geburtstag aus der
 * U18-Gruppe in die Gruppe für volljährige Talk-Nutzer.
 */
class TalkSyncAgeGroups extends Command
{
    protected $signature = 'talk:sync-age-groups';

    protected $description = 'Talk-Konten der Nextcloud-Gruppe nach Alter zuordnen (U18 → volljährig)';

    public function handle(): int
    {
        $changed = PlayerTalkService::make()->syncAgeGroups();
        $this->info("Talk-Gruppen angepasst: {$changed}");

        return self::SUCCESS;
    }
}
