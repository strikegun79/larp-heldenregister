<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Booking;
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
        $this->pruneAuditLogs($dryRun);
        $this->pruneNotifications($dryRun);

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
}
