<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\NewsletterSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Newsletter $newsletter,
        public readonly NewsletterSend $send,
    ) {
        $this->onQueue('newsletter');
    }

    /** Rate-Limit: max. NEWSLETTER_EMAILS_PER_HOUR Mails/Stunde. */
    public function middleware(): array
    {
        return [new RateLimited('newsletter-versand')];
    }

    /** Job bis zu 72 Stunden wiederholen, damit auch große Empfängerlisten vollständig versendet werden. */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(72);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name') . ' – ' . $this->newsletter->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter',
            with: [
                'bodyHtml'       => $this->absoluteUrls($this->newsletter->body_html),
                'unsubscribeUrl' => $this->send->unsubscribe_url,
            ],
        );
    }

    /** Relative src/href-Attribute in absolute URLs umwandeln (für E-Mail-Clients). */
    private function absoluteUrls(string $html): string
    {
        $base = rtrim(config('app.url'), '/');

        return preg_replace_callback(
            '/\b(src|href)="(\/[^"]+)"/i',
            fn ($m) => "{$m[1]}=\"{$base}{$m[2]}\"",
            $html
        );
    }
}
