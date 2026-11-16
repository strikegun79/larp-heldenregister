<?php

namespace Database\Seeders;

use App\Models\TaskTypeDefinition;
use Illuminate\Database\Seeder;

/**
 * Befüllt task_type_definitions aus config/adventure_tasks.php.
 * firstOrCreate: legt nur fehlende Task-Typen an, bestehende Einstellungen
 * (z. B. deaktivierte Aufgaben oder geänderte Fristen) bleiben erhalten.
 */
class TaskTypeDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = config('adventure_tasks.tasks', []);

        foreach ($tasks as $type => $def) {
            TaskTypeDefinition::firstOrCreate(
                ['task_type' => $type],
                [
                    'label'             => $def['label'],
                    'description'       => $def['description'] ?? null,
                    'default_days'      => $def['default_days'],
                    'default_reference' => $def['default_reference'],
                    'default_direction' => $def['default_direction'],
                    'is_enabled'        => true,
                ]
            );
        }
    }
}
