<?php

namespace App\Notifications;

use App\Models\TeamerSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Teamer-Anmeldung (teamer_signups) bestätigt – gleiche Mail wie TeamerBookingConfirmed. */
class TeamerApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly TeamerSignup $signup) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->teamer_notifications ?? true) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $signup = $this->signup->loadMissing(['adventure', 'eventRole']);

        return (new MailMessage)
            ->subject('Teamer-Anmeldung bestätigt: '.$signup->adventure?->name)
            ->markdown('emails.teamer_booking_confirmed', [
                'playerName'    => trim($notifiable->name.' '.($notifiable->lastname ?? '')),
                'adventureName' => $signup->adventure?->name ?? '—',
                'roleName'      => $signup->eventRole?->description ?? 'Teamer',
                'startDate'     => optional($signup->adventure?->start_at)->format('d.m.Y'),
                'endDate'       => optional($signup->adventure?->end_at)->format('d.m.Y'),
                'dashboardUrl'  => route('dashboard'),
            ]);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $signup = $this->signup;

        return [
            'adventure_id'   => $signup->adventure_id,
            'adventure_name' => $signup->adventure?->name,
            'start_at'       => optional($signup->adventure?->start_at)->toDateString(),
            'message'        => 'Deine Teamer-Anmeldung für „'.($signup->adventure?->name ?? '—').'" wurde bestätigt.',
            'url'            => route('adventures.show', $signup->adventure_id),
        ];
    }
}
