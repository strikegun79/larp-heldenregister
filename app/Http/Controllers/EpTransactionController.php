<?php

namespace App\Http\Controllers;

use App\Models\EpTransaction;
use App\Models\EpTransactionType;
use App\Models\Hero;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EpTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:heldenregister.edit');
    }

    /**
     * Eigenständiges EP-Buchungsformular (EP-02): Held + Betrag + Art + Datum.
     */
    public function create(): View
    {
        $heroes = Hero::orderBy('character_name')->get();
        $epTypes = EpTransactionType::orderBy('id')->get();

        return view('ep_transactions.create', compact('heroes', 'epTypes'));
    }

    /**
     * Verarbeitet das eigenständige Buchungsformular (EP-02).
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_id' => ['required', 'exists:heroes,id'],
            'ep_count' => ['required', 'numeric', 'min:0.5'],
            'ep_transaction_type_id' => ['required', 'exists:ep_transaction_types,id'],
            'transacted_at' => ['nullable', 'date'],
        ]);

        $hero = Hero::findOrFail($data['hero_id']);

        $this->book($hero, $data);

        return redirect()->route('ep.create')
            ->with('status', "EP für „{$hero->character_name}“ gebucht.");
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
     * Gemeinsame Buchungslogik für beide Eingabewege.
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
