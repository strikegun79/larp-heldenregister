<?php

namespace App\Console\Commands;

use App\Models\AdventureTask;
use App\Models\EventStatus;
use App\Models\Booking;
use App\Notifications\EventReminder;
use App\Notifications\WaitlistPromoted;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * TASK-01: Führt alle fälligen Aufgaben (AdventureTasks) aus.
 * Idempotent: bereits ausgeführte Tasks (executed_at != null) werden übersprungen.
 */
class RunAdventureTasks extends Command
{
    protected $signature = 'adventures:run-tasks {--dry-run : Nur anzeigen, was ausgeführt würde}';

    protected $description = 'Führt fällige Event-Aufgaben aus (TASK-01).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $tasks = AdventureTask::where('is_active', true)
            ->whereNull('executed_at')
            ->with('adventure')
            ->get();

        $due   = 0;
        $done  = 0;
        $skip  = 0;

        foreach ($tasks as $task) {
            if (! $task->isDue()) {
                $skip++;
                continue;
            }

            $due++;
            $this->line("  ▶ [{$task->adventure->name}] {$task->getLabel()}");

            if ($dryRun) {
                continue;
            }

            $result = $this->runTask($task);

            $task->update([
                'executed_at' => now(),
                'result'      => $result,
            ]);

            $this->info("    ✓ {$result}");
            $done++;
        }

        if ($dryRun) {
            $this->info("{$due} fällige Task(s) gefunden (Dry-Run, keine Änderungen).");
        } else {
            $this->info("{$done}/{$due} Task(s) ausgeführt, {$skip} noch nicht fällig.");
        }

