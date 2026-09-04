<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Ungültiger oder abgelaufener signierter Link (z. B. E-Mail-Bestätigung)
        // → statt nacktem 403 eine verständliche deutsche Fehlermeldung anzeigen.
        $this->renderable(function (InvalidSignatureException $e, $request) {
            $alreadyVerified = $request->user()?->hasVerifiedEmail();

            return response()->view('errors.link-ungueltig', [
                'alreadyVerified' => $alreadyVerified,
            ], 410);
        });
    }
}
