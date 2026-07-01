<?php

namespace App\Console\Commands;

use App\Models\Adventure;
use App\Models\EventStatus;
use App\Notifications\EventReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * NOTI-05: Erinnerungsmails an bestätigte Teilnehmer für Events, die innerhalb
 * der nächsten X Tage beginnen. Idempotent über `adventures.reminder_sent_at`.
 */
class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders {--days=3 : Vorlauf in Tagen}';

    protected $description = 'Sendet Event-Erinnerungen an bestätigte Teilnehmer (NOTI-05).';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $until = now()->addDays($days)->endOfDay();

        $events = Adventure::with('bookings.player.users')
            ->whereNull('reminder_sent_at')
            ->whereNotNull('start_at')
            ->where('start_at', '>=', now())
            ->where('start_at', '<=', $until)
            ->where('event_status_id', '!=', EventStatus::CANCELLED)
            ->get();

        $sent = 0;

        foreach ($events as $event) {
            foreach ($event->bookings as $booking) {
                // Nur bestätigte Anmeldungen mit hinterlegter E-Mail und aktivierter Benachrichtigung.
                if ($booking->status === 'bestaetigt' && $booking->player?->email
                    && $booking->player->notificationEnabled('notify_event_reminder')) {
                    Notification::route('mail', $booking->player->email)->notify(new EventReminder($event));
                    $sent++;
                }
            }
            // Markieren, damit nicht erneut versendet wird (Idempotenz).
            $event->update(['reminder_sent_at' => now()]);
        }

        $this->info("{$sent} Erinnerung(en) für {$events->count()} Event(s) versendet.");

        return self::SUCCESS;
    }
}