        return self::SUCCESS;
    }

    /** Führt einen einzelnen Task aus und gibt einen Ergebnis-String zurück. */
    private function runTask(AdventureTask $task): string
    {
        return match ($task->task_type) {
            'open_registration'     => $this->runStatusTransition($task, EventStatus::REGISTRATION_OPEN),
            'close_registration'    => $this->runStatusTransition($task, EventStatus::REGISTRATION_CLOSED),
            'complete_event'        => $this->runStatusTransition($task, EventStatus::COMPLETED),
            'disable_waitlist'      => $this->runDisableWaitlist($task),
            'send_reminder'         => $this->runSendReminder($task),
            'notify_ep_entry'       => $this->runNotifyEpEntry($task),
            'send_survey'           => $this->runSendSurvey($task),
            'send_participants_pdf' => $this->runSendParticipantsPdf($task),
            'promote_waitlist'      => $this->runPromoteWaitlist($task),
            'archive_bookings'      => $this->runArchiveBookings($task),
            default                 => "Unbekannter Task-Typ: {$task->task_type}",
        };
    }

    private function runStatusTransition(AdventureTask $task, int $targetStatus): string
    {
        $adventure = $task->adventure;

        if ($adventure->event_status_id === EventStatus::CANCELLED) {
            return 'Übersprungen: Event ist abgesagt.';
        }

        if (! $adventure->canTransitionTo($targetStatus)) {
            return "Übergang auf Status {$targetStatus} nicht erlaubt (aktuell: {$adventure->event_status_id}).";
        }

        if ($adventure->event_status_id === $targetStatus) {
            return "Status {$targetStatus} bereits gesetzt.";
        }

        $adventure->update(['event_status_id' => $targetStatus]);

        $statusLabel = config("adventure_tasks.tasks.{$task->task_type}.label");

        return "Status auf {$targetStatus} gesetzt ({$statusLabel}).";
    }

    private function runDisableWaitlist(AdventureTask $task): string
    {
        $adventure = $task->adventure;

        if (! $adventure->waitlist_mode) {
            return 'Wartelistenmodus war bereits deaktiviert.';
        }

        $waitlisted = $adventure->bookings()
            ->where('waitlisted', true)
            ->orderBy('created_at')
            ->orderBy('id')
            ->with('player.users')
            ->get();

        $promoted = [];

        foreach ($waitlisted as $booking) {
            if ($adventure->isOutsideAgeRange($booking->player)) {
                continue;
            }
            if ($adventure->freeSlots() <= 0) {
                break;
            }
            $booking->update(['waitlisted' => false]);
            $promoted[] = $booking->participant_name;

            $email = $booking->player?->email
                ?: $booking->player?->users()->first()?->email;

            if ($email && $booking->player?->notificationEnabled('notify_waitlist_promoted')) {
                Notification::route('mail', $email)->notify(new WaitlistPromoted($booking));
            }
        }

        $adventure->update(['waitlist_mode' => false]);

        if (count($promoted) > 0) {
            return 'Wartelistenmodus deaktiviert. Nachgerückt: ' . implode(', ', $promoted) . '.';
        }

        return 'Wartelistenmodus deaktiviert. Keine Nachrücker.';
    }

    private function runSendReminder(AdventureTask $task): string
    {
        $adventure = $task->adventure;

        if ($adventure->reminder_sent_at) {
            return 'Erinnerungsmail wurde bereits versendet.';
        }

        $sent = 0;

        foreach ($adventure->bookings()->with('player.users')->get() as $booking) {
            if ($booking->status !== 'bestaetigt' || $booking->waitlisted) {
                continue;
            }
            $email = $booking->player?->email;
            if (! $email) {
                continue;
            }
            if ($booking->player->notificationEnabled('notify_event_reminder')) {
                Notification::route('mail', $email)->notify(new EventReminder($adventure));
                $sent++;
            }
        }

        $adventure->update(['reminder_sent_at' => now()]);

        return "{$sent} Erinnerungsmail(s) versendet.";
    }

    private function runNotifyEpEntry(AdventureTask $task): string
    {
        $adventure = $task->adventure;
        $gamemaster = $adventure->gamemaster;

        if (! $gamemaster?->email) {
            return 'Kein Spielleiter oder keine E-Mail-Adresse hinterlegt.';
        }

        Mail::raw(
            "Hallo {$gamemaster->name},\n\n"
            . "das Event \"{$adventure->name}\" ist beendet.\n"
            . "Bitte denke daran, die Erfahrungspunkte für die Teilnehmer einzutragen.\n\n"
            . 'Vielen Dank!',
            function ($message) use ($gamemaster, $adventure) {
                $message->to($gamemaster->email)
                    ->subject("[Heldenregister] EP-Vergabe: {$adventure->name}");
            }
        );

        return "EP-Erinnerung an {$gamemaster->email} versendet.";
    }

    private function runSendSurvey(AdventureTask $task): string
    {
        // Umfrage muss manuell im Admin-Bereich (Umfragen) für dieses Event erstellt
        // und versendet werden. Dieser Task loggt nur eine Erinnerung.
        $adventure = $task->adventure;

        $survey = \App\Models\Survey::where('adventure_id', $adventure->id)->first();

        if (! $survey) {
            return 'Keine Umfrage für dieses Event hinterlegt. Bitte unter Admin → Umfragen anlegen.';
        }

        return "Umfrage \"{$survey->id}\" für dieses Event vorhanden – Versand über Admin → Umfragen.";
    }

    private function runSendParticipantsPdf(AdventureTask $task): string
    {
        $adventure = $task->adventure;
        $eventleader = $adventure->eventleader;

        if (! $eventleader?->email) {
            return 'Kein Eventleiter oder keine E-Mail-Adresse hinterlegt.';
        }

        $pdfUrl = route('adventures.participants-pdf', $adventure);

        Mail::raw(
            "Hallo {$eventleader->name},\n\n"
            . "das Event \"{$adventure->name}\" beginnt bald.\n"
            . "Die Teilnehmerliste kann hier abgerufen werden:\n{$pdfUrl}\n\n"
            . '(Login erforderlich)',
            function ($message) use ($eventleader, $adventure) {
                $message->to($eventleader->email)
                    ->subject("[Heldenregister] Teilnehmerliste: {$adventure->name}");
            }
        );

        return "Teilnehmerlisten-Link an {$eventleader->email} versendet.";
    }

    private function runPromoteWaitlist(AdventureTask $task): string
    {
        $adventure = $task->adventure;

        $waitlisted = $adventure->bookings()
            ->where('waitlisted', true)
            ->orderBy('created_at')
            ->orderBy('id')
            ->with('player.users')
            ->get();

        $promoted = [];

        foreach ($waitlisted as $booking) {
            if ($adventure->isOutsideAgeRange($booking->player)) {
                continue;
            }
            if ($adventure->freeSlots() <= 0) {
                break;
            }
            $booking->update(['waitlisted' => false]);
            $promoted[] = $booking->participant_name;

            $email = $booking->player?->email
                ?: $booking->player?->users()->first()?->email;

            if ($email && $booking->player?->notificationEnabled('notify_waitlist_promoted')) {
                Notification::route('mail', $email)->notify(new WaitlistPromoted($booking));
            }
        }

        if (count($promoted) === 0) {
            return 'Keine Nachrücker (Warteliste leer oder Event voll).';
        }

        return count($promoted) . ' Nachrücker: ' . implode(', ', $promoted) . '.';
    }

    private function runArchiveBookings(AdventureTask $task): string
    {
        $adventure = $task->adventure;

        $count = $adventure->bookings()
            ->where('status', 'storniert')
            ->count();

        if ($count === 0) {
            return 'Keine stornierten Buchungen vorhanden.';
        }

        return "{$count} stornierte Buchung(en) vorhanden (manuelle Archivierung in Verwaltung).";
    }
}
