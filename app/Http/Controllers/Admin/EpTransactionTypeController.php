<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EpTransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der EP-Buchungsarten (EP-06).
 * Buchungsarten unterscheiden Gutschriften (is_credit=true) von Buchungen.
 */
class EpTransactionTypeController extends Controller
{
    public function index(): View
    {
        $types = EpTransactionType::withCount('transactions')->orderBy('id')->get();

        return view('admin.ep_transaction_types.index', compact('types'));
    }

    public function create(Request $request): View
    {
        $data = ['type' => new EpTransactionType];

        return $request->expectsJson()
            ? view('admin.ep_transaction_types._form', $data)
            : view('admin.ep_transaction_types.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request, null);
        // IDs in 10er-Schritten analog Legacy-Schema (type_transEP).
        $data['id'] = (int) EpTransactionType::max('id') + 10;

        EpTransactionType::create($data);

        return $this->respond($request, 'EP-Buchungsart wurde angelegt.');
    }

    public function edit(EpTransactionType $epTransactionType, Request $request): View
    {
        $data = ['type' => $epTransactionType];

        return $request->expectsJson()
            ? view('admin.ep_transaction_types._form', $data)
            : view('admin.ep_transaction_types.edit', $data);
    }

    public function update(Request $request, EpTransactionType $epTransactionType): RedirectResponse|JsonResponse
    {
        $epTransactionType->update($this->validated($request, $epTransactionType));

        return $this->respond($request, 'EP-Buchungsart wurde aktualisiert.');
    }

    public function destroy(Request $request, EpTransactionType $epTransactionType): RedirectResponse|JsonResponse
    {
        if ($epTransactionType->transactions()->exists()) {
            $msg = 'Buchungsart wird von EP-Transaktionen verwendet und kann nicht gelöscht werden.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $epTransactionType->delete();

        return $this->respond($request, 'EP-Buchungsart wurde gelöscht.');
    }

    private function validated(Request $request, ?EpTransactionType $type): array
    {
        return $request->validate([
            'description' => ['required', 'string', 'max:100'],
            'is_credit' => ['boolean'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.ep-transaction-types.index')->with('status', $message);
    }
}
