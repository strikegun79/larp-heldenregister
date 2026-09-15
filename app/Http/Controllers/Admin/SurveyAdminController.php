<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SurveyInvitationMail;
use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EventRole;
use App\Models\SurveyAnswer;
use App\Models\SurveyLink;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\Survey;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Umfrage-Verwaltung (SURV-01): Umfragen pro Veranstaltung erstellen,
 * Links generieren und versenden, Ergebnisse einsehen.
 * Berechtigung: survey.admin (Erstellen/Versenden) + survey.view (Einsehen).
 */
class SurveyAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /** Übersicht aller Umfragen. */
    public function index(): View
    {
        $this->authorize('survey.view');

        $surveys = Survey::with(['adventure', 'template'])
            ->withCount([
                'links',
                'links as completed_count' => fn ($q) => $q->whereNotNull('completed_at'),
            ])
            ->latest()
            ->paginate(20);

        return view('admin.surveys.index', compact('surveys'));
    }

    /** Formular: neue Umfrage aus Vorlage für eine Veranstaltung erstellen. */
    public function create(): View
    {
        $this->authorize('survey.admin');

        $adventures = Adventure::orderByDesc('start_at')->get();
        $templates  = SurveyTemplate::where('active', true)->orderBy('name')->get();

        return view('admin.surveys.create', compact('adventures', 'templates'));
    }

    /** Umfrage-Instanz speichern. */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('survey.admin');

        $data = $request->validate([
            'adventure_id'       => ['required', 'exists:adventures,id'],
            'survey_template_id' => ['required', 'exists:survey_templates,id'],
            'title'              => ['required', 'string', 'max:255'],
            'closes_at'          => ['nullable', 'date', 'after:today'],
        ]);

        Survey::create([
            ...$data,
            'status'     => 'draft',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.surveys.index')
            ->with('success', 'Umfrage erstellt.');
    }

    /**
     * Teilnehmer der Veranstaltung automatisch in die Einladungsliste importieren.
     * Filtert nach target_group der Vorlage und überspringt bereits vorhandene Links (E-Mail-Duplikat-Schutz).
     */
    public function importBookings(Survey $survey): RedirectResponse
    {
        $this->authorize('survey.admin');

        if ($survey->status === 'closed') {
            return back()->with('error', 'Geschlossene Umfragen können nicht bearbeitet werden.');
        }

        $survey->load(['adventure.bookings.player.users', 'template']);
        $targetGroup = $survey->template->target_group;

        $bookings = $survey->adventure->bookings()
            ->with(['player.users', 'role'])
            ->whereIn('status', ['bestaetigt', 'offen'])
            ->get();

        $imported = 0;
        $skipped  = 0;

        // Bereits vorhandene E-Mails für diese Umfrage laden
        $existingEmails = $survey->links()->pluck('email')->map('strtolower')->toArray();

        foreach ($bookings as $booking) {
            [$name, $email, $resolvedType] = $this->resolveRecipient($booking, $targetGroup);

            // Bei 'all' werden alle Gruppen importiert; bei einer spezifischen Gruppe nur passende
            if ($targetGroup !== 'all' && $resolvedType !== $targetGroup) {
                continue;
            }

            if (! $email) {
                continue;
            }

            if (in_array(strtolower($email), $existingEmails, true)) {
                $skipped++;
                continue;
            }

            // target_type am Link ist immer der aufgelöste Typ (Wizard-Entscheidung läuft pro Link)
            SurveyLink::create([
                'survey_id'   => $survey->id,
                'target_type' => $resolvedType,
                'player_id'   => $booking->player_id,
                'name'        => $name,
                'email'       => $email,
                'expires_at'  => $survey->closes_at,
            ]);

            $existingEmails[] = strtolower($email);
            $imported++;
        }

        $msg = "{$imported} Person(en) importiert.";
        if ($skipped > 0) {
            $msg .= " {$skipped} übersprungen (E-Mail bereits vorhanden).";
        }

        return back()->with('success', $msg);
    }

    /**
     * E-Mail-Einladung an einen einzelnen Link versenden (oder erneut senden).
     * Dokumentiert den Versandzeitpunkt im SurveyLink.
     */
    public function sendLink(Survey $survey, SurveyLink $link): RedirectResponse
    {
        $this->authorize('survey.admin');

        if ($survey->status === 'closed' || $link->isCompleted()) {
            return back()->with('error', 'Link ist nicht mehr aktiv.');
        }

        Mail::to($link->email)->queue(new SurveyInvitationMail($link));

        // sent_at am Link dokumentieren (survey_links.sent_at wird einmalig beim ersten Versand gesetzt)
        if (! $link->sent_at) {
            $link->update(['sent_at' => now()]);
        }

        // survey.sent_at und status sicherstellen
        if ($survey->status === 'draft') {
            $survey->update(['status' => 'active', 'sent_at' => now()]);
        }

        return back()->with('success', "Einladung an {$link->email} versendet.");
    }

    /** Einladungslink löschen – nur wenn noch keine Antwort vorliegt. */
    public function destroyLink(Survey $survey, SurveyLink $link): RedirectResponse
    {
        $this->authorize('survey.admin');

        if ($link->isCompleted()) {
            return back()->with('error', 'Beantwortete Einladungen können nicht gelöscht werden.');
        }

        $name = $link->name;
        $link->delete();

        return back()->with('success', "Einladung von \"{$name}\" gelöscht.");
    }

    /** Manuelle Einladungsliste (JSON-Empfänger) versenden – für Einzel-Ergänzungen. */
    public function send(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorize('survey.admin');

        if ($survey->status === 'closed') {
            return back()->with('error', 'Geschlossene Umfragen können nicht versendet werden.');
        }

        $data = $request->validate([
            'recipients'                  => ['required', 'array', 'min:1'],
            'recipients.*.name'           => ['required', 'string', 'max:255'],
            'recipients.*.email'          => ['required', 'email'],
            'recipients.*.target_type'    => ['required', 'in:participant_child,participant_teen,teamer,parent'],
            'recipients.*.player_id'      => ['nullable', 'exists:players,id'],
        ]);

        $sent = 0;
        foreach ($data['recipients'] as $recipient) {
            $link = SurveyLink::create([
                'survey_id'   => $survey->id,
                'target_type' => $recipient['target_type'],
                'player_id'   => $recipient['player_id'] ?? null,
                'name'        => $recipient['name'],
                'email'       => $recipient['email'],
                'expires_at'  => $survey->closes_at,
                'sent_at'     => now(),
            ]);

            Mail::to($recipient['email'])->queue(new SurveyInvitationMail($link));
            $sent++;
        }

        $survey->update(['status' => 'active', 'sent_at' => now()]);

        return back()->with('success', "{$sent} Einladung(en) versendet.");
    }

    /** Ergebnisauswertung inkl. Einladungsliste. */
    public function results(Survey $survey): View
    {
        $this->authorize('survey.view');

        $survey->load(['template.questions', 'adventure']);

        $questions = $survey->template->questions;
        $stats     = [];

        foreach ($questions as $question) {
            $answers = SurveyAnswer::where('survey_template_question_id', $question->id)
                ->whereHas('response.link', fn ($q) => $q->where('survey_id', $survey->id))
                ->get();

            $stat = ['question' => $question, 'count' => $answers->count()];

            if ($question->type === 'rating') {
                $stat['average']      = $answers->avg('rating_answer');
                $stat['distribution'] = $answers->groupBy('rating_answer')->map->count()->sortKeys();
            } elseif ($question->type === 'text') {
                $stat['texts'] = $answers->pluck('text_answer')->filter()->values();
            } elseif ($question->type === 'yes_no') {
                $stat['yes_count'] = $answers->where('yes_no_answer', true)->count();
                $stat['no_count']  = $answers->where('yes_no_answer', false)->count();
            }

            $stats[] = $stat;
        }

        $completedCount = $survey->completedCount();
        $totalCount     = $survey->totalCount();

        // Einladungsliste mit Response-Status
        $links = $survey->links()
            ->with(['player', 'response'])
            ->orderBy('name')
            ->get();

        return view('admin.surveys.results', compact(
            'survey', 'stats', 'completedCount', 'totalCount', 'links'
        ));
    }

    /** Einzelantwort eines Teilnehmers anzeigen. */
    public function showResponse(Survey $survey, SurveyLink $link): View
    {
        $this->authorize('survey.view');

        $link->load(['response.answers.question', 'player', 'survey.template.questions', 'survey.adventure']);

        return view('admin.surveys.response', compact('survey', 'link'));
    }

    /** PDF-Export: alle Antworten, eine pro Seite. */
    public function exportPdf(Survey $survey): Response
    {
        $this->authorize('survey.view');

        $survey->load(['template.questions', 'adventure']);

        $links = $survey->links()
            ->with(['response.answers.question', 'player'])
            ->whereNotNull('completed_at')
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('admin.surveys.export-pdf', compact('survey', 'links'))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'umfrage-' . str($survey->title)->slug() . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /** Umfrage schließen. */
    public function close(Survey $survey): RedirectResponse
    {
        $this->authorize('survey.admin');

        $survey->update(['status' => 'closed']);

        return back()->with('success', 'Umfrage geschlossen.');
    }

    /**
     * Ermittelt Name, E-Mail und Zielgruppen-Typ aus einer Buchung.
     * Verwendet filled() statt ??, damit leere Strings nicht als gültige E-Mail durchgehen.
     *
     * @return array{0: string, 1: string|null, 2: string}
     */
    private function resolveRecipient(Booking $booking, string $targetGroup): array
    {
        $isTeamer = $booking->role?->for_teamer ?? false;
        $age      = $booking->participant_age;   // getParticipantAgeAttribute()

        $resolvedType = match (true) {
            $isTeamer                  => 'teamer',
            $age !== null && $age < 13  => 'participant_child',
            $age !== null && $age <= 17 => 'participant_teen',
            $age === null               => $targetGroup, // Kein Geburtsdatum → Typ aus Vorlage übernehmen
            default                    => 'teamer',      // 18+ Spieler ohne Teamer-Rolle → Erwachsenen-Form (Scroll)
        };

        // participant_name (getParticipantNameAttribute()), kann '—' zurückgeben
        $rawName = $booking->participant_name;
        $name    = filled($rawName) && $rawName !== '—' ? $rawName : null;

        $email = null;

        if ($targetGroup === 'parent') {
            $guardian = $booking->guardian();
            $email    = $guardian?->email;
            $name     = filled($guardian?->name) ? $guardian->name : ($name . ' (Erziehungsberechtigte/r)');
            $resolvedType = 'parent';
        } else {
            // filled() statt ??, damit leere Strings '' nicht als gültig gelten
            $email = filled($booking->player?->email)
                ? $booking->player->email
                : (filled($booking->player?->users->first()?->email)
                    ? $booking->player->users->first()->email
                    : $booking->guardian()?->email);
        }

        // Wenn kein Name ermittelbar, E-Mail als Fallback
        if (! filled($name)) {
            $name = $email ?? 'Unbekannt';
        }

        return [$name, $email, $resolvedType];
    }
}
