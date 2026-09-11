<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly Newsletter $newsletter) {}

    public function failed(Throwable $e): void
    {
        Log::error('Newsletter-Versand fehlgeschlagen', [
            'newsletter_id'    => $this->newsletter->id,
            'newsletter_title' => $this->newsletter->title,
            'exception'        => $e->getMessage(),
        ]);
    }

    public function handle(): void
    {
        // Doppelversand verhindern: abbrechen wenn bereits E-Mails verschickt wurden
        if ($this->newsletter->sends()->exists()) {
            return;
        }

        NewsletterSubscription::whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->chunkById(100, function ($subscribers) {
                foreach ($subscribers as $subscription) {
                    $send = NewsletterSend::create([
                        'newsletter_id'   => $this->newsletter->id,
                        'subscription_id' => $subscription->id,
                    ]);

                    Mail::to($subscription->email)
                        ->queue(new NewsletterMail($this->newsletter, $send));
                }
            });
    }
}
