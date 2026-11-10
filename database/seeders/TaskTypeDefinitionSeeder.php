<?php

namespace Database\Seeders;

use App\Models\TaskTypeDefinition;
use Illuminate\Database\Seeder;

/**
 * Befüllt task_type_definitions aus config/adventure_tasks.php.
 * updateOrCreate: bestehende Einstellungen werden nicht überschrieben.
 */
class TaskTypeDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = config('adventure_tasks.tasks', []);

        foreach ($tasks as $type => $def) {
            TaskTypeDefinition::updateOrCreate(
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
