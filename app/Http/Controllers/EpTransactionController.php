<?php

namespace App\Http\Controllers;

use App\Models\EpTransaction;
use App\Models\EpTransactionType;
use App\Models\Hero;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EpTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:heldenregister.edit');
    }

    /**
     * Bucht EP für einen Helden aus dem Hero-Modal heraus (heroes/{hero}/ep).
     * `transacted_at` ist optional; fehlt es, wird der aktuelle Zeitpunkt verwendet.
     */
    public function store(Request $request, Hero $hero): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'ep_count' => ['required', 'numeric', 'min:0.5'],
            'ep_transaction_type_id' => ['required', 'exists:ep_transaction_types,id'],
            'transacted_at' => ['nullable', 'date'],
        ]);

        $this->book($hero, $data);

        $message = 'EP-Buchung gespeichert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Buchungslogik inkl. Audit-Log.
     *
     * @param  array<string, mixed>  $data
     */
    private function book(Hero $hero, array $data): EpTransaction
    {
        $tx = $hero->epTransactions()->create([
            'ep_transaction_type_id' => $data['ep_transaction_type_id'],
            'ep_count' => $data['ep_count'],
            'transacted_at' => $data['transacted_at'] ?? now(),
        ]);

        $type = EpTransactionType::find($data['ep_transaction_type_id']);

        AuditLogger::log('ep.booked', $hero, [
            'ep_count' => $data['ep_count'],
            'typ' => $type?->description,
            'gutschrift' => $type?->is_credit,
            'datum' => $tx->transacted_at->toDateString(),
        ]);

        return $tx;
    }
}
