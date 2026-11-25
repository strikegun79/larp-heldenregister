<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Notifications\TalkAccessEnded;
use App\Services\AuditLogger;
use App\Services\NextcloudTalk\NextcloudTalkClient;
use App\Services\NextcloudTalk\PlayerTalkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Issue #31 (Phase 3): Der Bürokrat gibt Nextcloud Talk je Spieler frei (ab 14 Jahren)
 * oder nimmt die Freigabe zurück.
 */
class TalkApprovalController extends Controller
{
    public const FILTERS = ['' => 'Alle Spieler', 'approved' => 'Freigegeben', 'open' => 'Nicht freigegeben', 'error' => 'Mit Fehler'];

    public function index(Request $request): View
    {
        $q = trim($request->string('q'));
        $filter = array_key_exists((string) $request->string('filter'), self::FILTERS) ? (string) $request->string('filter') : '';

        $players = Player::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($b) use ($q) {
                    $b->where('name', 'like', "%{$q}%")
                        ->orWhere('lastname', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($filter === 'approved', fn ($query) => $query->whereNotNull('talk_approved_at'))
            ->when($filter === 'open', fn ($query) => $query->whereNull('talk_approved_at'))
            ->when($filter === 'error', fn ($query) => $query->whereNotNull('talk_error'))
            ->orderBy('lastname')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.talk.approvals', [
            'players' => $players,
            'q' => $q,
            'filter' => $filter,
            'filters' => self::FILTERS,
            'filtered' => $q !== '' || $filter !== '',
            'talkActive' => NextcloudTalkClient::fromConfig()->isActive(),
        ]);
    }

    public function approve(Request $request, Player $player): RedirectResponse
    {
        if ($blocker = $player->talkApprovalBlocker()) {
            return back()->with('error', $player->full_name.': '.$blocker);
        }

        if ($player->talk_approved_at === null) {
            $player->talk_approved_at = now();
            $player->talk_approved_by = $request->user()->id;
            $player->talk_error = null;
            $player->save();
            AuditLogger::log('talk.approved', $player);
            PlayerTalkService::notifyApproved($player);
        }

        PlayerTalkService::queueProvision($player);

        $message = $player->isTalkMinor()
            ? 'Talk für '.$player->full_name.' freigegeben. Jetzt müssen die Eltern im Heldenregister zustimmen.'
            : 'Talk für '.$player->full_name.' freigegeben. Das Konto wird eingerichtet.';

        return back()->with('status', $message);
    }

    public function revoke(Player $player): RedirectResponse
    {
        PlayerTalkService::revoke($player, TalkAccessEnded::REVOKED);
        AuditLogger::log('talk.revoked', $player);

        return back()->with('status', 'Talk-Freigabe für '.$player->full_name.' zurückgenommen.');
    }

    public function retry(Player $player): RedirectResponse
    {
        PlayerTalkService::queueProvision($player);

        return back()->with('status', 'Kontoeinrichtung für '.$player->full_name.' wird erneut versucht.');
    }
}
