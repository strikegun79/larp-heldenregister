<?php

namespace App\Jobs;

use App\Models\Player;
use App\Services\NextcloudTalk\PlayerTalkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * Issue #31 (Phase 3): Nextcloud-Konto eines Spielers einrichten oder deaktivieren.
 * Läuft nie parallel für denselben Spieler (kein doppelt angelegtes Konto).
 */
class PlayerTalkAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const PROVISION = 'provision';

    public const DISABLE = 'disable';

    /** Neues Konto unter dem nächsten freien Benutzernamen anlegen (vorhandener Name gehört jemand anderem). */
    public const PROVISION_NEW_NAME = 'provision_new_name';

    public int $tries = 5;

    public function __construct(public readonly int $playerId, public readonly string $action) {}

    /** @return list<object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('talk-player-'.$this->playerId))->releaseAfter(30)->expireAfter(300)];
    }

    public function handle(): void
    {
        // Issue #42: Deaktivieren auch für gelöschte Spieler (Konto beim Löschen sperren).
        $player = $this->action === self::DISABLE
            ? Player::withTrashed()->find($this->playerId)
            : Player::find($this->playerId);
        if (! $player) {
            return;
        }

        $service = PlayerTalkService::make();
        match ($this->action) {
            self::DISABLE => $service->disable($player),
            self::PROVISION_NEW_NAME => $service->provision($player, newName: true),
            default => $service->provision($player),
        };
    }
}
