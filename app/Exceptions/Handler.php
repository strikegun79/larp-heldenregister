<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Session\TokenMismatchException;
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
     * Abgelaufene Session abfangen bevor prepareException() den Typ zu
     * HttpException(419) konvertiert und renderable-Callbacks nicht mehr matchen.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException && ! $request->expectsJson()) {
            return redirect()->route('login')
                ->with('warning', 'Deine Sitzung ist abgelaufen – bitte melde dich erneut an.');
        }

        return parent::render($request, $e);
    }

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
