<?php

namespace App\Jobs;

use App\Models\Adventure;
use App\Services\NextcloudTalk\AdventureTalkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * Issue #31 (Phase 2): Teamer-Raum eines Events mit den bestätigten Teamern abgleichen.
 */
class SyncAdventureTalkTeamersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @param  list<int>  $removedUserIds  Nutzer, deren Teamer-Anmeldung gelöscht wurde */
    public function __construct(public readonly int $adventureId, public readonly array $removedUserIds = []) {}

    /** @return list<object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('talk-adventure-'.$this->adventureId))->releaseAfter(30)->expireAfter(300)];
    }

    public function handle(): void
    {
        $adventure = Adventure::find($this->adventureId);
        if ($adventure) {
            AdventureTalkService::make()->syncTeamers($adventure, $this->removedUserIds);
        }
    }
}
