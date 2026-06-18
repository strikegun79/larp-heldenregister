<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Schreibt einen Audit-Log-Eintrag.
     *
     * @param  array<string, mixed>|null  $changes
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?array $changes = null,
        ?User $actor = null,
    ): void {
        $actor ??= auth()->user();

        AuditLog::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor ? trim("{$actor->name} {$actor->lastname}") : 'System',
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? static::labelFor($subject) : null,
            'changes' => $changes,
        ]);
    }

    private static function labelFor(Model $model): string
    {
        if (method_exists($model, 'auditLabel')) {
            return $model->auditLabel();
        }

        // Generischer Fallback: Name-Felder oder ID.
        foreach (['name', 'description', 'title', 'email'] as $field) {
            if (! empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        return class_basename($model).'#'.$model->getKey();
    }
}
