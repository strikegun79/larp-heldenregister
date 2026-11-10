<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gemeinsames Antwortverhalten für einfache Lookup-CRUD-Controller (ADM-05).
 * Konvention: Der nutzende Controller implementiert indexRoute(): string.
 */
trait RespondsToLookup
{
    abstract protected function indexRoute(): string;

    protected function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route($this->indexRoute())->with('status', $message);
    }

    protected function respondError(Request $request, string $message, int $status = 422): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : back()->with('error', $message);
    }
}
