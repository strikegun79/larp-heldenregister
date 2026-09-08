<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\NewsletterSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mews\Purifier\Facades\Purifier;

class NewsletterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'can:newsletter.manage']);
    }

    public function index(): View
    {
        $newsletters       = Newsletter::withCount('sends')->latest()->paginate(20);
        $activeSubscribers = NewsletterSubscription::whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->count();

        return view('admin.newsletter.index', compact('newsletters', 'activeSubscribers'));
    }

    public function create(): View
    {
        return view('admin.newsletter.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
        ]);

        $newsletter = Newsletter::create([
            'title'          => $validated['title'],
            'body_html'      => Purifier::clean($validated['body_html'], 'newsletter'),
            'status'         => Newsletter::STATUS_DRAFT,
            'sender_user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.newsletter.edit', $newsletter)
            ->with('success', 'Entwurf gespeichert.');
    }

    public function edit(Newsletter $newsletter): View
    {
        abort_if($newsletter->isSent(), 403, 'Versendete Newsletter können nicht bearbeitet werden.');

        $activeSubscribers = NewsletterSubscription::whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->count();

        return view('admin.newsletter.edit', compact('newsletter', 'activeSubscribers'));
    }

    public function update(Request $request, Newsletter $newsletter): RedirectResponse
    {
        abort_if($newsletter->isSent(), 403);

        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
        ]);

        $newsletter->update([
            'title'    => $validated['title'],
            'body_html' => Purifier::clean($validated['body_html'], 'newsletter'),
        ]);

        return redirect()->route('admin.newsletter.edit', $newsletter)
            ->with('success', 'Änderungen gespeichert.');
    }

    public function destroy(Newsletter $newsletter): RedirectResponse
    {
        abort_if($newsletter->isSent(), 403, 'Versendete Newsletter können nicht gelöscht werden.');

        $newsletter->delete();

        return redirect()->route('admin.newsletter.index')
            ->with('success', 'Newsletter gelöscht.');
    }

    /** JSON: aktuelle Abonnenten-Anzahl für den Editor-Zähler auf der Create-Seite. */
    public function subscriberCount(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'count' => NewsletterSubscription::whereNotNull('confirmed_at')
                ->whereNull('unsubscribed_at')
                ->count(),
        ]);
    }

    /** Dupliziert einen bestehenden Newsletter als neuen Entwurf. */
    public function duplicate(Newsletter $newsletter): RedirectResponse
    {
        $copy = Newsletter::create([
            'title'          => 'Kopie: ' . $newsletter->title,
            'body_html'      => $newsletter->body_html,
            'status'         => Newsletter::STATUS_DRAFT,
            'sender_user_id' => request()->user()->id,
        ]);

        return redirect()->route('admin.newsletter.edit', $copy)
            ->with('success', 'Newsletter als Entwurf dupliziert.');
    }
}
