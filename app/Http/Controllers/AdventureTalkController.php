<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Services\NextcloudTalk\AdventureTalkService;
use App\Services\NextcloudTalk\NextcloudTalkClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Issue #31 (Phase 2): Reiter „Talk“ in der Event-Verwaltung – Plot-Thread und Teamer-Raum
 * nachträglich einrichten, Teamer abgleichen, fehlgeschlagene Einrichtung wiederholen.
 */
class AdventureTalkController extends Controller
{
    /** Gewählte Optionen einrichten (bereits verknüpfte bleiben unverändert). */
    public function update(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        if ($blocker = $this->inactive()) {
            return $this->respond($request, $blocker, 422);
        }

        $request->validate([
            'talk_plot_thread' => ['boolean'],
            'talk_teamer_room' => ['boolean'],
        ]);

        $adventure->talk_plot_thread = $adventure->talk_plot_thread || $request->boolean('talk_plot_thread');
        $adventure->talk_teamer_room = $adventure->talk_teamer_room || $request->boolean('talk_teamer_room');
        $adventure->save();

        AdventureTalkService::queueSetup($adventure);

        return $this->respond($request, 'Talk wird eingerichtet.');
    }

    /** Teamer-Raum jetzt mit den bestätigten Teamern abgleichen. */
    public function sync(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        if ($blocker = $this->inactive()) {
            return $this->respond($request, $blocker, 422);
        }
        if (! $adventure->talk_teamer_room_token) {
            return $this->respond($request, 'Für dieses Event gibt es noch keinen Teamer-Raum.', 422);
        }

        AdventureTalkService::queueSync($adventure);

        return $this->respond($request, 'Teamer werden abgeglichen.');
    }

    /** Einrichtung und Abgleich erneut anstoßen (z. B. nach einem Fehler). */
    public function retry(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        if ($blocker = $this->inactive()) {
            return $this->respond($request, $blocker, 422);
        }

        AdventureTalkService::queueSetup($adventure);
        AdventureTalkService::queueSync($adventure);

        return $this->respond($request, 'Talk-Einrichtung wird erneut versucht.');
    }

    private function inactive(): ?string
    {
        return NextcloudTalkClient::fromConfig()->isActive()
            ? null
            : 'Die Talk-Integration ist nicht aktiv (Verwaltung → Nextcloud Talk).';
    }

    private function respond(Request $request, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'refresh_modal' => $status === 200], $status);
        }

        return back()->with($status === 200 ? 'status' : 'error', $message);
    }
}
