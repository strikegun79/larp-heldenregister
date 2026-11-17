<?php

namespace App\Http\Controllers;

use App\Models\EventClientRequirement;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download der PDF einer Auftraggeber-Anforderung (Issue #14).
 * Die Dateien liegen nicht öffentlich; Zugriff nur für eingeloggte Nutzer.
 */
class EventClientRequirementPdfController extends Controller
{
    public function show(EventClientRequirement $requirement): StreamedResponse
    {
        abort_unless($requirement->pdf_path && Storage::disk('local')->exists($requirement->pdf_path), 404);

        return Storage::disk('local')->response(
            $requirement->pdf_path,
            $requirement->pdf_name ?: 'Einverstaendniserklaerung.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
