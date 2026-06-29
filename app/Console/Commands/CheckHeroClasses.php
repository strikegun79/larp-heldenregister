<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ETL-04: Prüft die hero2classes.class_id-Werte in der Legacy-DB auf Konsistenz.
 * Meldet nicht auflösbare Einträge und gibt eine Stichprobe aus.
 */
class CheckHeroClasses extends Command
{
    protected $signature = 'etl:check-hero-classes
                            {--sample=10 : Anzahl Stichproben-Zeilen}';

    protected $description = 'ETL-04: Prüft hero2classes.class_id auf Slug/ID-Inkonsistenz und unauflösbare Einträge';

    public function handle(): int
    {
        $this->info('ETL-04 · hero2classes.class_id-Prüfbericht');
        $this->line(str_repeat('─', 60));

        // Lookup-Maps aufbauen
        $classBySlug     = DB::table('hero_classes')->pluck('id', 'slug');     // slug → laravel id
        $classByLegacyId = DB::connection('legacy')
            ->table('type_classes')
            ->pluck('idname', 'id');                                           // legacy numeric id → slug
        $heroes = DB::table('heroes')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id');                                        // legacy_id → laravel id

        $rows = DB::connection('legacy')->table('hero2classes')->get();
        $total = $rows->count();

        $okSlug    = [];
        $okNumeric = [];
        $missing   = [];   // hero_id nicht im Mapping
        $unresolvable = []; // class_id weder slug noch numeric

        foreach ($rows as $row) {
            $heroMapped = isset($heroes[$row->hero_id]);

            if (is_numeric($row->class_id)) {
                // Numerische ID: über legacy type_classes.id auflösen
                $slug = $classByLegacyId[$row->class_id] ?? null;
                $laravelId = $slug ? ($classBySlug[$slug] ?? null) : null;

                if (! $heroMapped) {
                    $missing[] = $row;
                } elseif ($laravelId !== null) {
                    $okNumeric[] = array_merge((array) $row, ['resolved_to' => $laravelId, 'via' => 'numeric→slug']);
                } else {
                    $unresolvable[] = array_merge((array) $row, ['reason' => "numeric class_id={$row->class_id} nicht in type_classes"]);
                }
            } else {
                // Slug (idname): direkt auflösen
                $laravelId = $classBySlug[$row->class_id] ?? null;

                if (! $heroMapped) {
                    $missing[] = $row;
                } elseif ($laravelId !== null) {
                    $okSlug[] = array_merge((array) $row, ['resolved_to' => $laravelId, 'via' => 'slug']);
                } else {
                    $unresolvable[] = array_merge((array) $row, ['reason' => "slug '{$row->class_id}' nicht in hero_classes"]);
                }
            }
        }

        // Zusammenfassung
        $this->newLine();
        $this->table(
            ['Kategorie', 'Anzahl', '%'],
            [
                ['OK  Slug auflösbar',      count($okSlug),       $this->pct(count($okSlug), $total)],
                ['OK  Numerisch auflösbar', count($okNumeric),    $this->pct(count($okNumeric), $total)],
                ['!   Held nicht migriert', count($missing),      $this->pct(count($missing), $total)],
                ['ERR Unauflösbar',         count($unresolvable), $this->pct(count($unresolvable), $total)],
                ['    Gesamt',              $total,               '100%'],
            ]
        );

        // Nicht auflösbare Einträge
        if (! empty($unresolvable)) {
            $this->newLine();
            $this->error('Nicht auflösbare Klassenzuordnungen:');
            $this->table(
                ['hero_id', 'class_id', 'Grund'],
                array_map(fn ($r) => [$r['hero_id'], $r['class_id'], $r['reason']], $unresolvable)
            );
        }

        // Stichprobe auflösbarer Einträge
        $sample = (int) $this->option('sample');
        $sampleRows = array_slice(array_merge($okSlug, $okNumeric), 0, $sample);

        if (! empty($sampleRows)) {
            $this->newLine();
            $this->info("Stichprobe (max. {$sample} Einträge):");
            $this->table(
                ['hero_id (legacy)', 'class_id (legacy)', 'Laravel hero_class_id', 'Methode'],
                array_map(fn ($r) => [$r['hero_id'], $r['class_id'], $r['resolved_to'], $r['via']], $sampleRows)
            );
        }

        if (empty($unresolvable)) {
            $this->newLine();
            $this->info('Alle auflösbaren Einträge sind konsistent. Keine Datenfehler gefunden.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->warn(count($unresolvable) . ' Einträge konnten nicht aufgelöst werden. Vor Go-Live bereinigen!');

        return Command::FAILURE;
    }

    private function pct(int $count, int $total): string
    {
        if ($total === 0) {
            return '—';
        }

        return round($count / $total * 100, 1) . '%';
    }
}
