<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskTypeDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der globalen Task-Typ-Definitionen (TASK-01).
 * Nur Bearbeiten – keine Anlage oder Löschung (Typen sind code-seitig fest).
 */
class TaskManagerController extends Controller
{
    public function index(): View
    {
        $definitions = TaskTypeDefinition::orderedByConfig();
        $categories  = config('adventure_tasks.categories', []);

        return view('admin.task-manager.index', compact('definitions', 'categories'));
    }

    public function edit(TaskTypeDefinition $taskTypeDefinition, Request $request): View
    {
        $data = ['definition' => $taskTypeDefinition];

        return $request->expectsJson()
            ? view('admin.task-manager._form', $data)
            : view('admin.task-manager.edit', $data);
    }

    public function update(Request $request, TaskTypeDefinition $taskTypeDefinition): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'label'             => ['required', 'string', 'max:100'],
            'description'       => ['nullable', 'string', 'max:500'],
            'default_days'      => ['required', 'integer', 'min:0', 'max:999'],
            'default_reference' => ['required', 'in:start_at,end_at'],
            'default_direction' => ['required', 'in:before,after'],
            'is_enabled'        => ['boolean'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');

        $taskTypeDefinition->update($data);

        return $request->expectsJson()
            ? response()->json(['message' => 'Task-Typ wurde aktualisiert.', 'reload' => true])
            : redirect()->route('admin.task-manager.index')->with('status', 'Task-Typ wurde aktualisiert.');
    }
}
