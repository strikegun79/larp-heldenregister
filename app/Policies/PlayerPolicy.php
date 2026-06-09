<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

/**
 * Ein Benutzer darf nur seine eigenen (zugeordneten) Spieler verwalten.
 * Admins dürfen alles – das regelt global Gate::before im AuthServiceProvider.
 */
class PlayerPolicy
{
    public function view(User $user, Player $player): bool
    {
        return $this->owns($user, $player);
    }

    public function update(User $user, Player $player): bool
    {
        return $this->owns($user, $player);
    }

    public function delete(User $user, Player $player): bool
    {
        return $this->owns($user, $player);
    }

    private function owns(User $user, Player $player): bool
    {
        return $user->players()->whereKey($player->getKey())->exists();
    }
}
