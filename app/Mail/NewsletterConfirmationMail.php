<?php

namespace App\Mail;

use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly NewsletterSubscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Newsletter bestätigen – Waldritter Gießen',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter-confirmation',
            with: [
                'confirmUrl' => $this->subscription->confirm_url,
            ],
        );
    }
}
