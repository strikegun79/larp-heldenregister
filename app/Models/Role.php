<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /**
     * Alle bekannten Rollen-Slugs (Quelle der Wahrheit für Gates & Seeder).
     * Bewusst als Konstante, damit der AuthServiceProvider beim Booten
     * keine DB-Abfrage benötigt (Migrationen würden sonst fehlschlagen).
     */
    public const ROLE_SLUGS = [
        'admin',
        'registrar',
        'project_lead',
        'game_master',
        'teamer',
        'event_booking',
        'participant',
    ];

    /**
     * Die IDs stammen aus dem Legacy-System (type_role) und werden
     * nicht automatisch vergeben.
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'slug',
        'label',
    ];

    /**
     * Benutzer mit dieser Rolle.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
