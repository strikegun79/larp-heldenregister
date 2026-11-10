<?php

namespace App\Mail;

use App\Models\SurveyLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SurveyLink $link) {}

    public function envelope(): Envelope
    {
        $eventName = $link->survey->adventure->name ?? 'unserem Event';

        return new Envelope(
            subject: "Dein Feedback zu: {$eventName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.survey-invitation',
            with: [
                'link'      => $this->link,
                'survey'    => $this->link->survey,
                'adventure' => $this->link->survey->adventure,
                'surveyUrl' => $this->link->url,
            ],
        );
    }
}
