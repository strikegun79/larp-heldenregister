<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\NextcloudTalk\NextcloudTalkClient;
use App\Services\NextcloudTalk\TalkTexts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Issue #31 (Phase 1): Verwaltung der Nextcloud-Talk-Anbindung.
 * Zugangsdaten stehen in der .env; hier nur Status, Verbindungstest und Einstellungen.
 */
class TalkSettingsController extends Controller
{
    /** Einstellungen in der settings-Tabelle. */
    public const KEYS = ['talk_enabled', 'talk_plot_room_token', 'talk_u18_group', 'talk_app_url', 'talk_help_url'];

    public function index(): View
    {
        $client = NextcloudTalkClient::fromConfig();
        $values = Setting::whereIn('key', self::KEYS)->pluck('value', 'key');

        return view('admin.talk.index', [
            'configured' => $client->isConfigured(),
            'url' => $client->url(),
            'values' => $values,
            'defaultHelpUrl' => config('nextcloud.default_help_url'),
            'texts' => TalkTexts::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'talk_enabled' => ['boolean'],
            'talk_plot_room' => ['nullable', 'string', 'max:255'],
            'talk_u18_group' => ['nullable', 'string', 'max:64', 'regex:/^[\pL\pN _.@-]+$/u'],
            'talk_app_url' => ['nullable', 'url:https', 'max:255'],
            'talk_help_url' => ['nullable', 'url:https', 'max:255'],
        ], [
            'talk_u18_group.regex' => 'Der Gruppenname enthält unzulässige Zeichen.',
            'talk_app_url.url' => 'Bitte einen vollständigen https-Link angeben.',
            'talk_help_url.url' => 'Bitte einen vollständigen https-Link angeben.',
        ]);

        // Plot-Team-Chat: Link oder Token; gespeichert wird nur der Raum-Token.
        $token = null;
        if (filled($data['talk_plot_room'] ?? null)) {
            $token = NextcloudTalkClient::roomTokenFrom($data['talk_plot_room']);
            if ($token === null) {
                return back()->withInput()->withErrors(['talk_plot_room' => 'Kein gültiger Talk-Link oder Raum-Token (Link in der Form …/call/abc123).']);
            }
        }

        Setting::set('talk_enabled', $request->boolean('talk_enabled') ? '1' : '0');
        Setting::set('talk_plot_room_token', $token);
        Setting::set('talk_u18_group', filled($data['talk_u18_group'] ?? null) ? trim($data['talk_u18_group']) : null);
        Setting::set('talk_app_url', $data['talk_app_url'] ?? null);
        Setting::set('talk_help_url', $data['talk_help_url'] ?? null);

        return redirect()->route('admin.talk.index')->with('status', 'Talk-Einstellungen wurden gespeichert.');
    }

    /**
     * Issue #31 (Phase 3): Texte für die Elternerlaubnis. Leeres Feld = Entwurf.
     * Bereits erteilte Erlaubnisse behalten ihre gespeicherte Fassung (Prüfsumme).
     */
    public function updateTexts(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (array_keys(TalkTexts::SECTIONS) as $key) {
            $rules[$key] = ['nullable', 'string', 'max:5000'];
        }
        $data = $request->validate($rules);

        foreach (array_keys(TalkTexts::SECTIONS) as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            Setting::set($key, $value === '' || $value === TalkTexts::DEFAULTS[$key] ? null : $value);
        }

        return redirect()->route('admin.talk.index')->with('status', 'Texte für die Elternerlaubnis wurden gespeichert.');
    }

    /** Verbindung zur Nextcloud prüfen (Ergebnis als Flash-Meldung). */
    public function test(): RedirectResponse
    {
        $result = NextcloudTalkClient::fromConfig()->testConnection();

        return redirect()->route('admin.talk.index')->with('talk_test', $result);
    }
}
