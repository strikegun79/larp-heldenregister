<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskTypeDefinition extends Model
{
    protected $primaryKey = 'task_type';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'task_type',
        'label',
        'description',
        'default_days',
        'default_reference',
        'default_direction',
        'is_enabled',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'is_enabled'   => 'boolean',
    ];

    public function adventureTasks(): HasMany
    {
        return $this->hasMany(AdventureTask::class, 'task_type', 'task_type');
    }

    /** Icon aus der PHP-Config (nicht DB-editierbar). */
    public function getIcon(): string
    {
        return config("adventure_tasks.tasks.{$this->task_type}.icon", 'clock');
    }

    /** Kategorie-Schlüssel aus der PHP-Config. */
    public function getCategoryKey(): string
    {
        return config("adventure_tasks.tasks.{$this->task_type}.category", 'management');
    }

    /** Sortiert nach der Reihenfolge in config/adventure_tasks.php. */
    public static function orderedByConfig(): \Illuminate\Support\Collection
    {
        $order = array_keys(config('adventure_tasks.tasks', []));
        $all   = static::all()->keyBy('task_type');

        return collect($order)
            ->map(fn ($type) => $all->get($type))
            ->filter();
    }
}
