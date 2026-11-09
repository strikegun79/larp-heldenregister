<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;

/**
 * Schickt allen Admins eine Benachrichtigung über eine neue Registrierung.
 * Ersetzt den Admin-Mailversand aus dem Legacy register/index.php.
 */
class NotifyAdminsOfNewUser
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $admins = User::whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->get();

        Notification::send($admins, new NewUserRegistered($user));
    }
}
