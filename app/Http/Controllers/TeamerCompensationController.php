<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\TeamerSignup;
use App\Support\GermanNumberWords;
use App\Support\Iban;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Issue #19: Teamer laden ihre ausgefüllte Abrechnung der Aufwandsentschädigung herunter.
 * Nur die eigene Anmeldung (Bankdaten und Steuernummer sieht nur die Person selbst).
 */
class TeamerCompensationController extends Controller
{
    public function download(Request $request, Adventure $adventure, TeamerSignup $signup): Response|RedirectResponse
    {
        abort_if($signup->adventure_id !== $adventure->id, 404);
        abort_unless($signup->user_id === $request->user()->id, 403);

        $signup->setRelation('adventure', $adventure);
        $user = $request->user();

        $blocker = $signup->compensationBlocker();
        $missing = $user->missingTeamerBillingFields();
        if ($blocker !== null || $missing !== []) {
            return back()->with('error', $blocker ?? 'Im Profil fehlt noch: '.implode(', ', $missing).'.');
        }

        $signup->load('eventRole');
        $adventure->loadMissing('location');
        $amount = (string) $signup->compensationAmount();

        $pdf = Pdf::loadView('adventures.compensation_pdf', [
            'adventure' => $adventure,
            'signup' => $signup,
            'user' => $user,
            'amount' => $amount,
            'amountWords' => GermanNumberWords::euro($amount),
            'iban' => Iban::format($user->teamer_iban),
        ])->setPaper('a4');

        // Dateiname mit Startdatum der Veranstaltung, z. B. Aufwandsentschaedigung_jufolarp_muller_2026-11-14.pdf
        $filename = 'Aufwandsentschaedigung_'.Str::slug($adventure->kuerzel ?: $adventure->name)
            .'_'.Str::slug($user->lastname ?: $user->name)
            .'_'.$adventure->start_at->format('Y-m-d')
            .'.pdf';

        return $pdf->download($filename);
    }
}
