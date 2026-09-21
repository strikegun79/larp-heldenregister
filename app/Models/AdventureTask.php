<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdventureTask extends Model
{
    protected $fillable = [
        'adventure_id',
        'task_type',
        'trigger_reference',
        'trigger_direction',
        'trigger_days',
        'is_active',
        'executed_at',
        'result',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'trigger_days' => 'integer',
        'executed_at'  => 'datetime',
    ];

    public function adventure(): BelongsTo
    {
        return $this->belongsTo(Adventure::class);
    }

    /** Berechnet das Auslösedatum basierend auf Referenz und Offset. */
    public function triggerDate(): ?Carbon
    {
        $reference = $this->adventure->{$this->trigger_reference};

        if (! $reference) {
            return null;
        }

        return $this->trigger_direction === 'before'
            ? $reference->copy()->subDays($this->trigger_days)->startOfDay()
            : $reference->copy()->addDays($this->trigger_days)->startOfDay();
    }

    /** Ist dieser Task heute fällig (aktiv, noch nicht ausgeführt, Datum erreicht)? */
    public function isDue(): bool
    {
        if (! $this->is_active || $this->executed_at !== null) {
            return false;
        }

        $trigger = $this->triggerDate();

        return $trigger && $trigger->lte(now()->startOfDay());
    }

    /** Label aus der DB-Definition (Fallback: Config, dann task_type). */
    public function getLabel(): string
    {
        return TaskTypeDefinition::find($this->task_type)?->label
            ?? config("adventure_tasks.tasks.{$this->task_type}.label", $this->task_type);
    }
}
