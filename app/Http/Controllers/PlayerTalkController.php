<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Notifications\TalkAccessEnded;
use App\Services\AuditLogger;
use App\Services\NextcloudTalk\PlayerTalkService;
use App\Services\NextcloudTalk\TalkTexts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Issue #31 (Phase 3): Eltern erlauben im Reiter „Talk“ die Talk-Nutzung ihres
 * minderjährigen Kindes – oder widerrufen sie. Nur verknüpfte Nutzer, die nicht der
 * Spieler selbst sind (Pivot self = false).
 */
class PlayerTalkController extends Controller
{
    public function consent(Request $request, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorizeGuardian($request, $player);

        if ($player->talk_approved_at === null) {
            return $this->respond($request, 'Talk ist für diesen Spieler noch nicht freigegeben.', 422);
        }

        $request->validate(['accept' => ['accepted']], [
            'accept.accepted' => 'Bitte bestätige, dass du die Texte gelesen hast und die Nutzung erlaubst.',
        ]);

        if ($player->talk_consent_at === null) {
            $player->talk_consent_at = now();
            $player->talk_consent_by = $request->user()->id;
            $player->talk_consent_version = TalkTexts::version();
            $player->save();
            AuditLogger::log('talk.consent_given', $player, ['version' => $player->talk_consent_version]);
        }

        PlayerTalkService::queueProvision($player);

        return $this->respond($request, PlayerTalkService::missingAccountEmail($player)
            ? 'Danke für die Erlaubnis! Bitte trage noch die E-Mail-Adresse des Spielers ein, dann wird das Talk-Konto eingerichtet.'
            : 'Danke! Das Talk-Konto wird eingerichtet. Die Einladung kommt per E-Mail.');
    }

    public function withdraw(Request $request, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorizeGuardian($request, $player);

        PlayerTalkService::revoke($player, TalkAccessEnded::WITHDRAWN);
        AuditLogger::log('talk.consent_withdrawn', $player);

        return $this->respond($request, 'Die Erlaubnis wurde widerrufen. Das Talk-Konto wird deaktiviert.');
    }

    /** Nur Eltern (verknüpft, nicht „self“) eines minderjährigen Spielers; Admin über Gate::before nicht. */
    private function authorizeGuardian(Request $request, Player $player): void
    {
        $isGuardian = $request->user()->players()
            ->whereKey($player->getKey())
            ->wherePivot('self', false)
            ->exists();

        abort_unless($isGuardian && $player->isTalkMinor(), 403);
    }

    private function respond(Request $request, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'reload' => $status === 200], $status);
        }

        return back()->with($status === 200 ? 'status' : 'error', $message);
    }
}
