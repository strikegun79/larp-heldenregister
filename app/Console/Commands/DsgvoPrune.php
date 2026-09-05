<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\SurveyResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * DSGVO Art. 5 Abs. 1 lit. e: Speicherbegrenzung – automatische Bereinigung
 * personenbezogener Daten nach definierten Aufbewahrungsfristen.
 */
class DsgvoPrune extends Command
{
    protected $signature = 'dsgvo:prune {--dry-run : Nur anzeigen, was gelöscht würde}';

    protected $description = 'DSGVO-Aufbewahrungsfristen: Unterschriften, Gesundheitsdaten, Audit-Logs und Benachrichtigungen bereinigen';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[Dry-run] Keine Änderungen werden vorgenommen.');
        }

        $this->pruneSignatures($dryRun);
        $this->pruneHealthData($dryRun);
        $this->pruneBookings($dryRun);
        $this->pruneAuditLogs($dryRun);
        $this->pruneNotifications($dryRun);
        $this->pruneSurveyTexts($dryRun);
        $this->pruneSurveyIpAddresses($dryRun);

        $this->info($dryRun ? 'Dry-run abgeschlossen.' : 'DSGVO-Bereinigung abgeschlossen.');

        return self::SUCCESS;
    }

    // Unterschriften: 30 Tage nach Event-Ende löschen.
    private function pruneSignatures(bool $dryRun): void
    {
        $query = Booking::whereNotNull('signature')
            ->whereHas('adventure', fn ($q) => $q->where('end_at', '<', now()->subDays(30)));

        $count = $query->count();
        $this->line("  Unterschriften (Event > 30 Tage): {$count}");

        if (! $dryRun && $count > 0) {
            $query->update(['signature' => null]);
        }
    }

    // Gesundheitsdaten: 2 Jahre nach Event-Ende anonymisieren.
    private function pruneHealthData(bool $dryRun): void
    {
        $query = Booking::where(fn ($q) => $q->whereNotNull('allergien')->orWhereNotNull('medikamente'))
            ->whereHas('adventure', fn ($q) => $q->where('end_at', '<', now()->subYears(2)));

        $count = $query->count();
        $this->line("  Gesundheitsdaten (Event > 2 Jahre): {$count}");

        if (! $dryRun && $count > 0) {
            $query->update(['allergien' => null, 'medikamente' => null, 'health_data_consent_at' => null]);
        }
    }

    // Buchungsdaten: 3 Jahre nach Event-Ende anonymisieren (DSGVO Art. 5 Abs. 1 lit. e).
    // Personenbezug wird gekappt; statistische Felder (adventure_id, status, paid, etc.) bleiben.
    private function pruneBookings(bool $dryRun): void
    {
        $query = Booking::whereHas('adventure', fn ($q) => $q->where('end_at', '<', now()->subYears(3)))
            ->where(fn ($q) => $q
                ->whereNotNull('player_id')
                ->orWhereNotNull('guest_name')
                ->orWhereNotNull('erreichbarkeit')
                ->orWhereNotNull('kontakt_telefon')
            );

        $count = $query->count();
        $this->line("  Buchungsdaten anonymisieren (Event > 3 Jahre): {$count}");

        if (! $dryRun && $count > 0) {
            $query->update([
                'player_id'          => null,
                'hero_id'            => null,
                'booked_by_user_id'  => null,
                'guest_name'         => null,
                'guest_lastname'     => null,
                'guest_age'          => null,
                'guest_place'        => null,
                'erreichbarkeit'     => null,
                'kontakt_telefon'    => null,
            ]);
        }
    }

    // Audit-Logs: nach 3 Jahren löschen.
    private function pruneAuditLogs(bool $dryRun): void
    {
        $count = AuditLog::where('created_at', '<', now()->subYears(3))->count();
        $this->line("  Audit-Logs (> 3 Jahre): {$count}");

        if (! $dryRun && $count > 0) {
            AuditLog::where('created_at', '<', now()->subYears(3))->delete();
        }
    }

    // Notifications: nach 1 Jahr löschen.
    private function pruneNotifications(bool $dryRun): void
    {
        $count = DB::table('notifications')->where('created_at', '<', now()->subYear())->count();
        $this->line("  Benachrichtigungen (> 1 Jahr): {$count}");

        if (! $dryRun && $count > 0) {
            DB::table('notifications')->where('created_at', '<', now()->subYear())->delete();
        }
    }

    // IP-Adressen in Umfrageantworten: 90 Tage nach Abgabe löschen.
    // Zweck ist ausschließlich Anti-Spam-Rate-Limiting; danach kein berechtigtes Interesse mehr.
    private function pruneSurveyIpAddresses(bool $dryRun): void
    {
        $count = DB::table('survey_responses')
            ->whereNotNull('ip_address')
            ->where('submitted_at', '<', now()->subDays(90))
            ->count();

        $this->line("  Umfrage-IP-Adressen (> 90 Tage): {$count}");

        if (! $dryRun && $count > 0) {
            DB::table('survey_responses')
                ->whereNotNull('ip_address')
                ->where('submitted_at', '<', now()->subDays(90))
                ->update(['ip_address' => null]);
        }
    }

    // Umfrage-Freitexte (SURV-01): 2 Jahre nach Abgabe anonymisieren.
    // Aggregierte Rating-/Ja-Nein-Antworten bleiben dauerhaft erhalten.
    private function pruneSurveyTexts(bool $dryRun): void
    {
        $count = DB::table('survey_answers')
            ->whereNotNull('text_answer')
            ->where('created_at', '<', now()->subYears(2))
            ->count();

        $this->line("  Umfrage-Freitexte (> 2 Jahre): {$count}");

        if (! $dryRun && $count > 0) {
            DB::table('survey_answers')
                ->whereNotNull('text_answer')
                ->where('created_at', '<', now()->subYears(2))
                ->update(['text_answer' => null]);
        }
    }
}
