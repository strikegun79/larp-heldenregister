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
 * Issue #31 (Phase 2): Plot-Thread und Teamer-Raum eines Events einrichten bzw. umbenennen.
 * Läuft nie parallel zu einem anderen Talk-Job desselben Events (keine Duplikate).
 */
class SetupAdventureTalkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public readonly int $adventureId) {}

    /** @return list<object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('talk-adventure-'.$this->adventureId))->releaseAfter(30)->expireAfter(300)];
    }

    public function handle(): void
    {
        $adventure = Adventure::find($this->adventureId);
        if ($adventure) {
            AdventureTalkService::make()->setup($adventure);
        }
    }
}
