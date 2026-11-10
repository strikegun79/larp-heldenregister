<?php

namespace App\Notifications;

use App\Models\Adventure;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Info an die Projektleitung: Ein Nutzer möchte eine Stornierung rückgängig machen.
 */
class BookingReinstateRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Adventure $adventure,
        private readonly Booking $booking,
        private readonly string $participantName,
        private readonly string $requestedByName,
        private readonly string $message,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approveUrl = URL::signedRoute('adventures.bookings.approve-reinstate', [
            'adventure' => $this->adventure->id,
            'booking'   => $this->booking->id,
        ]);
        $rejectUrl = URL::signedRoute('adventures.bookings.reject-reinstate', [
            'adventure' => $this->adventure->id,
            'booking'   => $this->booking->id,
        ]);
        $manageUrl = route('adventures.manage', $this->adventure->id);

        return (new MailMessage)
            ->subject('Anfrage zur Rücknahme einer Stornierung: '.$this->adventure->name)
            ->line('**'.$this->requestedByName.'** bittet darum, die Stornierung der Anmeldung von **'.$this->participantName.'** für die Veranstaltung **„'.$this->adventure->name.'"** rückgängig zu machen.')
            ->line('**Nachricht des Nutzers:**')
            ->line($this->message)
            ->action('Stornierung genehmigen', $approveUrl)
            ->line('[Stornierung ablehnen]('.$rejectUrl.')')
            ->line('---')
            ->action('Zur Veranstaltungsverwaltung', $manageUrl);
    }
}
