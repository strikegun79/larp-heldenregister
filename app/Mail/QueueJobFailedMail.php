<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Events\JobFailed;

// Nicht queued – direkte Zustellung, da der Queue selbst fehlerhaft sein kann.
class QueueJobFailedMail extends Mailable
{
    public string $jobName;
    public string $queueName;
    public string $exceptionMessage;
    public string $failedAt;

    public function __construct(JobFailed $event)
    {
        $this->jobName          = $event->job->getName();
        $this->queueName        = $event->job->getQueue();
        $this->exceptionMessage = $event->exception->getMessage();
        $this->failedAt         = now()->format('d.m.Y H:i:s');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] Queue-Job fehlgeschlagen: '.$this->jobName,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.queue-job-failed',
        );
    }
}
