<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventClient;
use App\Models\EventClientRequirement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Mews\Purifier\Facades\Purifier;

/**
 * Zusätzliche Anforderungen je Auftraggeber (Issue #14), z. B. eine
 * Einverständniserklärung mit PDF. Aktive Anforderungen sind Pflichtfelder
 * bei der Anmeldung zu jedem Event des Auftraggebers.
 */
class EventClientRequirementController extends Controller
{
    /** Ablageort der PDFs auf dem nicht öffentlichen Disk „local“. */
    private const PDF_DIRECTORY = 'event-client-requirements';

    public function create(Request $request, EventClient $eventClient): View
    {
        $data = [
            'client' => $eventClient,
            'requirement' => new EventClientRequirement(['active' => true]),
        ];

        return view('admin.event_clients._requirement_form', $data);
    }

    public function store(Request $request, EventClient $eventClient): RedirectResponse|JsonResponse
    {
        $data = $this->validateRequirement($request);
        $data['event_client_id'] = $eventClient->id;
        $data += $this->storePdf($request);

        EventClientRequirement::create($data);

        return $this->respond($request, 'Anforderung wurde angelegt.');
    }

    public function edit(EventClientRequirement $requirement): View
    {
        return view('admin.event_clients._requirement_form', [
            'client' => $requirement->client,
            'requirement' => $requirement,
        ]);
    }

    public function update(Request $request, EventClientRequirement $requirement): RedirectResponse|JsonResponse
    {
        $data = $this->validateRequirement($request);
        $old = $requirement->pdf_path;

        if ($request->hasFile('pdf')) {
            $data += $this->storePdf($request);
        } elseif ($request->boolean('remove_pdf')) {
            $data += ['pdf_path' => null, 'pdf_name' => null];
        }

        // Alte Datei nur löschen, wenn noch niemand zugestimmt hat (Nachweis erhalten).
        if ($old && array_key_exists('pdf_path', $data) && ! $requirement->bookings()->exists()) {
            Storage::disk('local')->delete($old);
        }

        $requirement->update($data);

        return $this->respond($request, 'Anforderung wurde aktualisiert.');
    }

    public function destroy(Request $request, EventClientRequirement $requirement): RedirectResponse|JsonResponse
    {
        // Anforderungen mit Zustimmungen bleiben als Nachweis erhalten und werden nur deaktiviert.
        if ($requirement->bookings()->exists()) {
            $requirement->update(['active' => false]);

            return $this->respond($request, 'Anforderung wurde bereits bestätigt und daher nur deaktiviert.');
        }

        if ($requirement->pdf_path) {
            Storage::disk('local')->delete($requirement->pdf_path);
        }
        $requirement->delete();

        return $this->respond($request, 'Anforderung wurde gelöscht.');
    }

    private function validateRequirement(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:60000'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_pdf' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], [
            'description.required' => 'Bitte eine Beschreibung eingeben.',
            'pdf.mimes' => 'Es sind nur PDF-Dateien erlaubt.',
        ]);

        // Formatierten Text bereinigen; reine Leer-Formatierung zählt als leer.
        $data['description'] = Purifier::clean($data['description'], 'requirement');
        if (trim(strip_tags($data['description'])) === '') {
            throw ValidationException::withMessages(['description' => 'Bitte eine Beschreibung eingeben.']);
        }

        unset($data['pdf'], $data['remove_pdf']);
        $data['active'] = $request->boolean('active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /** PDF speichern und Pfad sowie Originalnamen zurückgeben. */
    private function storePdf(Request $request): array
    {
        if (! $request->hasFile('pdf')) {
            return [];
        }

        $file = $request->file('pdf');

        return [
            'pdf_path' => $file->store(self::PDF_DIRECTORY, 'local'),
            'pdf_name' => mb_substr($file->getClientOriginalName(), 0, 255),
        ];
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.event-clients.index')->with('status', $message);
    }
}
