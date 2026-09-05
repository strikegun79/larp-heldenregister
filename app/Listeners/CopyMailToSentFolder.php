<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

/**
 * Kopiert jede gesendete Mail per IMAP APPEND in den Sent-Ordner,
 * damit der Versand im Postfach nachvollzogen werden kann.
 * Zugangsdaten: MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD.
 * Ordner: IMAP_SENT_FOLDER (Standard: INBOX.Sent).
 */
class CopyMailToSentFolder
{
    public function handle(MessageSent $event): void
    {
        $host       = config('mail.mailers.smtp.host');
        $username   = config('mail.mailers.smtp.username');
        $password   = config('mail.mailers.smtp.password');
        $sentFolder = env('IMAP_SENT_FOLDER', 'INBOX.Sent');

        if (! $host || ! $username || ! $password) {
            return;
        }

        $mailbox = @imap_open(
            "{{$host}:993/imap/ssl/novalidate-cert}{$sentFolder}",
            $username,
            $password,
            OP_SILENT
        );

        if ($mailbox === false) {
            Log::warning('CopyMailToSentFolder: IMAP-Verbindung fehlgeschlagen', [
                'error' => imap_last_error(),
                'host'  => $host,
            ]);

            return;
        }

        try {
            $raw = $event->sent->toString();

            if (! imap_append($mailbox, "{{$host}:993/imap/ssl/novalidate-cert}{$sentFolder}", $raw, '\\Seen')) {
                Log::warning('CopyMailToSentFolder: imap_append fehlgeschlagen', [
                    'error' => imap_last_error(),
                ]);
            }
        } finally {
            imap_close($mailbox);
        }
    }
}
