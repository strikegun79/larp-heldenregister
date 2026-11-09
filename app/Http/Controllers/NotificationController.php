<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * In-App-Benachrichtigungen (NOTI-07): als gelesen markieren.
 */
class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Eine Benachrichtigung als gelesen markieren und zu ihrem Ziel springen.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $entry = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $entry->markAsRead();

        $url = $entry->data['url'] ?? null;

        return $url ? redirect($url) : back();
    }

    /**
     * Alle ungelesenen Benachrichtigungen als gelesen markieren.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Alle Benachrichtigungen als gelesen markiert.');
    }
}
