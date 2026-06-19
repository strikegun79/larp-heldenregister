<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\EventCategory;
use App\Models\EventClient;
use App\Models\EventRole;
use App\Models\EventStatus;
use App\Models\Location;
use App\Models\Player;
use App\Models\User;
use App\Notifications\EventCancelled;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdventureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Abenteuer ansehen: events.view ODER adventure.book (siehe adventure.access).
        $this->middleware('can:adventure.access')->only(['index', 'show', 'calendar']);
        // Events anlegen/bearbeiten/absagen + Verwaltungsliste: events.edit
        // (Admin, Bürokrat, Projektleitung).
        $this->middleware('can:events.edit')->only(['create', 'store', 'edit', 'update', 'destroy', 'manage', 'cancel', 'manageIndex']);
        // Teilnehmer-PDF: Projektleitung, Bürokrat, Admin (ADV-17).
        $this->middleware('can:take-signatures')->only('participantsPdf');
        // Teilnahme-/Belegungsreport (REP-03): Event-Verwalter.
        $this->middleware('can:events.edit')->only('participationCsv');
    }

    /**
     * Liste aller Abenteuer.
     */
    public function index(Request $request): View
    {
        $q = trim($request->string('q'));

        $adventures = Adventure::with(['location', 'status', 'category'])
            ->withCount('confirmedBookings')
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderByDesc('start_at')
            ->paginate(20)
            ->withQueryString();

        return view('adventures.index', compact('adventures', 'q'));
    }

    /**
     * Kalender-/Listenansicht kommender Events, chronologisch nach Monat
     * gruppiert (ADV-12).
     */
    public function calendar(): View
    {
        $events = Adventure::with(['location', 'status'])
            ->withCount('confirmedBookings')
            ->whereNotNull('start_at')
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at')
            ->get()
            ->groupBy(fn (Adventure $a) => $a->start_at->format('Y-m'));

        return view('adventures.calendar', compact('events'));
    }

    /**
     * Verwaltungsliste der Events (ADV-06): Status, Belegung, Aktionen.
     * Für alle Event-Verwalter (events.edit), getrennt von der Browse-Liste.
     */
    public function manageIndex(): View
    {
        $adventures = Adventure::with('status')
            ->withCount('confirmedBookings')
            ->orderByDesc('start_at')
            ->paginate(25);

        return view('adventures.manage_index', compact('adventures'));
    }

    public function create(): View
    {
        return view('adventures.create', $this->formData(new Adventure([
            'event_status_id' => 20,
            'event_client_id' => 1,
            'event_category_id' => 0,
            'max_player' => 10,
            'fee' => 12,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $adventure = Adventure::create($this->validateAdventure($request));

        return redirect()
            ->route('adventures.show', $adventure)
            ->with('status', 'Abenteuer wurde angelegt.');
    }

    public function show(Request $request, Adventure $adventure): View|RedirectResponse
    {
        // Direktaufruf im Browser → zur Index-Seite umleiten, dort Modal per ?open= öffnen.
        if (! $request->ajax()) {
            return redirect()->route('adventures.index', ['open' => $adventure->id]);
        }

        $adventure->load(['location', 'status', 'category', 'client', 'bookings.player', 'bookings.role', 'visits', 'gamemaster', 'eventleader', 'teamerSignups.user']);

        // Buchbare Spieler: Bürokrat/Admin alle, sonst nur eigene/betreute (BOOK-10).
        $players = Gate::allows('book-any-player')
            ? Player::orderBy('name')->get()
            : $request->user()->players()->orderBy('name')->get();

        // Sichtbare Anmeldungen (ADV-15): Bürokrat/Projektleitung/Spielleiter/Admin
        // sehen alle, sonst nur eigene Spieler + selbst angemeldete Gäste (ADV-21).
        $ownPlayerIds = $request->user()->players->pluck('id');
        $visibleBookings = Gate::allows('view-all-bookings')
            ? $adventure->bookings
            : $adventure->bookings->filter(
                fn ($b) => $ownPlayerIds->contains($b->player_id) || $b->booked_by_user_id === $request->user()->id
            )->values();

        $teamerSignups = $adventure->teamerSignups;
        $myTeamerSignup = $teamerSignups->firstWhere('user_id', $request->user()->id);

        return view('adventures._detail', [
            'adventure' => $adventure,
            'players' => $players,
            'roles' => EventRole::orderBy('id')->get(),
            'visibleBookings' => $visibleBookings,
            'teamerSignups' => $teamerSignups,
            'myTeamerSignup' => $myTeamerSignup,
        ]);
    }

    /**
     * Verwaltungs-Modal mit Tabs (ADV-16): Event-Daten (Editor), Anmeldungen
     * mit Aktionen und Check-in. Für die Verwaltung → Abenteuer; keine
     * Selbst-Anmeldung.
     */
    public function manage(Adventure $adventure): View
    {
        $adventure->load(['bookings.player.users', 'bookings.bookedBy', 'bookings.role', 'visits', 'status', 'teamerSignups.user']);

        $nscBookings = $adventure->bookings->where('event_role_id', EventRole::NSC_ROLE_ID)->values();
        $mainBookings = $adventure->bookings->where('event_role_id', '!=', EventRole::NSC_ROLE_ID)->values();

        return view('adventures._manage', array_merge($this->formData($adventure), [
            'nscBookings' => $nscBookings,
            'mainBookings' => $mainBookings,
        ]));
    }

    /**
     * Teilnehmerliste als PDF (ADV-17): Kopf mit Datum/Ort/Typ und Anzahl
     * männlich/weiblich, dann alle Anmeldungen inkl. Unterschrift.
     */
    public function participantsPdf(Adventure $adventure): Response
    {
        $adventure->load(['location', 'category', 'bookings.player.users', 'bookings.bookedBy', 'teamerSignups.user']);

        // Nur reguläre Teilnehmer (ohne NSC-Elternteil) in der Hauptliste.
        $bookings = $adventure->bookings
            ->where('event_role_id', '!=', EventRole::NSC_ROLE_ID)
            ->sortBy([['player.lastname', 'asc'], ['player.name', 'asc']])
            ->values();

        $nscBookings = $adventure->bookings
            ->where('event_role_id', EventRole::NSC_ROLE_ID)
            ->values();

        $male = $bookings->filter(fn ($b) => $b->player?->gender === 'männlich')->count();
        $female = $bookings->filter(fn ($b) => $b->player?->gender === 'weiblich')->count();
        $diverse = $bookings->filter(fn ($b) => $b->player?->gender === 'divers')->count();

        $pdf = Pdf::loadView('adventures.participants_pdf', compact('adventure', 'bookings', 'nscBookings', 'male', 'female', 'diverse'));

        // Inline (ADV-19): öffnet im Browser-Tab/Popup statt Download.
        return $pdf->stream('teilnehmerliste-'.$adventure->id.'.pdf');
    }

    /**
     * Event absagen (ADV-07): Status „abgesagt" (70) setzen. Da Status ≠ 30,
     * sind damit automatisch keine neuen Anmeldungen mehr möglich.
     */
    public function cancel(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        if ($adventure->event_status_id === EventStatus::CANCELLED) {
            return $this->cancelFail($request, 'Das Event ist bereits abgesagt.');
        }

        if (! $adventure->canTransitionTo(EventStatus::CANCELLED)) {
            return $this->cancelFail($request, 'Dieses Event kann nicht (mehr) abgesagt werden.');
        }

        $adventure->update(['event_status_id' => EventStatus::CANCELLED]);

        // NOTI-04: Absage-Mails an alle gebuchten Spieler mit E-Mail.
        $recipients = $adventure->bookings()->with('player')->get()
            ->map(fn ($b) => $b->player?->email)
            ->filter()
            ->unique();
        foreach ($recipients as $email) {
            Notification::route('mail', $email)->notify(new EventCancelled($adventure));
        }

        $message = 'Event wurde abgesagt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    private function cancelFail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('error', $message);
    }

    /**
     * Teilnahme-/Belegungsreport eines Events als CSV (REP-03): je Anmeldung
     * Spieler/Rolle/Liste/Status/Beitrag/Anwesend, plus Summenzeile.
     */
    public function participationCsv(Adventure $adventure): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $adventure->load(['bookings.player', 'bookings.role', 'visits']);
        $visitedIds = $adventure->visits->pluck('player_id');

        return response()->streamDownload(function () use ($adventure, $visitedIds) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Spieler', 'Rolle', 'Liste', 'Status', 'Beitrag', 'Anwesend'], ';');

            foreach ($adventure->bookings as $b) {
                fputcsv($out, [
                    $b->participant_name.($b->is_guest ? ' (Gast)' : ''),
                    $b->role?->description,
                    $b->waitlisted ? 'Warteliste' : 'regulär',
                    $b->status_label,
                    $b->paid ? 'bezahlt' : 'offen',
                    $visitedIds->contains($b->player_id) ? 'ja' : 'nein',
                ], ';');
            }

            $payable = $adventure->bookings->where('waitlisted', false);
            fputcsv($out, [], ';');
            fputcsv($out, ['Summen'], ';');
            fputcsv($out, ['Regulär', $payable->count()], ';');
            fputcsv($out, ['Warteliste', $adventure->bookings->where('waitlisted', true)->count()], ';');
            fputcsv($out, ['Bezahlt', $payable->where('paid', true)->count()], ';');
            fputcsv($out, ['Offen', $payable->where('paid', false)->count()], ';');
            fputcsv($out, ['Anwesend', $adventure->visits->count()], ';');
            fclose($out);
        }, 'belegung-'.$adventure->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function edit(Adventure $adventure): View
    {
        $data = $this->formData($adventure);

        if (request()->ajax()) {
            return view('adventures._edit_modal', $data);
        }

        return view('adventures.edit', $data);
    }

    public function update(Request $request, Adventure $adventure): RedirectResponse|JsonResponse
    {
        $data = $this->validateAdventure($request);

        // Geführter Status-Workflow: nur erlaubte Übergänge (ADV-05).
        if (! $adventure->canTransitionTo((int) $data['event_status_id'])) {
            $message = 'Dieser Status-Übergang ist nicht erlaubt.';

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['event_status_id' => [$message]]], 422)
                : back()->withErrors(['event_status_id' => $message])->withInput();
        }

        $adventure->update($data);

        $message = 'Abenteuer wurde aktualisiert.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('adventures.show', $adventure)->with('status', $message);
    }

    public function destroy(Adventure $adventure): RedirectResponse
    {
        $adventure->delete();

        return redirect()
            ->route('adventures.index')
            ->with('status', 'Abenteuer wurde gelöscht.');
    }

    /**
     * Gemeinsame Auswahllisten für die Formulare.
     *
     * @return array<string, mixed>
     */
    private function formData(Adventure $adventure): array
    {
        return [
            'adventure' => $adventure,
            'locations' => Location::orderBy('titel')->get(),
            'statuses' => EventStatus::orderBy('id')->get(),
            'categories' => EventCategory::orderBy('name')->get(),
            'clients' => EventClient::orderBy('name')->get(),
            // Berechtigte Nutzer für GM/Eventleiter (ADV-11): Spielleiter,
            // Projektleitung, Teamer, Admin.
            'eligibleUsers' => User::whereHas('roles', fn ($q) => $q->whereIn('roles.id', [10, 30, 40, 50]))
                ->orderBy('name')->get(),
            // Geführter Workflow: bei bestehendem Event nur erlaubte Ziel-Status,
            // bei der Neuanlage alle (ADV-05).
            'allowedStatusIds' => $adventure->exists ? $adventure->allowedStatusIds() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAdventure(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'function_email' => ['nullable', 'email', 'max:255'],
            'gamemaster_id' => ['nullable', 'exists:users,id'],
            'eventleader_id' => ['nullable', 'exists:users,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'loot_ep_day' => ['integer', 'min:0'],
            'event_status_id' => ['required', 'exists:event_statuses,id'],
            'event_client_id' => ['required', 'exists:event_clients,id'],
            'event_category_id' => ['required', 'exists:event_categories,id'],
            'max_player' => ['required', 'integer', 'min:1'],
            'waitlist' => ['integer', 'min:0'],
            'fee' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
