<?php

namespace App\Console\Commands;

use App\Models\Hero;
use Illuminate\Console\Command;

/**
 * PUB-01: Bestehende Helden ohne public_code mit einem eindeutigen Code versehen.
 */
class GenerateHeroCodes extends Command
{
    protected $signature   = 'heroes:generate-codes {--dry-run : Nur anzeigen, nichts speichern}';
    protected $description = 'Helden ohne öffentlichen Code mit einem 6-stelligen Code versehen (PUB-01)';

    public function handle(): int
    {
        $heroes = Hero::whereNull('public_code')->get();

        if ($heroes->isEmpty()) {
            $this->info('Alle Helden haben bereits einen Code.');
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $this->info(($dryRun ? '[Dry-run] ' : '')."Helden ohne Code: {$heroes->count()}");

        $bar = $this->output->createProgressBar($heroes->count());
        $bar->start();

        foreach ($heroes as $hero) {
            $code = Hero::generatePublicCode();
            if (! $dryRun) {
                $hero->update(['public_code' => $code]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info($dryRun ? 'Dry-run abgeschlossen – keine Änderungen gespeichert.' : 'Codes wurden erfolgreich gesetzt.');

        return self::SUCCESS;
    }
}
