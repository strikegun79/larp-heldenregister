<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schützt den matrix-corporal Policy-Endpoint per Bearer-Token.
 * Ersetzt die feste Token-Prüfung aus dem Legacy corporal.php.
 */
class VerifyMatrixCorporalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('matrix.corporal_token');

        if (empty($expected) || ! hash_equals($expected, (string) $request->bearerToken())) {
            abort(401);
        }

        return $next($request);
    }
}
