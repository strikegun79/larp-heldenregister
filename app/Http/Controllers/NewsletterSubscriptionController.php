<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class NewsletterSubscriptionController extends Controller
{
    private const CONSENT_TEXT = 'Ich möchte den Newsletter der Waldritter Gießen per E-Mail erhalten. '
        . 'Meine E-Mail-Adresse wird ausschließlich für den Versand des Newsletters genutzt und nicht '
        . 'an Dritte weitergegeben. Die Einwilligung kann jederzeit widerrufen werden.';

    public function subscribe(Request $request): RedirectResponse
    {
        $user  = $request->user();
        $email = $user->email;

        $subscription = NewsletterSubscription::where('email', $email)->first();

        if ($subscription) {
            if ($subscription->isActive()) {
                return back()->with('newsletter_status', 'already_subscribed');
            }

            // Erneut anmelden (abgemeldet oder noch nicht bestätigt)
            $subscription->update([
                'user_id'          => $user->id,
                'unsubscribed_at'  => null,
                'confirmed_at'     => null,
                'consent_text'     => self::CONSENT_TEXT,
                'consented_at'     => now(),
            ]);
        } else {
            $subscription = NewsletterSubscription::create([
                'user_id'      => $user->id,
                'email'        => $email,
                'consent_text' => self::CONSENT_TEXT,
                'consented_at' => now(),
            ]);
        }

        Mail::to($email)->send(new NewsletterConfirmationMail($subscription));

        return back()->with('newsletter_status', 'confirmation_sent');
    }

    public function unsubscribe(Request $request): RedirectResponse
    {
        $subscription = NewsletterSubscription::where('email', $request->user()->email)
            ->whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->first();

        if ($subscription) {
            $subscription->update(['unsubscribed_at' => now()]);
        }

        return back()->with('newsletter_status', 'unsubscribed');
    }

    /** Öffentliche Double-Opt-in-Bestätigung (kein Login nötig). */
    public function confirm(string $token): View
    {
        $subscription = NewsletterSubscription::where('token', $token)->first();

        if (! $subscription || $subscription->unsubscribed_at !== null) {
            return view('newsletter.confirm-invalid');
        }

        if (! $subscription->isConfirmed()) {
            $subscription->update(['confirmed_at' => now()]);
        }

        return view('newsletter.confirmed');
    }
}
