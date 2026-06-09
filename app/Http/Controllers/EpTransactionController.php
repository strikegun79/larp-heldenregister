<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EpTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // EP buchen darf, wer Helden bearbeiten darf (Bürokrat + Admin).
        $this->middleware('can:heldenregister.edit');
    }

    /**
     * Bucht eine EP-Gutschrift/-Kosten für einen Helden. Das Vorzeichen ergibt
     * sich aus der gewählten Buchungsart (ep_transaction_types.is_credit);
     * `ep_count` ist stets der positive Betrag.
     */
    public function store(Request $request, Hero $hero): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'ep_count' => ['required', 'numeric', 'min:0.5'],
            'ep_transaction_type_id' => ['required', 'exists:ep_transaction_types,id'],
        ]);

        $hero->epTransactions()->create([
            'ep_transaction_type_id' => $data['ep_transaction_type_id'],
            'ep_count' => $data['ep_count'],
            'transacted_at' => now(),
        ]);

        $message = 'EP-Buchung gespeichert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }
}
