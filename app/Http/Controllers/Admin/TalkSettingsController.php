<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\NextcloudTalk\NextcloudTalkClient;
use App\Services\NextcloudTalk\NextcloudTalkException;
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
    public const KEYS = ['talk_enabled', 'talk_plot_room_token', 'talk_u18_group', 'talk_user_group', 'talk_moderators', 'talk_app_url', 'talk_help_url'];

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
            // Sicherheit: nur Gruppen mit „TALK-“ am Anfang, nie „admin“ (siehe NextcloudTalkClient::isAllowedGroup).
            'talk_u18_group' => ['nullable', 'string', 'max:64', $groupRule = function (string $attribute, mixed $value, \Closure $fail) {
                if (filled($value) && ! NextcloudTalkClient::isAllowedGroup((string) $value)) {
                    $fail('Nur Gruppen, die mit „'.NextcloudTalkClient::GROUP_PREFIX.'“ beginnen und keine Sonderzeichen enthalten, sind erlaubt.');
                }
            }],
            'talk_user_group' => ['nullable', 'string', 'max:64', $groupRule],
            // Nextcloud-Benutzernamen, die in jedem Teamer-Raum Moderator werden (einer pro Zeile).
            'talk_moderators' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                $names = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                if (count($names) > 20) {
                    $fail('Höchstens 20 Moderatoren.');
                }
                foreach ($names as $name) {
                    if (preg_match('/^[\pL\pN_.@-]{1,64}$/u', $name) !== 1) {
                        $fail('Ungültiger Nextcloud-Benutzername: „'.$name.'“.');

                        return;
                    }
                }
            }],
            'talk_app_url' => ['nullable', 'url:https', 'max:255'],
            'talk_help_url' => ['nullable', 'url:https', 'max:255'],
        ], [
            'talk_app_url.url' => 'Bitte einen vollständigen https-Link angeben.',
            'talk_help_url.url' => 'Bitte einen vollständigen https-Link angeben.',
        ]);

        // Plot-Team-Chat: Link oder Token; gespeichert wird nur der Raum-Token.
        $token = null;
        if (filled($data['talk_plot_room'] ?? null)) {
            $token = NextcloudTalkClient::roomTokenFrom($data['talk_plot_room']);
            if ($token === null) {
                return back()->withInput()->withErrors(['talk_plot_room' => 'Kein gültiger Talk-Link oder Raum-Token (Link in der Form …/index.php/call/abc123).']);
            }
        }

        Setting::set('talk_enabled', $request->boolean('talk_enabled') ? '1' : '0');
        Setting::set('talk_plot_room_token', $token);
        Setting::set('talk_u18_group', filled($data['talk_u18_group'] ?? null) ? trim($data['talk_u18_group']) : null);
        Setting::set('talk_user_group', filled($data['talk_user_group'] ?? null) ? trim($data['talk_user_group']) : null);
        $moderators = preg_split('/[\s,;]+/', (string) ($data['talk_moderators'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        Setting::set('talk_moderators', $moderators ? implode("\n", array_unique($moderators)) : null);
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
        $client = NextcloudTalkClient::fromConfig();
        $result = $client->testConnection();

        // Talk-Gruppen prüfen: existieren sie und darf das technische Konto sie verwalten?
        if ($result['ok']) {
            $result['groups'] = [];
            foreach (['talk_user_group' => 'Volljährige', 'talk_u18_group' => 'U18'] as $key => $label) {
                $group = (string) Setting::get($key);
                if ($group === '') {
                    $result['groups'][] = ['ok' => false, 'text' => "{$label}: keine Gruppe eingetragen"];

                    continue;
                }
                try {
                    $visible = $client->groupVisible($group);
                    $result['groups'][] = $visible
                        ? ['ok' => true, 'text' => "{$label}: Gruppe „{$group}“ gefunden"]
                        : ['ok' => false, 'text' => "{$label}: Gruppe „{$group}“ nicht gefunden – Gruppen-ID prüfen und das technische Konto als Gruppen-Admin eintragen"];
                } catch (NextcloudTalkException $e) {
                    $result['groups'][] = ['ok' => false, 'text' => "{$label}: ".$e->getMessage()];
                }
            }
        }

        return redirect()->route('admin.talk.index')->with('talk_test', $result);
    }
}
